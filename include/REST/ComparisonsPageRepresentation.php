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

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Comparison;
use Tuleap\Baseline\ComparisonsPage;
use Tuleap\REST\JsonCast;

class ComparisonsPageRepresentation
{
    /** @var ComparisonRepresentation[] */
    public $comparisons;

    /** @var int */
    public $total_count;

    /**
     * @param $comparisons ComparisonRepresentation[]
     */
    public function __construct(array $comparisons, int $total_count)
    {
        $this->comparisons = $comparisons;
        $this->total_count = $total_count;
    }

    public static function build(ComparisonsPage $comparisons_page)
    {
        $comparison_representations = array_map(
            function (Comparison $comparison) {
                return ComparisonRepresentation::fromComparison($comparison);
            },
            $comparisons_page->getComparisons()
        );
        return new self(
            $comparison_representations,
            JsonCast::toInt($comparisons_page->getTotalComparisonsCount())
        );
    }

    public function getTotalCount(): int
    {
        return $this->total_count;
    }
}
