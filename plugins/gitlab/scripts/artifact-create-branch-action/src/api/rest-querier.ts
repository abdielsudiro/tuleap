/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { FetchWrapperError, get, post } from "@tuleap/tlp-fetch";
import { ResultAsync } from "neverthrow";

interface GitLabBranchCreationError {
    readonly error_message?: unknown;
    readonly i18n_error_message?: string;
}

export function postGitlabBranch(
    gitlab_integration_id: number,
    artifact_id: number,
    reference: string
): ResultAsync<GitLabIntegrationCreatedBranchInformation, Promise<GitLabBranchCreationError>> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        gitlab_integration_id: gitlab_integration_id,
        artifact_id: artifact_id,
        reference: reference,
    });

    return ResultAsync.fromPromise(
        post("/api/v1/gitlab_branch", {
            headers,
            body,
        }).then((response) => response.json()),
        (err: unknown): Promise<GitLabBranchCreationError> => {
            const default_error: GitLabBranchCreationError = {
                error_message: err,
            };

            if (!(err instanceof FetchWrapperError)) {
                return Promise.resolve(default_error);
            }

            return ResultAsync.fromPromise(err.response.json(), () => default_error).match(
                (response_json) => {
                    if (
                        Object.prototype.hasOwnProperty.call(
                            response_json.error,
                            "i18n_error_message"
                        )
                    ) {
                        return { i18n_error_message: response_json.error.i18n_error_message };
                    }

                    return { error_message: response_json.error };
                },
                () => default_error
            );
        }
    );
}

export function postGitlabMergeRequest(
    gitlab_integration_id: number,
    artifact_id: number,
    source_branch: string
): ResultAsync<void, unknown> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        gitlab_integration_id: gitlab_integration_id,
        artifact_id: artifact_id,
        source_branch: source_branch,
    });

    return ResultAsync.fromPromise(
        post("/api/v1/gitlab_merge_request", {
            headers,
            body,
        }).then(() => {
            // ignore response
        }),
        (err: unknown) => err
    );
}

export interface GitLabIntegrationBranchInformation {
    readonly default_branch: string;
}

export interface GitLabIntegrationCreatedBranchInformation {
    readonly branch_name: string;
}

export function getGitLabRepositoryBranchInformation(
    gitlab_integration_id: number
): ResultAsync<GitLabIntegrationBranchInformation, unknown> {
    return ResultAsync.fromPromise(
        (async (): Promise<GitLabIntegrationBranchInformation> => {
            const response = await get(
                `/api/v1/gitlab_repositories/${encodeURIComponent(gitlab_integration_id)}/branches`
            );
            return response.json();
        })(),
        (err: unknown) => err
    );
}
