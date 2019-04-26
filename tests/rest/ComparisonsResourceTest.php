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

namespace Tuleap\Baseline\Tests\REST;

require_once __DIR__ . '/BaselineFixtureData.php';

use RestBase;

class ComparisonsResourceTest extends RestBase
{
    /** @var int */
    private $an_artifact_id;

    public function setUp(): void
    {
        parent::setUp();

        $artifact_ids_by_title = $this->getArtifactIdsIndexedByTitle(
            BaselineFixtureData::PROJECT_NAME,
            BaselineFixtureData::TRACKER_NAME
        );
        $this->an_artifact_id  = $artifact_ids_by_title[BaselineFixtureData::ARTIFACT_TITLE];
    }

    public function testPostBaselineComparison()
    {
        $base_baseline        = $this->createABaseline($this->an_artifact_id);
        $compared_to_baseline = $this->createABaseline($this->an_artifact_id);

        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->post(
                'baselines_comparisons',
                null,
                json_encode(
                    [
                        'name'                    => 'new comparison',
                        'comment'                 => 'used fo tests',
                        'base_baseline_id'        => $base_baseline['id'],
                        'compared_to_baseline_id' => $compared_to_baseline['id']
                    ]
                )
            )
        );
        $this->assertEquals(201, $response->getStatusCode());
        $json_response = $response->json();

        $this->assertNotNull($json_response['id']);
        $this->assertEquals('new comparison', $json_response['name']);
        $this->assertEquals('used fo tests', $json_response['comment']);
        $this->assertEquals($base_baseline['id'], $json_response['base_baseline_id']);
        $this->assertEquals($compared_to_baseline['id'], $json_response['compared_to_baseline_id']);
        $this->assertNotNull($json_response['author_id']);
        $this->assertNotNull($json_response['creation_date']);
    }

    public function testGetBaselineComparison()
    {
        $comparison = $this->createAComparison($this->an_artifact_id);

        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->get('baselines_comparisons/' . $comparison['id'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json_response = $response->json();

        $this->assertEquals($comparison['id'], $json_response['id']);
        $this->assertEquals($comparison['name'], $json_response['name']);
        $this->assertEquals($comparison['comment'], $json_response['comment']);
        $this->assertEquals($comparison['base_baseline_id'], $json_response['base_baseline_id']);
        $this->assertEquals($comparison['compared_to_baseline_id'], $json_response['compared_to_baseline_id']);
        $this->assertNotNull($json_response['author_id']);
        $this->assertNotNull($json_response['creation_date']);
    }

    public function testDelete()
    {
        $comparison = $this->createAComparison($this->an_artifact_id);

        $delete_response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->delete('baselines_comparisons/' . $comparison['id'])
        );

        $this->assertEquals(200, $delete_response->getStatusCode());

        $get_response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->get('baselines_comparisons/' . $comparison['id'])
        );
        $this->assertEquals(404, $get_response->getStatusCode());
    }

    /**
     * @depends testPostBaselineComparison
     */
    public function testGetByProject(): void
    {
        $project_id = $this->project_ids[BaselineFixtureData::PROJECT_NAME];
        $url        = 'projects/' . $project_id . '/baselines_comparisons?limit=2';
        $response   = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->get($url)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json_response = $response->json();
        $this->assertGreaterThanOrEqual(1, $json_response['total_count']);

        $comparisons_response = $json_response['comparisons'];
        $this->assertGreaterThanOrEqual(1, count($comparisons_response));
        $this->assertLessThanOrEqual(2, count($comparisons_response));

        $baseline_response = $comparisons_response[0];
        $this->assertNotNull($baseline_response['id']);
        $this->assertNotNull($baseline_response['name']);
        $this->assertNotNull($baseline_response['base_baseline_id']);
        $this->assertNotNull($baseline_response['compared_to_baseline_id']);
        $this->assertNotNull($baseline_response['author_id']);
        $this->assertNotNull($baseline_response['creation_date']);
    }

    private function createABaseline(int $artifact_id): array
    {
        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->post(
                'baselines',
                null,
                json_encode(
                    [
                        'name'        => 'created baseline',
                        'artifact_id' => $artifact_id
                    ]
                )
            )
        );
        return $response->json();
    }

    private function createAComparison(int $artifact_id): array
    {
        $base_baseline        = $this->createABaseline($artifact_id);
        $compared_to_baseline = $this->createABaseline($artifact_id);

        $response = $this->getResponseByName(
            BaselineFixtureData::TEST_USER_NAME,
            $this->client->post(
                'baselines_comparisons',
                null,
                json_encode(
                    [
                        'name'                    => 'created comparison',
                        'base_baseline_id'        => $base_baseline['id'],
                        'compared_to_baseline_id' => $compared_to_baseline['id']
                    ]
                )
            )
        );
        return $response->json();
    }
}
