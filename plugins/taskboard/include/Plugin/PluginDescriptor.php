<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Plugin;

class PluginDescriptor extends \PluginDescriptor
{
    public function __construct()
    {
        parent::__construct(
            dgettext('tuleap-taskboard', 'Taskboard'),
            false,
            dgettext('tuleap-taskboard', 'Visually keep track of things that need to get done in a task board, used in Agile Dashboard.')
        );

        $this->setVersionFromFile(__DIR__ . '/../../VERSION');
    }
}
