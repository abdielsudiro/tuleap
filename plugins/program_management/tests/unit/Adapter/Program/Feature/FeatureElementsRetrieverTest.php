<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Program;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

class FeatureElementsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $parent_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BackgroundColorRetriever
     */
    private $retrieve_background;

    /**
     * @var FeatureElementsRetriever
     */
    private $retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FeaturesStore
     */
    private $features_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
     */
    private $build_program;

    protected function setUp(): void
    {
        $this->features_dao         = \Mockery::mock(FeaturesStore::class);
        $this->build_program        = \Mockery::mock(BuildProgram::class);
        $this->artifact_factory     = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->retrieve_background  = \Mockery::mock(BackgroundColorRetriever::class);
        $this->parent_dao           = \Mockery::mock(ArtifactsLinkedToParentDao::class);

        $this->retriever = new FeatureElementsRetriever(
            $this->build_program,
            $this->features_dao,
            new FeatureRepresentationBuilder(
                $this->artifact_factory,
                $this->form_element_factory,
                $this->retrieve_background,
                new VerifyIsVisibleFeatureAdapter($this->artifact_factory),
                new UserStoryLinkedToFeatureChecker($this->parent_dao, \Mockery::mock(BuildPlanning::class), $this->artifact_factory)
            )
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(202));
        $this->features_dao->shouldReceive('searchPlannableFeatures')->andReturn(
            [
                ['tracker_name' => 'User stories', 'artifact_id' => 1, 'artifact_title' => 'Artifact 1', 'field_title_id' => 1],
                ['tracker_name' => 'Features', 'artifact_id' => 2, 'artifact_title' => 'Artifact 2', 'field_title_id' => 1],
            ]
        );

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(1)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnTrue();

        $project      = ProjectTestBuilder::aProject()->withId(202)->withPublicName('My project')->build();
        $tracker_one  = $this->buildTracker(1, 'bug', $project);
        $artifact_one = $this->buildArtifact(1, $tracker_one);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact_one);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 1)->andReturn($artifact_one);

        $tracker_two  = $this->buildTracker(2, 'user stories', $project);
        $artifact_two = $this->buildArtifact(2, $tracker_two);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(2)->andReturn($artifact_two);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 2)->andReturn($artifact_two);

        $this->retrieve_background->shouldReceive('retrieveBackgroundColor')
            ->andReturn(new BackgroundColor("lake-placid-blue"));

        $this->parent_dao->shouldReceive('getPlannedUserStory')->andReturn([]);
        $this->parent_dao->shouldReceive('getChildrenOfFeatureInTeamProjects')->twice()->andReturn([]);

        $collection = [
            new FeatureRepresentation(
                1,
                'Artifact 1',
                'bug #1',
                '/plugins/tracker/?aid=1',
                MinimalTrackerRepresentation::build($tracker_one),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
            new FeatureRepresentation(
                2,
                'Artifact 2',
                'user stories #2',
                '/plugins/tracker/?aid=2',
                MinimalTrackerRepresentation::build($tracker_two),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
        ];

        self::assertEquals($collection, $this->retriever->retrieveFeaturesToBePlanned(202, $user));
    }

    private function buildTracker(int $tracker_id, string $name, Project $project): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withName($name)
            ->withColor(TrackerColor::fromName('deep-blue'))
            ->withProject($project)
            ->build();
    }

    private function buildArtifact(int $artifact_id, \Tracker $tracker): Artifact
    {
        $artifact = new Artifact($artifact_id, $tracker->getId(), 110, 1234567890, false);
        $artifact->setTracker($tracker);
        return $artifact;
    }
}
