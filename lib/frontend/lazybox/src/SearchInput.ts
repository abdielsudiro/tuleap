/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { define, dispatch, html } from "hybrids";
import type { LazyboxSearchFieldCallback } from "./type";
import { isBackspaceKey, isEnterKey } from "./helpers/keys-helper";

export type SearchInput = {
    disabled: boolean;
    placeholder: string;
    search_callback: LazyboxSearchFieldCallback;
    clear(): void;
    setFocus(): void;
};
export type HostElement = HTMLElement & InternalSearchInput;
export type InternalSearchInput = Readonly<SearchInput> & {
    query: string;
    timeout_id: number | undefined;
    content(): HTMLElement;
};
const TRIGGER_CALLBACK_DELAY_IN_MS = 250;

export const onInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    dispatch(host, "search-input");
    const query = event.target.value;

    // setTimeout + clearTimeout is a trick to "debounce":
    // we call the callback only once after the delay and _not_ for each input
    clearTimeout(host.timeout_id);

    if (query === "") {
        // The query has been cleared, no need to wait
        host.search_callback(query);
    }

    host.timeout_id = window.setTimeout(() => {
        host.search_callback(query);
    }, TRIGGER_CALLBACK_DELAY_IN_MS);
};

const preventEnterFromSubmittingParentForms = (event: KeyboardEvent): void => {
    event.preventDefault();
};

const hasBackspaceBeenPressedWhileQueryWasAlreadyEmpty = (
    host: InternalSearchInput,
    event: KeyboardEvent
): boolean => isBackspaceKey(event) && host.query === "";

export const onKeyUp = (host: HostElement, event: KeyboardEvent): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    if (isEnterKey(event)) {
        preventEnterFromSubmittingParentForms(event);
        return;
    }
    if (hasBackspaceBeenPressedWhileQueryWasAlreadyEmpty(host, event)) {
        dispatch(host, "backspace-pressed");
    }
    // Assign host.query after everything to be able to detect when backspace has been pressed
    // while the query was already empty. Otherwise, it deletes an element when we just wanted
    // to remove the last character of the query.
    host.query = event.target.value;
};

export const buildClear = (host: InternalSearchInput): (() => void) => {
    return () => {
        host.query = "";
        host.search_callback(host.query);
    };
};

export const buildFocus = (host: InternalSearchInput): (() => void) => {
    return () => {
        host.content().querySelector("input")?.focus();
    };
};

export const TAG = "tuleap-lazybox-search";
export const SearchInput = define<InternalSearchInput>({
    tag: TAG,
    disabled: false,
    placeholder: "",
    query: "",
    clear: {
        get: buildClear,
    },
    setFocus: {
        get: buildFocus,
    },
    search_callback: undefined,
    timeout_id: undefined,
    content: (host) => html`<input
        type="search"
        disabled="${host.disabled}"
        data-test="lazybox-search-field"
        class="lazybox-search-field"
        tabindex="0"
        autocomplete="off"
        autocorrect="off"
        autocapitalize="none"
        spellcheck="false"
        role="searchbox"
        aria-autocomplete="list"
        aria-controls="lazybox-dropdown-value"
        placeholder="${host.placeholder}"
        value="${host.query}"
        oninput="${onInput}"
        onkeyup="${onKeyUp}"
    />`,
});
