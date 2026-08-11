<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_openapi\generator;

/**
 * Tests for service_filter.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\service_filter
 */
final class service_filter_test extends \advanced_testcase {
    /**
     * Insert a bare external service and assign it a set of functions.
     *
     * @param string $shortname
     * @param string[] $functionnames
     */
    private function create_service(string $shortname, array $functionnames): void {
        global $DB;

        $serviceid = $DB->insert_record('external_services', (object) [
            'name' => $shortname,
            'shortname' => $shortname,
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'timecreated' => time(),
        ]);

        foreach ($functionnames as $functionname) {
            $DB->insert_record('external_services_functions', (object) [
                'externalserviceid' => $serviceid,
                'functionname' => $functionname,
            ]);
        }
    }

    /**
     * Only the assigned function's path survives the filter.
     */
    public function test_filter_keeps_only_the_services_functions(): void {
        $this->resetAfterTest();

        $this->create_service('tool_openapi_test_service', ['core_webservice_get_site_info']);

        $doc = document_builder::build();
        $filtered = service_filter::filter($doc, 'tool_openapi_test_service');

        $this->assertSame(['/core_webservice_get_site_info'], array_keys($filtered['paths']));
        $this->assertLessThan(count($doc['paths']), count($filtered['paths']));
    }

    /**
     * A service with no assigned functions yields an empty catalog.
     */
    public function test_filter_with_no_functions_yields_no_paths(): void {
        $this->resetAfterTest();

        $this->create_service('tool_openapi_empty_service', []);

        $filtered = service_filter::filter(document_builder::build(), 'tool_openapi_empty_service');

        $this->assertSame([], $filtered['paths']);
    }

    /**
     * openapi/info/components are untouched, only paths is trimmed.
     */
    public function test_filter_preserves_the_rest_of_the_envelope(): void {
        $this->resetAfterTest();

        $this->create_service('tool_openapi_test_service', ['core_webservice_get_site_info']);

        $doc = document_builder::build();
        $filtered = service_filter::filter($doc, 'tool_openapi_test_service');

        $this->assertSame($doc['openapi'], $filtered['openapi']);
        $this->assertSame($doc['info'], $filtered['info']);
        $this->assertSame($doc['components'], $filtered['components']);
    }

    /**
     * An unknown service shortname is rejected, not silently ignored.
     */
    public function test_filter_rejects_an_unknown_service(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        service_filter::filter(document_builder::build(), 'does-not-exist');
    }
}
