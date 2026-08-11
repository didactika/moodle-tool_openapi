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

namespace tool_openapi\access;

/**
 * Tests for session_gate.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\session_gate
 */
final class session_gate_test extends \advanced_testcase {
    /**
     * No session at all: this gate does not authorize.
     */
    public function test_no_session_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->assertNull((new session_gate())->authorize(null));
    }

    /**
     * The guest account is not a real session for this purpose.
     */
    public function test_guest_session_does_not_authorize(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setGuestUser();

        $this->assertNull((new session_gate())->authorize(null));

        unset($USER);
    }

    /**
     * A session with tool/openapi:viewfullcatalog gets the full catalog,
     * regardless of whether ?service= was given.
     */
    public function test_viewfullcatalog_grants_the_full_catalog(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/openapi:viewfullcatalog', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        accesslib_clear_all_caches_for_unit_testing();

        $scope = (new session_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertTrue($scope->is_unrestricted());
    }

    /**
     * A session with only tool/openapi:view and no ?service= gets nothing.
     */
    public function test_view_only_without_a_service_does_not_authorize(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/openapi:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertNull((new session_gate())->authorize(null));
    }

    /**
     * A session with only tool/openapi:view and a real ?service= gets that
     * service's functions, not the full catalog.
     */
    public function test_view_only_with_a_known_service_is_limited_to_it(): void {
        global $DB;

        $this->resetAfterTest();

        $serviceid = $DB->insert_record('external_services', (object) [
            'name' => 'svc',
            'shortname' => 'svc',
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'timecreated' => time(),
        ]);
        $DB->insert_record('external_services_functions', (object) [
            'externalserviceid' => $serviceid,
            'functionname' => 'core_course_get_courses',
        ]);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/openapi:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        accesslib_clear_all_caches_for_unit_testing();

        $scope = (new session_gate())->authorize('svc');

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * An unknown ?service= is rejected, not left to throw.
     */
    public function test_view_only_with_an_unknown_service_does_not_authorize(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/openapi:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertNull((new session_gate())->authorize('does-not-exist'));
    }
}
