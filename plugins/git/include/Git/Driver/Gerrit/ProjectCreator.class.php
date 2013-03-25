<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RemoteSSHCommand.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';
require_once GIT_BASE_DIR . '/GitRepository.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/MembershipManager.class.php';
require_once 'UserFinder.class.php';

class Git_Driver_Gerrit_ProjectCreator {

    const GROUP_REPLICATION = 'replication';

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var string */
    private $dir;

    /** @var Git_Driver_Gerrit_UserFinder */
    private $user_finder;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct($dir, Git_Driver_Gerrit $driver, Git_Driver_Gerrit_UserFinder $user_finder, UGroupManager $ugroup_manager) {
        $this->dir            = $dir;
        $this->driver         = $driver;
        $this->user_finder    = $user_finder;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function createGerritProject(Git_RemoteServer_GerritServer $gerrit_server, GitRepository $repository) {
        $admin_group = null;
        $ugroups = $this->ugroup_manager->getUGroups($repository->getProject());
        $good_ugroups = array();
        foreach ($ugroups as $ugroup) {
            try {
                if ($ugroup->getId() > UGroup::NONE ||  $ugroup->getId() == UGroup::PROJECT_MEMBERS || $ugroup->getId() == UGroup::PROJECT_ADMIN) {
                    if ($ugroup->getId() == UGroup::PROJECT_ADMIN) {
                        $admin_group = $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
                    }
                    $this->driver->createGroup($gerrit_server,
                        $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName(),
                        $ugroup->getUserLdapIds($repository->getProject()->getID())
                    );
                    $good_ugroups[] = $ugroup;
                }
            } catch (Exception $e) {
                // Continue with the next group
            }
        }

        $parent_project_name = $repository->getProject()->getUnixName();

        if (! $this->driver->parentProjectExists($gerrit_server, $parent_project_name)) {
            $parent_project_name = $this->driver->createParentProject($gerrit_server, $repository, $admin_group);
        }

        $gerrit_project = $this->driver->createProject($gerrit_server, $repository, $parent_project_name);

        $this->initiatePermissions(
            $repository,
            $gerrit_server,
            $gerrit_server->getCloneSSHUrl($gerrit_project),
            $good_ugroups,
            Config::get('sys_default_domain') .'-'. self::GROUP_REPLICATION
        );
        
        $this->exportGitBranches($gerrit_server, $gerrit_project, $repository);

        return $gerrit_project;
    }

    private function exportGitBranches(Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project, GitRepository $repository) {
        $gerrit_project_url = escapeshellarg($gerrit_server->getCloneSSHUrl($gerrit_project));
        $cmd                = "cd ".$repository->getFullPath()."; git push $gerrit_project_url refs/heads/*:refs/heads/*; git push $gerrit_project_url refs/tags/*:refs/tags/*";
        `$cmd`;
    }

    private function initiatePermissions(GitRepository $repository, Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project_url, array $ugroups, $replication_group) {
        $this->cloneGerritProjectConfig($gerrit_server, $gerrit_project_url);
        foreach ($ugroups as $ugroup) {
            $this->addGroupToGroupFile($gerrit_server, $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName());
        }
        $this->addGroupToGroupFile($gerrit_server, $replication_group);
        $this->addGroupToGroupFile($gerrit_server, 'Administrators');
        $this->addRegisteredUsersGroupToGroupFile();
        $this->addPermissionsToProjectConf($repository, $ugroups, $replication_group);
        $this->pushToServer();
    }

    private function cloneGerritProjectConfig(Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project_url) {
        $gerrit_project_url = escapeshellarg($gerrit_project_url);
        `mkdir $this->dir`;
        `cd $this->dir; git init`;
        $this->setUpCommitter($gerrit_server);
        `cd $this->dir; git remote add origin $gerrit_project_url`;
        `cd $this->dir; git pull origin refs/meta/config`;
        `cd $this->dir; git checkout FETCH_HEAD`;
    }

    private function setUpCommitter(Git_RemoteServer_GerritServer $gerrit_server) {
        $name  = escapeshellarg($gerrit_server->getLogin());
        $email = escapeshellarg('codendiadm@'. Config::get('sys_default_domain'));
        `cd $this->dir; git config --add user.name $name`;
        `cd $this->dir; git config --add user.email $email`;
    }

    private function addGroupToGroupFile(Git_RemoteServer_GerritServer $gerrit_server, $group) {
        $group_uuid = $this->driver->getGroupUUID($gerrit_server, $group);
        $this->addGroupDefinitionToGroupFile($group_uuid, $group);
    }

    private function addRegisteredUsersGroupToGroupFile() {
        $this->addGroupDefinitionToGroupFile('global:Registered-Users', 'Registered Users');
    }

    private function addGroupDefinitionToGroupFile($uuid, $group_name) {
        file_put_contents("$this->dir/groups", "$uuid\t$group_name\n", FILE_APPEND);
    }

    private function addPermissionsToProjectConf(GitRepository $repository, array $ugroups, $replication_group) {
        // https://groups.google.com/d/msg/repo-discuss/jTAY2ApcTGU/DPZz8k0ZoUMJ
        // Project owners are those who own refs/* within that project... which
        // means they can modify the permissions for any reference in the
        // project.

        $ugroup_ids_read = $this->user_finder->getUgroups($repository->getId(), Git::PERM_READ);
        $ugroup_ids_write = $this->user_finder->getUgroups($repository->getId(), Git::PERM_WRITE);
        $ugroup_ids_rewind = $this->user_finder->getUgroups($repository->getId(), Git::PERM_WPLUS);

        $ugroups_read   = array();
        $ugroups_write  = array();
        $ugroups_rewind = array();

        foreach ($ugroups as $ugroup) {
            if(in_array($ugroup->getId(), $ugroup_ids_read)) {
                $ugroups_read[] = $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
            }
            if (in_array($ugroup->getId(), $ugroup_ids_write)) {
                $ugroups_write[] = $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
            }
            if (in_array($ugroup->getId(), $ugroup_ids_rewind)) {
                $ugroups_rewind[] = $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
            }
        }

        if (in_array(UGroup::REGISTERED, $ugroup_ids_read) && $this->shouldAddRegisteredUsers($repository)) {
            $ugroups_read[] = 'Registered Users';
        }

        $this->addToSection('refs', 'Read', "group $replication_group");

        /*if ($this->shouldAddRegisteredUsers($repository) && !in_array(UGroup::REGISTERED, $ugroup_ids_read)) {
            $this->addToSection('refs/heads', 'Read', "group Registered Users");
        }*/
        foreach ($ugroups_read as $ugroup_read) {
            $this->addToSection('refs/heads', 'Read', "group $ugroup_read");
            $this->addToSection('refs/heads', 'label-Code-Review', "-1..+1 group $ugroup_read");
        }
        foreach ($ugroups_write as $ugroup_write) {
            $this->addToSection('refs/heads', 'Read', "group $ugroup_write");
            $this->addToSection('refs/heads', 'create', "group $ugroup_write");
            $this->addToSection('refs/heads', 'forgeAuthor', "group $ugroup_write");
            $this->addToSection('refs/heads', 'label-Code-Review', "-2..+2 group $ugroup_write");
            $this->addToSection('refs/heads', 'label-Verified', "-1..+1 group $ugroup_write");
            $this->addToSection('refs/heads', 'submit', "group $ugroup_write");
            $this->addToSection('refs/heads', 'push', "group $ugroup_write");
            $this->addToSection('refs/heads', 'pushMerge', "group $ugroup_write");
        }
        foreach ($ugroups_rewind as $ugroup_rewind) {
            $this->addToSection('refs/heads', 'push', "+force group $ugroup_rewind");
        }

        $this->addToSection('refs/heads', 'create', "group Administrators");  // push initial ref
        $this->addToSection('refs/heads', 'forgeCommitter', "group Administrators"); // push initial ref

        foreach ($ugroups_read as $ugroup_read) {
            $this->addToSection('refs/changes', 'push', "group $ugroup_read");
        }
        foreach ($ugroups_write as $ugroup_write) {
            $this->addToSection('refs/changes', 'push', "group $ugroup_write");
            $this->addToSection('refs/changes', 'pushMerge', "group $ugroup_write");
        }
        foreach ($ugroups_rewind as $ugroup_rewind) {
            $this->addToSection('refs/changes', 'push', "+force group $ugroup_rewind");
        }

        foreach ($ugroups_read as $ugroup_read) {
            $this->addToSection('refs/for/refs/heads', 'push', "group $ugroup_read");
        }
        foreach ($ugroups_write as $ugroup_write) {
            $this->addToSection('refs/for/refs/heads', 'push', "group $ugroup_write");
            $this->addToSection('refs/for/refs/heads', 'pushMerge', "group $ugroup_write");
        }
        // To be able to push merge commit on master, we need pushMerge on refs/for/*
        // http://code.google.com/p/gerrit/issues/detail?id=1072
        $this->addToSection('refs/for', 'pushMerge', "group Administrators"); // push initial ref

        foreach ($ugroups_read as $ugroup_read) {
            $this->addToSection('refs/tags', 'read', "group $ugroup_read");
        }
        foreach ($ugroups_write as $ugroup_write) {
            $this->addToSection('refs/tags', 'read', "group $ugroup_write");
            $this->addToSection('refs/tags', 'pushTag', "group $ugroup_write");
        }

        $this->addToSection('refs/tags', 'pushTag', "group Administrators"); // push initial ref
        $this->addToSection('refs/tags', 'create', "group Administrators");  // push initial ref
        $this->addToSection('refs/tags', 'forgeCommitter', "group Administrators");  // push initial ref
    }

    private function shouldAddRegisteredUsers(GitRepository $repository) {
        return $repository->getProject()->isPublic() && $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $repository);
    }

    private function addToSection($section, $permission, $value) {
        `cd $this->dir; git config -f project.config --add access.$section/*.$permission '$value'`;
    }
    private function pushToServer() {
        `cd $this->dir; git add project.config groups`;
        `cd $this->dir; git commit -m 'Updated project config and access rights'`; //TODO: what about author name?
        `cd $this->dir; git push origin HEAD:refs/meta/config`;
    }

    public function removeTemporaryDirectory() {
        $backend = Backend::instance('System');
        $backend->recurseDeleteInDir($this->dir);
        rmdir($this->dir);
    }
}

?>
