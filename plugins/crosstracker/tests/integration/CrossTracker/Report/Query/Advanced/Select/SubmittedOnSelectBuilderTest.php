<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use DateTimeImmutable;
use DateTimeZone;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerExpertReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class SubmittedOnSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private PFUser $user;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var array<int, string>
     */
    private array $expected_results;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_submitted_on_field_id = $tracker_builder->buildSubmittedOnField($release_tracker->getId());
        $sprint_submitted_on_field_id  = $tracker_builder->buildSubmittedOnField($sprint_tracker->getId());

        $tracker_builder->setReadPermission(
            $release_submitted_on_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_submitted_on_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_date        = (new DateTimeImmutable('2024-07-05 17:03'));
        $sprint_date         = (new DateTimeImmutable('2024-12-24 02:58'));
        $release_artifact_id = $tracker_builder->buildArtifact($release_tracker->getId(), $release_date->getTimestamp());
        $sprint_artifact_id  = $tracker_builder->buildArtifact($sprint_tracker->getId(), $sprint_date->getTimestamp());

        $tracker_builder->buildLastChangeset($release_artifact_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_id);

        $this->expected_results = [
            $release_artifact_id => $release_date->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            $sprint_artifact_id  => $sprint_date->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
        ];
    }

    private function getQueryResults(CrossTrackerExpertReport $report, PFUser $user): CrossTrackerReportContentRepresentation
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        assert($result instanceof CrossTrackerReportContentRepresentation);
        return $result;
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerExpertReport(
                1,
                "SELECT @submitted_on FROM @project = 'self' WHERE @submitted_on >= '1970-01-01'",
                $this->trackers,
            ),
            $this->user,
        );
        self::assertSame(2, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@submitted_on', $result->selected[1]->name);
        self::assertSame('date', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@submitted_on', $artifact);
            $value = $artifact['@submitted_on'];
            self::assertInstanceOf(DateResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_results),
            $values
        );
    }
}
