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

export type GroupType = "unmoved" | "deleted" | "added";
export const UNMOVED_GROUP: GroupType = "unmoved";
export const DELETED_GROUP: GroupType = "deleted";
export const ADDED_GROUP: GroupType = "added";

export interface GroupOfLines {
    readonly type: GroupType;
    unidiff_offsets: number[];
    has_initial_comment_placeholder: boolean;
}

export interface UnidiffFileLine {
    readonly unidiff_offset: number;
    readonly content: string;
}

export interface UnMovedFileLine extends UnidiffFileLine {
    readonly old_offset: number;
    readonly new_offset: number;
}

export interface AddedFileLine extends UnidiffFileLine {
    readonly new_offset: number;
    readonly old_offset: null;
}

export interface RemovedFileLine extends UnidiffFileLine {
    readonly new_offset: null;
    readonly old_offset: number;
}

export type FileLine = UnMovedFileLine | AddedFileLine | RemovedFileLine;
export type LeftLine = UnMovedFileLine | RemovedFileLine;
export type RightLine = UnMovedFileLine | AddedFileLine;

export type FileDiffWidgetType =
    | "new-inline-comment"
    | "tuleap-pullrequest-comment"
    | "tuleap-pullrequest-placeholder";

interface WidgetElement extends HTMLElement {
    localName: FileDiffWidgetType;
}

export interface FileDiffCommentWidget extends WidgetElement {
    localName: "new-inline-comment" | "tuleap-pullrequest-comment";
}

export interface FileDiffPlaceholderWidget extends WidgetElement {
    localName: "tuleap-pullrequest-placeholder";
    isReplacingAComment: boolean;
    height: number;
}

export type FileDiffWidget = FileDiffCommentWidget | FileDiffPlaceholderWidget;
