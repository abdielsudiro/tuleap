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
    <section v-bind:data-test="is_section_in_edit_mode ? 'section-edition' : undefined">
        <div class="artidoc-dropdown-container">
            <section-dropdown
                v-bind:editor="editor"
                v-bind:section="section"
                v-if="!is_sections_loading"
            />
        </div>

        <article
            class="document-section"
            v-bind:class="{
                'document-section-is-being-saved': isBeingSaved(),
                'document-section-is-just-saved': isJustSaved(),
                'document-section-is-just-refreshed': isJustRefreshed(),
                'document-section-is-in-error': is_in_error,
                'document-section-is-outdated': is_outdated,
            }"
        >
            <section-header
                class="section-header"
                v-if="!is_sections_loading"
                v-bind:title="editable_title"
                v-bind:input_current_title="inputCurrentTitle"
                v-bind:is_edit_mode="is_section_in_edit_mode"
            />
            <section-header-skeleton v-else class="section-header" />
            <section-description
                v-bind:editable_description="editable_description"
                v-bind:readonly_description="getReadonlyDescription()"
                v-bind:input_current_description="inputCurrentDescription"
                v-bind:is_edit_mode="is_section_in_edit_mode"
                v-bind:add_attachment_to_waiting_list="addAttachmentToWaitingList"
                v-bind:upload_url="upload_url"
                v-bind:is_image_upload_allowed="is_image_upload_allowed"
                v-bind:upload_file="upload_file"
            />
            <section-footer v-bind:editor="editor" v-bind:section="section" />
        </article>
    </section>
</template>

<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import SectionHeader from "./header/SectionHeader.vue";
import SectionDescription from "./description/SectionDescription.vue";
import { useSectionEditor } from "@/composables/useSectionEditor";
import SectionDropdown from "./header/SectionDropdown.vue";
import SectionHeaderSkeleton from "./header/SectionHeaderSkeleton.vue";
import SectionFooter from "./footer/SectionFooter.vue";
import { useAttachmentFile } from "@/composables/useAttachmentFile";
import { ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import type { UseUploadFileType } from "@/composables/useUploadFile";
import { useUploadFile } from "@/composables/useUploadFile";

const props = defineProps<{ section: ArtidocSection }>();

const { is_sections_loading } = strictInject(SECTIONS_STORE);

const {
    upload_url,
    addAttachmentToWaitingList,
    mergeArtifactAttachments,
    setWaitingListAttachments,
} = useAttachmentFile(ref(props.section.attachments ? props.section.attachments.field_id : 0));

const upload_file: UseUploadFileType = useUploadFile(upload_url, addAttachmentToWaitingList);

const editor = useSectionEditor(
    props.section,
    mergeArtifactAttachments,
    setWaitingListAttachments,
    upload_file.is_in_progress,
);

const {
    is_section_in_edit_mode,
    isJustRefreshed,
    isJustSaved,
    isBeingSaved,
    is_image_upload_allowed,
} = editor.editor_state;
const { is_in_error, is_outdated } = editor.editor_error;

const {
    inputCurrentDescription,
    inputCurrentTitle,
    editable_title,
    editable_description,
    getReadonlyDescription,
} = editor.editor_section_content;
</script>

<style lang="scss" scoped>
@use "@tuleap/burningparrot-theme/css/includes/global-variables";
@use "@/themes/includes/zindex";
@use "@/themes/includes/whitespace";

section {
    display: grid;
    grid-template-columns: auto whitespace.$section-right-padding;
}

.artidoc-dropdown-container {
    display: flex;
    z-index: zindex.$dropdown;
    justify-content: center;
    order: 1;

    @media print {
        display: none;
    }
}

.document-section {
    display: flex;
    flex-direction: column;
}

.section-header {
    position: sticky;
    z-index: zindex.$header;
    top: global-variables.$navbar-height;
    margin-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    background: var(--tuleap-artidoc-section-background);
}
</style>
