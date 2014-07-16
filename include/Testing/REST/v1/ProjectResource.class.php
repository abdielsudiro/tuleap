<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Testing\REST\v1;

use \Luracast\Restler\RestException;
use \Tuleap\REST\Header;
use \UserManager;
use \TrackerFactory;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \PFUser;
use \Tuleap\Testing\Config;
use \ProjectManager;
use \Tuleap\Testing\Dao;

class ProjectResource {

    const MAX_LIMIT = 50;

    /** @var PFUser */
    private $user;

    /** @var Config */
    private $config;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    public function __construct() {
        $this->config                       = new Config(new Dao());
        $this->project_manager              = ProjectManager::instance();
        $this->tracker_factory              = TrackerFactory::instance();
        $this->tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get campaigns
     *
     * Get testing campaigns for a given project
     *
     * @url GET {id}/campaigns
     *
     * @param int $id Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Testing\REST\v1\CampaignRepresentation}
     */
    protected function getId($id, $limit = 10, $offset = 0) {
        $project    = $this->project_manager->getProject($id);
        $this->user = UserManager::instance()->getCurrentUser();

        if ($project->isError()) {
            throw new RestException(404, 'Project not found');
        }

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);

        if (! $campaign_tracker_id) {
            throw new RestException(400, 'The campaign tracker id is not well configured');
        }

        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);

        if (! $campaign_tracker) {
            throw new RestException(404, 'The campaign tracker does not exist');
        }

        if (! $campaign_tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to campaign tracker');
        }

        $artifact_list = $this->tracker_artifact_factory->getArtifactsByTrackerIdUserCanView($this->user, $campaign_tracker_id);

        $result = array();

        foreach ($artifact_list as $artifact) {
            $campaign_representation = new CampaignRepresentation();
            $campaign_representation->build($artifact, $this->tracker_form_element_factory, $this->user);
            $result[] = $campaign_representation;
        }

        $this->sendPaginationHeaders($limit, $offset, count($result));

        return array_slice($result, $offset, $limit);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}