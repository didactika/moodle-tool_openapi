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
 * Tests for wstoken_gate.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\wstoken_gate
 */
final class wstoken_gate_test extends \advanced_testcase {
    /**
     * Cleans up the simulated request param after each test.
     */
    protected function tearDown(): void {
        unset($_GET['wstoken']);
        parent::tearDown();
    }

    /**
     * @param int $enabled
     * @return int The service id.
     */
    private function create_service(int $enabled = 1): int {
        global $DB;

        $serviceid = $DB->insert_record('external_services', (object) [
            'name' => 'svc',
            'shortname' => 'svc',
            'enabled' => $enabled,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'timecreated' => time(),
        ]);
        $DB->insert_record('external_services_functions', (object) [
            'externalserviceid' => $serviceid,
            'functionname' => 'core_course_get_courses',
        ]);

        return $serviceid;
    }

    /**
     * @param int $serviceid
     * @param string $token
     * @param int|null $validuntil
     */
    private function create_token(int $serviceid, string $token, ?int $validuntil = null): void {
        global $DB;

        $DB->insert_record('external_tokens', (object) [
            'token' => $token,
            'tokentype' => 0,
            'userid' => get_admin()->id,
            'externalserviceid' => $serviceid,
            'contextid' => \context_system::instance()->id,
            'creatorid' => get_admin()->id,
            'validuntil' => $validuntil,
            'timecreated' => time(),
        ]);
    }

    /**
     * No wstoken param at all: this gate does not authorize.
     */
    public function test_no_token_param_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A well-formed but unknown token does not authorize.
     */
    public function test_unknown_token_does_not_authorize(): void {
        $this->resetAfterTest();
        $_GET['wstoken'] = 'doesnotexist123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A valid token grants exactly its service's own functions.
     */
    public function test_valid_token_is_limited_to_its_service(): void {
        $this->resetAfterTest();
        $serviceid = $this->create_service();
        $this->create_token($serviceid, 'validtoken123');
        $_GET['wstoken'] = 'validtoken123';

        $scope = (new wstoken_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * An expired token does not authorize.
     */
    public function test_expired_token_does_not_authorize(): void {
        $this->resetAfterTest();
        $serviceid = $this->create_service();
        $this->create_token($serviceid, 'expiredtoken123', time() - 3600);
        $_GET['wstoken'] = 'expiredtoken123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A token for a disabled service does not authorize.
     */
    public function test_token_for_disabled_service_does_not_authorize(): void {
        $this->resetAfterTest();
        $serviceid = $this->create_service(0);
        $this->create_token($serviceid, 'validtoken123');
        $_GET['wstoken'] = 'validtoken123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }
}
