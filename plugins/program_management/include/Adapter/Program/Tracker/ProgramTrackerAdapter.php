<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Tracker;

use TrackerFactory;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlannableTrackerCannotBeEmptyException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramPlannableTracker;

final class ProgramTrackerAdapter implements BuildTracker
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @return array<ProgramPlannableTracker>
     * @throws ProgramTrackerException
     * @throws PlannableTrackerCannotBeEmptyException
     */
    public function buildPlannableTrackerList(array $plannable_trackers_id, int $project_id): array
    {
        $plannable_trackers_ids = [];
        foreach ($plannable_trackers_id as $tracker_id) {
            $plannable_tracker        = ProgramPlannableTracker::build($this, $tracker_id, $project_id);
            $plannable_trackers_ids[] = $plannable_tracker;
        }

        if (empty($plannable_trackers_ids)) {
            throw new PlannableTrackerCannotBeEmptyException();
        }

        return $plannable_trackers_ids;
    }

    /**
     * @throws ProgramTrackerException
     */
    public function checkTrackerIsValid(int $tracker_id, int $project_id): void
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new PlanTrackerNotFoundException($tracker_id);
        }

        if ((int) $tracker->getGroupId() !== $project_id) {
            throw new PlanTrackerDoesNotBelongToProjectException($tracker_id, $project_id);
        }
    }
}
