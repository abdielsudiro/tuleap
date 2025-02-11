<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <document-header />
    <div class="artidoc-container">
        <document-view class="artidoc-app-container" />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import DocumentHeader from "@/components/DocumentHeader.vue";
import useScrollToAnchor from "@/composables/useScrollToAnchor";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";

const item_id = strictInject(DOCUMENT_ID);
const store = strictInject(SECTIONS_STORE);

const configuration = strictInject(CONFIGURATION_STORE);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const { scrollToAnchor } = useScrollToAnchor();

onMounted(() => {
    store
        .loadSections(item_id, configuration.selected_tracker.value, can_user_edit_document)
        .then(() => {
            const hash = window.location.hash.slice(1);
            if (hash) {
                scrollToAnchor(hash);
            }
        });
});
</script>

<style lang="scss">
@use "@/themes/artidoc";
@use "@tuleap/prose-mirror-editor/style";

html {
    scroll-behavior: smooth;
}

.artidoc-container {
    height: 100%;
}
</style>

<style lang="scss" scoped>
.artidoc-app-container {
    height: inherit;
}
</style>
