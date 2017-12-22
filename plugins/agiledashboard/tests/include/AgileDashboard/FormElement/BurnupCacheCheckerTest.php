<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Agiledashboard\FormElement;

use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;

require_once dirname(__FILE__) . '/../../../bootstrap.php';

class BurnupCacheCheckerTest extends \TuleapTestCase
{
    /**
     * @var ChartCachedDaysComparator
     */
    private $cache_days_comparator;
    /**
     * @var BurnupCacheGenerator
     */
    private $cache_generator;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Tracker_Artifact
     */
    private $artifact;
    /**
     * @var BurnupData
     */
    private $burnup_data;
    /**
     * @var BurnupCacheChecker
     */
    private $burnup_cache_Checker;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_value_checker;

    public function setUp()
    {
        parent::setUp();

        $this->cache_generator            = mock('Tuleap\Agiledashboard\FormElement\BurnupCacheGenerator');
        $this->chart_value_checker  = mock('Tuleap\Tracker\FormElement\ChartConfigurationValueChecker');
        $burnup_cache_dao           = mock('Tuleap\Agiledashboard\FormElement\BurnupCacheDao');
        $this->cache_days_comparator      = mock('Tuleap\Tracker\FormElement\ChartCachedDaysComparator');
        $this->burnup_cache_Checker = new BurnupCacheChecker(
            $this->cache_generator,
            $this->chart_value_checker,
            $burnup_cache_dao,
            $this->cache_days_comparator
        );

        $this->artifact = aMockArtifact()->withId(101)->build();

        $start_date        = new \DateTime();
        $duration          = 10;
        $this->burnup_data = new BurnupData(
            new \TimePeriodWithoutWeekEnd($start_date->getTimestamp(), $duration),
            false
        );

        $this->user = aUser()->withId(101)->build();
    }

    public function itReturnsFalseWhenStartDateFieldIsNotReadable()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(false);

        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->burnup_data, $this->user)
        );
    }

    public function itReturnsTrueWhenBurnupIsAlreadyUnderCalculation()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        stub($this->cache_generator)->isCacheBurnupAlreadyAsked($this->artifact)->returns(true);

        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->burnup_data, $this->user)
        );
    }

    public function itReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnup()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        stub($this->cache_generator)->isCacheBurnupAlreadyAsked($this->artifact)->returns(false);
        stub($this->cache_days_comparator)->areDaysIdentical()->returns(false);

        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->burnup_data, $this->user)
        );
        expect($this->cache_generator)->forceBurnupCacheGeneration($this->artifact->getId())->once();
    }

    public function itReturnsFalseWhenBurnupHasNoNeedToBeComputed()
    {
        stub($this->chart_value_checker)->hasStartDate()->returns(true);
        stub($this->cache_generator)->isCacheBurnupAlreadyAsked($this->artifact)->returns(false);
        stub($this->cache_days_comparator)->areDaysIdentical()->returns(true);

        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->burnup_data, $this->user)
        );
        expect($this->cache_generator)->forceBurnupCacheGeneration($this->artifact->getId())->never();
    }
}
