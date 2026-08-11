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
 * Tests for token_gate.
 *
 * PHP's CLI SAPI has no apache_request_headers(), so token_gate always
 * falls back to $_SERVER['HTTP_AUTHORIZATION'] under PHPUnit -- setting
 * that directly is how these tests simulate the header.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\token_gate
 */
final class token_gate_test extends \advanced_testcase {
    /**
     * Cleans up the simulated header after each test.
     */
    protected function tearDown(): void {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }

    /**
     * Inserts a tool_openapi_tokens row for the given plaintext token.
     *
     * @param string $plaintext
     * @param string|null $allowedfunctions
     * @param int $revoked
     * @return int
     */
    private function create_token(string $plaintext, ?string $allowedfunctions = null, int $revoked = 0): int {
        global $DB;

        return $DB->insert_record('tool_openapi_tokens', (object) [
            'name' => 'test token',
            'tokenhash' => hash('sha256', $plaintext),
            'allowedfunctions' => $allowedfunctions,
            'createdby' => get_admin()->id,
            'timecreated' => time(),
            'lastused' => null,
            'revoked' => $revoked,
        ]);
    }

    /**
     * No Authorization header at all: this gate does not authorize.
     */
    public function test_no_header_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->assertNull((new token_gate())->authorize(null));
    }

    /**
     * A header that is not a Bearer token is ignored.
     */
    public function test_non_bearer_header_does_not_authorize(): void {
        $this->resetAfterTest();
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';

        $this->assertNull((new token_gate())->authorize(null));
    }

    /**
     * A well-formed but unknown token does not authorize.
     */
    public function test_unknown_token_does_not_authorize(): void {
        $this->resetAfterTest();
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer does-not-exist';

        $this->assertNull((new token_gate())->authorize(null));
    }

    /**
     * A known, non-revoked token with no restriction grants the full
     * catalog, and updates lastused.
     */
    public function test_valid_token_grants_full_catalog_and_updates_lastused(): void {
        global $DB;

        $this->resetAfterTest();
        $id = $this->create_token('sometoken123');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer sometoken123';

        $scope = (new token_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertTrue($scope->is_unrestricted());
        $this->assertNotNull($DB->get_field('tool_openapi_tokens', 'lastused', ['id' => $id]));
    }

    /**
     * A token with allowedfunctions set is limited to that list.
     */
    public function test_valid_token_with_restriction_is_limited(): void {
        $this->resetAfterTest();
        $this->create_token('sometoken123', "core_course_get_courses\n");
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer sometoken123';

        $scope = (new token_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * A revoked token does not authorize, even with the right value.
     */
    public function test_revoked_token_does_not_authorize(): void {
        $this->resetAfterTest();
        $this->create_token('sometoken123', null, 1);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer sometoken123';

        $this->assertNull((new token_gate())->authorize(null));
    }
}
