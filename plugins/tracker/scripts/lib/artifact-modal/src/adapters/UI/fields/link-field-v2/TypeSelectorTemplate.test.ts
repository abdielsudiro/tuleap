/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { getTypeSelectorTemplate } from "./TypeSelectorTemplate";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./LinkField";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { FORWARD_DIRECTION } from "../../../../domain/fields/link-field-v2/LinkType";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";

function getSelectMainOptionsGroup(select: HTMLSelectElement): HTMLOptGroupElement {
    const optgroup = select.querySelector("[data-test=link-type-select-optgroup]");
    if (!(optgroup instanceof HTMLOptGroupElement)) {
        throw new Error("The main <optgroup> can't be found in the target");
    }
    return optgroup;
}

describe("TypeSelectorTemplate", () => {
    let host: HostElement,
        allowed_link_types: CollectionOfAllowedLinksTypesPresenters,
        cross_reference: ArtifactCrossReference | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        allowed_link_types =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType([
                {
                    shortname: IS_CHILD_LINK_TYPE,
                    forward_label: "Child",
                    reverse_label: "Parent",
                },
                {
                    shortname: "_covered_by",
                    forward_label: "Covered by",
                    reverse_label: "Covers",
                },
            ]);
        cross_reference = ArtifactCrossReferenceStub.withRef("story #150");
    });

    const render = (): HTMLSelectElement => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        host = {
            field_presenter: LinkFieldPresenter.fromFieldAndCrossReference(
                {
                    field_id: 276,
                    type: "art_link",
                    label: "Artifact link",
                    allowed_types: [],
                },
                cross_reference
            ),
            allowed_link_types,
            current_link_type: LinkTypeStub.buildUntyped(),
        } as HostElement;

        const updateFunction = getTypeSelectorTemplate(host);
        updateFunction(host, target);

        const select = target.querySelector("[data-test=link-type-select]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("An expected element has not been found in template");
        }
        return select;
    };

    it("should build the type selector", () => {
        const select = render();
        const optgroup = getSelectMainOptionsGroup(select);

        expect(optgroup.label).toBe("story #150");

        const options_with_label = Array.from(select.options).filter(
            (option) => option.label !== "–"
        );
        const separators = Array.from(select.options).filter((option) => option.label === "–");
        expect(separators).toHaveLength(2);
        expect(options_with_label).toHaveLength(3);

        const [untyped_option, child_option, covered_by_option] = options_with_label;
        expect(untyped_option.selected).toBe(true);
        expect(untyped_option.label).toBe("Linked to");
        expect(child_option.label).toBe("Child");
        expect(covered_by_option.label).toBe("Covered by");
    });

    it("Should display 'New artifact' when there is no artifact cross reference (creation mode)", () => {
        cross_reference = null;
        const select = render();

        expect(getSelectMainOptionsGroup(select).label).toBe("New artifact");
    });

    it(`sets the current link type when there's a change in the select`, () => {
        const select = render();
        select.value = `${IS_CHILD_LINK_TYPE} ${FORWARD_DIRECTION}`;
        select.dispatchEvent(new Event("change"));

        expect(host.current_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
    });
});
