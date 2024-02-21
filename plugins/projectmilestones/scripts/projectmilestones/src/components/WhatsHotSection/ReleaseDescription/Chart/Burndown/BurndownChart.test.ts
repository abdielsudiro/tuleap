/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { MilestoneData } from "../../../../../type";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import BurndownChart from "./BurndownChart.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";

describe("BurndownChart", () => {
    function getPersonalWidgetInstance(): VueWrapper<InstanceType<typeof BurndownChart>> {
        return shallowMount(BurndownChart, {
            propsData: {
                release_data: {
                    id: 2,
                    burndown_data: null,
                } as MilestoneData,
                burndown_data: null,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("When component is renderer, Then there is a svg element with id of release", () => {
        const wrapper = getPersonalWidgetInstance();
        expect(wrapper.element).toMatchSnapshot();
    });
});
