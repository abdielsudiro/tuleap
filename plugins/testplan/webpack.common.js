/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

const path = require("path");
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");
const { VueLoaderPlugin } = require("vue-loader");
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/testplan/"),
    "/assets/testplan/"
);

const entry = {
    testplan: "./scripts/test-plan/index.ts",
    "testplan-style": "./themes/testplan.scss",
};

module.exports = [
    {
        entry,
        context,
        output,
        resolve: {
            extensions: [".js", ".ts", ".vue"],
            alias: {
                docx: path.resolve(__dirname, "node_modules", "docx"),
                vue: path.resolve(__dirname, "node_modules", "@vue", "compat"),
            },
        },
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_easygettext_loader,
                {
                    test: /\.vue$/,
                    exclude: /node_modules/,
                    loader: "vue-loader",
                    options: {
                        compilerOptions: {
                            compatConfig: {
                                MODE: 2,
                            },
                        },
                    },
                },
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            new VueLoaderPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
