<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueFormatter;

/**
 * I hold all the submitted values for the initial (first) changeset of an artifact.
 * Artifact links field has special treatment so that we can handle the "reverse" artifact links separately.
 * We don't want them in $fields_data.
 */
final class InitialChangesetValuesContainer
{
    /**
     * @param Option<NewArtifactLinkInitialChangesetValue> $artifact_links
     */
    public function __construct(private array $fields_data, private readonly Option $artifact_links)
    {
        $artifact_links->apply(function (NewArtifactLinkInitialChangesetValue $changeset_value) {
            // We must still add forward artifact links to $fields_data so that it can be saved and processed downstream
            $this->fields_data[$changeset_value->getFieldId()] = NewArtifactLinkInitialChangesetValueFormatter::formatForWebUI(
                $changeset_value
            );
        });
    }

    /**
     * @psalm-mutation-free
     */
    public function getFieldsData(): array
    {
        return $this->fields_data;
    }

    /**
     * @psalm-mutation-free
     * @return Option<NewArtifactLinkInitialChangesetValue>
     */
    public function getArtifactLinkValue(): Option
    {
        return $this->artifact_links;
    }
}
