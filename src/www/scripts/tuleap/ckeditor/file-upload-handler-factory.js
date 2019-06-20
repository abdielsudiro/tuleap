/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { post } from "tlp-fetch";
import { Upload } from "tus-js-client";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import { getGettextProvider } from "./gettext-factory.js";

export function buildFileUploadHandler(options) {
    const {
        ckeditor_instance,
        max_size_upload,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback
    } = options;

    return async function handler(event) {
        const loader = event.data.fileLoader;
        event.stop();

        if (loader.file.size > max_size_upload) {
            loader.message = sprintf(
                getGettextProvider().gettext("You are not allowed to upload files bigger than %s."),
                prettyKibibytes(max_size_upload)
            );
            loader.changeStatus("error");
            return;
        }
        const { id, upload_href, download_href } = await startUpload(loader, onErrorCallback);

        if (!upload_href) {
            onSuccess(loader, download_href);
            onSuccessCallback(id, download_href);
            return;
        }

        onStartCallback();
        await startUploader(loader, upload_href, download_href);
        ckeditor_instance.fire("change");
        onSuccessCallback(id, download_href);
    };
}

async function startUpload(loader, onErrorCallback) {
    try {
        const response = await post(loader.uploadUrl, {
            headers: { "content-type": "application/json" },
            body: JSON.stringify({
                name: loader.fileName,
                file_size: loader.file.size,
                file_type: loader.file.type
            })
        });

        return response.json();
    } catch (exception) {
        onErrorCallback();
        loader.message = getGettextProvider().gettext("Unable to upload the file");
        if (typeof exception.response === "undefined") {
            loader.changeStatus("error");
            throw exception;
        }

        try {
            const json = await exception.response.json();
            if (json.hasOwnProperty("error")) {
                loader.message = json.error.message;

                if (json.error.hasOwnProperty("i18n_error_message")) {
                    loader.message = json.error.i18n_error_message;
                }
            }
        } finally {
            loader.changeStatus("error");
        }
        throw exception;
    }
}

function startUploader(loader, upload_href, download_href) {
    return new Promise((resolve, reject) => {
        const uploader = new Upload(loader.file, {
            uploadUrl: upload_href,
            retryDelays: [0, 1000, 3000, 5000],
            metadata: {
                filename: loader.file.name,
                filetype: loader.file.type
            },
            onProgress: (bytes_sent, bytes_total) => {
                loader.uploadTotal = bytes_total;
                loader.uploaded = bytes_sent;
                loader.update();
            },
            onSuccess: () => {
                onSuccess(loader, download_href);
                return resolve();
            },
            onError: ({ originalRequest }) => {
                onError(loader, originalRequest);
                return reject();
            }
        });

        uploader.start();
    });
}

function onError(loader, originalRequest) {
    loader.message = loader.lang.filetools["httpError" + originalRequest.status];
    if (!loader.message) {
        loader.message = loader.lang.filetools.httpError.replace("%1", originalRequest.status);
    }
    loader.changeStatus("error");
}

function onSuccess(loader, download_href) {
    loader.responseData = {
        // ckeditor uploadImage widget inserts real size of the image as inline style
        // which causes strange rendering for big images in the artifact view once
        // the artifact is updated.
        // Using blank width & height inhibits this behavior.
        // See https://github.com/ckeditor/ckeditor-dev/blob/4.11.1/plugins/uploadimage/plugin.js#L84-L86
        width: " ",
        height: " "
    };
    loader.uploaded = 1;
    loader.fileName = loader.file.name;
    loader.url = download_href;
    loader.changeStatus("uploaded");
}
