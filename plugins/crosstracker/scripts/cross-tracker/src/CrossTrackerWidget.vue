<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
  -->

<template>
    <section
        class="tlp-pane-section"
        v-bind:class="{ 'reading-mode-shown': is_reading_mode_shown }"
    >
        <error-message v-bind:fault="current_fault" />
        <error-inactive-project-message v-bind:invalid_trackers="invalid_trackers" />
        <div
            class="tlp-alert-success cross-tracker-report-success"
            v-if="current_success.isValue()"
            data-test="cross-tracker-report-success"
        >
            {{ current_success.unwrapOr("") }}
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown"
            v-bind:backend_cross_tracker_report="backend_cross_tracker_report"
            v-bind:reading_cross_tracker_report="reading_cross_tracker_report"
            v-bind:has_error="has_error"
            v-on:switch-to-writing-mode="handleSwitchWriting"
            v-on:saved="reportSaved"
            v-on:discard-unsaved-report="unsavedReportDiscarded"
        />
        <writing-mode
            v-if="report_state === 'edit-query'"
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
            v-on:switch-to-reading-mode="handleSwitchReading"
        />
    </section>
    <section class="tlp-pane-section" v-if="!is_loading">
        <artifact-table
            v-if="!writing_cross_tracker_report.expert_mode"
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
        />
        <selectable-table
            v-if="writing_cross_tracker_report.expert_mode"
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
        />
    </section>
</template>
<script setup lang="ts">
import { computed, onMounted, provide, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import ArtifactTable from "./components/table/ArtifactTable.vue";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import type { SaveEvent } from "./writing-mode/WritingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import ErrorMessage from "./components/ErrorMessage.vue";
import ErrorInactiveProjectMessage from "./components/ErrorInactiveProjectMessage.vue";
import { getReport } from "./api/rest-querier";
import type WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import type BackendCrossTrackerReport from "./backend-cross-tracker-report";
import type ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import type { InvalidTracker, Report } from "./type";
import SelectableTable from "./components/selectable-table/SelectableTable.vue";
import type { ReportState } from "./domain/ReportState";
import {
    CLEAR_FEEDBACKS,
    IS_CSV_EXPORT_ALLOWED,
    IS_USER_ADMIN,
    NOTIFY_FAULT,
    REPORT_ID,
    REPORT_STATE,
} from "./injection-symbols";
import { useFeedbacks } from "./composables/useFeedbacks";
import { ReportRetrievalFault } from "./domain/ReportRetrievalFault";

const report_id = strictInject(REPORT_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);

const gettext_provider = useGettext();

const props = defineProps<{
    backend_cross_tracker_report: BackendCrossTrackerReport;
    reading_cross_tracker_report: ReadingCrossTrackerReport;
    writing_cross_tracker_report: WritingCrossTrackerReport;
}>();

const report_state = ref<ReportState>("report-saved");
provide(REPORT_STATE, report_state);
const is_loading = ref(true);
const invalid_trackers = ref<ReadonlyArray<InvalidTracker>>([]);

const is_reading_mode_shown = computed(
    () =>
        (report_state.value === "report-saved" || report_state.value === "result-preview") &&
        !is_loading.value,
);

const { current_fault, current_success, notifyFault, notifySuccess, clearFeedbacks } =
    useFeedbacks();
provide(NOTIFY_FAULT, notifyFault);
provide(CLEAR_FEEDBACKS, clearFeedbacks);
const has_error = computed<boolean>(() => current_fault.value.isValue());

const is_csv_export_allowed = computed<boolean>(() => {
    if (report_state.value !== "report-saved" || has_error.value === true) {
        return false;
    }
    if (!is_user_admin) {
        return true;
    }
    return invalid_trackers.value.length === 0;
});
provide(IS_CSV_EXPORT_ALLOWED, is_csv_export_allowed);

function initReports(): void {
    props.reading_cross_tracker_report.duplicateFromReport(props.backend_cross_tracker_report);
    props.writing_cross_tracker_report.duplicateFromReport(props.reading_cross_tracker_report);
}

function loadBackendReport(): void {
    is_loading.value = true;
    getReport(report_id)
        .match(
            (report: Report) => {
                props.backend_cross_tracker_report.init(
                    report.trackers,
                    report.expert_query,
                    report.expert_mode,
                );
                initReports();
                if (!report.expert_mode && report.invalid_trackers.length > 0) {
                    invalid_trackers.value = report.invalid_trackers;
                }
            },
            (fault) => {
                notifyFault(ReportRetrievalFault(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

onMounted(() => {
    loadBackendReport();
});

function handleSwitchWriting(): void {
    if (!is_user_admin) {
        return;
    }

    props.writing_cross_tracker_report.duplicateFromReport(props.reading_cross_tracker_report);
    report_state.value = "edit-query";
    clearFeedbacks();
}

function handleSwitchReading(event: SaveEvent): void {
    if (event.saved_state) {
        props.writing_cross_tracker_report.duplicateFromReport(props.reading_cross_tracker_report);
        report_state.value = "report-saved";
    } else {
        props.reading_cross_tracker_report.duplicateFromWritingReport(
            props.writing_cross_tracker_report,
        );
        report_state.value = "result-preview";
    }
    clearFeedbacks();
}

function reportSaved(): void {
    initReports();
    invalid_trackers.value = [];
    report_state.value = "report-saved";
    clearFeedbacks();
    notifySuccess(gettext_provider.$gettext("Report has been successfully saved"));
}

function unsavedReportDiscarded(): void {
    props.reading_cross_tracker_report.duplicateFromReport(props.backend_cross_tracker_report);
    report_state.value = "report-saved";
    clearFeedbacks();
}

defineExpose({
    report_state,
    current_fault,
    current_success,
    is_csv_export_allowed,
});
</script>

<style lang="scss" scoped>
.reading-mode-shown {
    border: 0;
}
</style>
