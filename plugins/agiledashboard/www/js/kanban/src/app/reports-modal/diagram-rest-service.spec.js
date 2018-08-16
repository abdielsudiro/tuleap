import reports_module from './reports-modal.spec.js';
import kanban_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe("DiagramRestService -", () => {
    let DiagramRestService,
        SharedPropertiesService,
        FilterTrackerReportService,
        $httpBackend;

    beforeEach(() => {
        angular.mock.module(reports_module);
        angular.mock.module(kanban_module);

        angular.mock.inject(function(
            _$httpBackend_,
            _SharedPropertiesService_,
            _FilterTrackerReportService_,
            _DiagramRestService_,
        ) {
            $httpBackend               = _$httpBackend_;
            SharedPropertiesService    = _SharedPropertiesService_;
            FilterTrackerReportService = _FilterTrackerReportService_;
            DiagramRestService         = _DiagramRestService_;
        });

        spyOn(SharedPropertiesService, "getUUID");

        installPromiseMatchers();
    });

    afterEach(() => {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getCumulativeFlowDiagram() -", () => {
        let UUID;
        beforeEach(() => {
            UUID = '20f15d7f-f8ae-465a-aa59-cc9d77e5e372';
            SharedPropertiesService.getUUID.and.returnValue(UUID);
            spyOn(FilterTrackerReportService, "getSelectedFilterTrackerReportId");
        });

        it("Given a kanban id, a start date, an end date and an interval between points, when I get the cumulative flow diagram for this period, then a GET request will be made with the correct parameters and a promise will be resolved with the data", () => {
            const kanban_id               = 6;
            const start_date              = '2010-09-06';
            const end_date                = '2016-11-26';
            const interval_between_points = 73;

            const cumulative_flow_data = {
                columns: [
                    { id: 37 },
                    { id: 89 }
                ]
            };

            const expected_url = `/api/v1/kanban/${kanban_id}/cumulative_flow?end_date=${end_date}&interval_between_point=${interval_between_points}&start_date=${start_date}`;

            $httpBackend.expectGET(expected_url, {
                Accept         : 'application/json, text/plain, */*',
                'X-Client-UUID': UUID,
            }).respond(cumulative_flow_data);

            const promise = DiagramRestService.getCumulativeFlowDiagram(
                kanban_id,
                start_date,
                end_date,
                interval_between_points
            );
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(cumulative_flow_data);
        });

        it("Given that a filtering Tracker report has been selected, then it will be passed as a param to the REST route", () => {
            FilterTrackerReportService.getSelectedFilterTrackerReportId.and.returnValue(547);
            const kanban_id               = 7;
            const start_date              = '2010-08-19';
            const end_date                = '2025-10-12';
            const interval_between_points = 48;

            const cumulative_flow_data = {
                columns: [
                    { id: 37 },
                    { id: 89 }
                ]
            };

            const expected_query = encodeURI(JSON.stringify({ tracker_report_id: 547 }));
            const expected_url = `/api/v1/kanban/${kanban_id}/cumulative_flow?end_date=${end_date}&interval_between_point=${interval_between_points}&query=${expected_query}&start_date=${start_date}`;

            $httpBackend.expectGET(expected_url, {
                Accept         : 'application/json, text/plain, */*',
                'X-Client-UUID': UUID,
            }).respond(cumulative_flow_data);

            const promise = DiagramRestService.getCumulativeFlowDiagram(
                kanban_id,
                start_date,
                end_date,
                interval_between_points
            );
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(cumulative_flow_data);
        });
    });
});
