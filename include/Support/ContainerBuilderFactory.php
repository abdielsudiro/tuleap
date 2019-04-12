<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Support;

use DI\ContainerBuilder;
use ParagonIE\EasyDB\EasyDB;
use ProjectManager;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Baseline\Adapter\BaselineArtifactRepositoryAdapter;
use Tuleap\Baseline\Adapter\BaselineRepositoryAdapter;
use Tuleap\Baseline\Adapter\ClockAdapter;
use Tuleap\Baseline\Adapter\ComparisonRepositoryAdapter;
use Tuleap\Baseline\Adapter\CurrentUserProviderAdapter;
use Tuleap\Baseline\Adapter\ProjectRepositoryAdapter;
use Tuleap\Baseline\BaselineArtifactRepository;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\Clock;
use Tuleap\Baseline\ComparisonRepository;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\ProjectRepository;
use Tuleap\DB\DBFactory;
use Tuleap\REST\UserManager;
use function DI\autowire;
use function DI\factory;

/**
 * Configure all dependencies for injections.
 */
class ContainerBuilderFactory
{
    public static function create(): ContainerBuilder
    {
        $container_builder = new ContainerBuilder();
        return $container_builder->addDefinitions(
            [
                Clock::class                             => autowire(ClockAdapter::class),
                UserManager::class                       => factory([UserManager::class, 'build']),
                CurrentUserProvider::class               => autowire(CurrentUserProviderAdapter::class),
                BaselineRepository::class                => autowire(BaselineRepositoryAdapter::class),
                ComparisonRepository::class              => autowire(ComparisonRepositoryAdapter::class),
                BaselineArtifactRepository::class        => autowire(BaselineArtifactRepositoryAdapter::class),
                ProjectRepository::class                 => autowire(ProjectRepositoryAdapter::class),
                ProjectManager::class                    => factory([ProjectManager::class, 'instance']),
                Tracker_ArtifactFactory::class           => factory([Tracker_ArtifactFactory::class, 'instance']),
                Tracker_Artifact_ChangesetFactory::class => factory(
                    [Tracker_Artifact_ChangesetFactoryBuilder::class, 'build']
                ),
                EasyDB::class                            => static function () {
                    return DBFactory::getMainTuleapDBConnection()->getDB();
                },
                TrackerFactory::class                    => factory([TrackerFactory::class, 'instance']),
                Tracker_FormElementFactory::class        => factory([Tracker_FormElementFactory::class, 'instance'])
            ]
        );
    }
}
