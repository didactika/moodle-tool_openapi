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

namespace tool_openapi\local;

/**
 * Tests for service_functions.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\local\service_functions
 */
final class service_functions_test extends \advanced_testcase {
    /**
     * Insert a bare external service and assign it a set of functions.
     *
     * @param string $shortname
     * @param string[] $functionnames
     * @return int
     */
    private function create_service(string $shortname, array $functionnames): int {
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

        return $serviceid;
    }

    /**
     * for_shortname resolves the shortname and returns its functions.
     */
    public function test_for_shortname_returns_the_assigned_functions(): void {
        $this->resetAfterTest();

        $this->create_service('svc_a', ['core_webservice_get_site_info']);

        $this->assertSame(
            ['core_webservice_get_site_info'],
            service_functions::for_shortname('svc_a')
        );
    }

    /**
     * An unknown shortname is rejected, not silently treated as empty.
     */
    public function test_for_shortname_rejects_an_unknown_service(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        service_functions::for_shortname('does-not-exist');
    }

    /**
     * for_id looks the same functions up directly, without a shortname.
     */
    public function test_for_id_returns_the_assigned_functions(): void {
        $this->resetAfterTest();

        $serviceid = $this->create_service('svc_b', ['core_course_get_courses']);

        $this->assertSame(['core_course_get_courses'], service_functions::for_id($serviceid));
    }

    /**
     * A service with no assigned functions returns an empty list, not an error.
     */
    public function test_for_id_with_no_functions_returns_empty(): void {
        $this->resetAfterTest();

        $serviceid = $this->create_service('svc_c', []);

        $this->assertSame([], service_functions::for_id($serviceid));
    }
}
