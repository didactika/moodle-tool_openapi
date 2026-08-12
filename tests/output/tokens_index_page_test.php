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

namespace tool_openapi\output;

use tool_openapi\output\tokens\index_page;

/**
 * Tests for the token list renderable.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\output\tokens\index_page
 */
final class tokens_index_page_test extends \advanced_testcase {
    /**
     * An empty list flags itself as empty so the template can swap the
     * table for the empty state.
     */
    public function test_no_tokens_sets_the_empty_flag(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new index_page([]))->export_for_template($PAGE->get_renderer('core'));

        $this->assertFalse($data['hastokens']);
        $this->assertSame([], $data['rows']);
    }

    /**
     * A token with no restrictions reports the full catalog and no IP
     * restriction, rather than blank cells.
     */
    public function test_unrestricted_token_row(): void {
        global $PAGE;

        $this->resetAfterTest();

        $token = (object) [
            'id' => 7,
            'name' => 'Integration',
            'allowedfunctions' => null,
            'iprestriction' => null,
            'timecreated' => 1767225600,
            'lastused' => null,
        ];

        $data = (new index_page([$token]))->export_for_template($PAGE->get_renderer('core'));
        $row = $data['rows'][0];

        $this->assertTrue($data['hastokens']);
        $this->assertSame('Integration', $row['name']);
        $this->assertSame(get_string('fullcatalog', 'tool_openapi'), $row['scope']);
        $this->assertSame(get_string('noiprestriction', 'tool_openapi'), $row['iprestriction']);
        $this->assertSame(get_string('never', 'tool_openapi'), $row['lastused']);
        $this->assertStringContainsString('delete.php', $row['deleteicon']);
    }

    /**
     * A restricted token lists its functions comma-separated, from the
     * newline-separated field.
     */
    public function test_restricted_token_row_lists_its_functions(): void {
        global $PAGE;

        $this->resetAfterTest();

        $token = (object) [
            'id' => 8,
            'name' => 'Limited',
            'allowedfunctions' => "core_course_get_courses\ncore_user_get_users\n",
            'iprestriction' => '192.0.2.0/24',
            'timecreated' => 1767225600,
            'lastused' => 1767312000,
        ];

        $row = (new index_page([$token]))->export_for_template($PAGE->get_renderer('core'))['rows'][0];

        $this->assertSame('core_course_get_courses, core_user_get_users', $row['scope']);
        $this->assertSame('192.0.2.0/24', $row['iprestriction']);
    }
}
