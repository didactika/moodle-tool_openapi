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
 * Tests for ip_gate.
 *
 * Matches are tested against whatever getremoteaddr() actually resolves to
 * in this environment (there is no real HTTP request in a PHPUnit CLI run),
 * rather than a hardcoded address, so the test is not tied to one runner's
 * network setup.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\ip_gate
 */
final class ip_gate_test extends \advanced_testcase {
    /**
     * @param string $iprange
     * @param string|null $allowedfunctions
     * @param int $enabled
     */
    private function create_rule(string $iprange, ?string $allowedfunctions = null, int $enabled = 1): void {
        global $DB;

        $DB->insert_record('tool_openapi_ip_rules', (object) [
            'iprange' => $iprange,
            'description' => 'test rule',
            'allowedfunctions' => $allowedfunctions,
            'enabled' => $enabled,
            'timecreated' => time(),
        ]);
    }

    /**
     * No rules at all: this gate does not authorize.
     */
    public function test_no_rules_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->assertNull((new ip_gate())->authorize(null));
    }

    /**
     * A rule matching the current address, with no function restriction,
     * grants the full catalog.
     */
    public function test_matching_rule_with_no_restriction_grants_full_catalog(): void {
        $this->resetAfterTest();

        $remoteaddr = getremoteaddr('');
        if ($remoteaddr === '') {
            $this->markTestSkipped('No remote address available in this environment.');
        }

        $this->create_rule($remoteaddr);

        $scope = (new ip_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertTrue($scope->is_unrestricted());
    }

    /**
     * A matching rule with allowedfunctions set is limited to that list.
     */
    public function test_matching_rule_with_restriction_is_limited(): void {
        $this->resetAfterTest();

        $remoteaddr = getremoteaddr('');
        if ($remoteaddr === '') {
            $this->markTestSkipped('No remote address available in this environment.');
        }

        $this->create_rule($remoteaddr, "core_course_get_courses\n");

        $scope = (new ip_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * A disabled rule is ignored even though its range matches.
     */
    public function test_disabled_matching_rule_does_not_authorize(): void {
        $this->resetAfterTest();

        $remoteaddr = getremoteaddr('');
        if ($remoteaddr === '') {
            $this->markTestSkipped('No remote address available in this environment.');
        }

        $this->create_rule($remoteaddr, null, 0);

        $this->assertNull((new ip_gate())->authorize(null));
    }

    /**
     * A rule for a different, reserved (RFC 5737) address never matches
     * whatever this environment's own address happens to be.
     */
    public function test_non_matching_rule_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->create_rule('203.0.113.1');

        $this->assertNull((new ip_gate())->authorize(null));
    }
}
