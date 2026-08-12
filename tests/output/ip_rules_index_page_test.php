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

use tool_openapi\output\ip_rules\index_page;

/**
 * Tests for the IP rule list renderable.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\output\ip_rules\index_page
 */
final class ip_rules_index_page_test extends \advanced_testcase {
    /**
     * An empty list flags itself as empty so the template can swap the
     * table for the empty state, and still offers the way back.
     */
    public function test_no_rules_sets_the_empty_flag(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new index_page([]))->export_for_template($PAGE->get_renderer('core'));

        $this->assertFalse($data['hasrules']);
        $this->assertSame([], $data['rows']);
        $this->assertStringContainsString('access_control/index.php', $data['backurl']);
    }

    /**
     * A rule row reports its scope and status in words, and offers both
     * actions.
     */
    public function test_rule_row(): void {
        global $PAGE;

        $this->resetAfterTest();

        $rule = (object) [
            'id' => 3,
            'iprange' => '192.0.2.0/24',
            'description' => 'Office',
            'allowedfunctions' => "core_course_get_courses\n",
            'enabled' => 0,
        ];

        $data = (new index_page([$rule]))->export_for_template($PAGE->get_renderer('core'));
        $row = $data['rows'][0];

        $this->assertTrue($data['hasrules']);
        $this->assertSame('192.0.2.0/24', $row['iprange']);
        $this->assertSame('core_course_get_courses', $row['scope']);
        $this->assertSame(get_string('disabled', 'tool_openapi'), $row['status']);
        $this->assertStringContainsString('edit.php', $row['actions']);
        $this->assertStringContainsString('delete.php', $row['actions']);
    }

    /**
     * A rule with no function list covers the whole catalog, and says so
     * rather than leaving the cell blank.
     */
    public function test_unrestricted_rule_reports_the_full_catalog(): void {
        global $PAGE;

        $this->resetAfterTest();

        $rule = (object) [
            'id' => 4,
            'iprange' => '198.51.100.7',
            'description' => null,
            'allowedfunctions' => null,
            'enabled' => 1,
        ];

        $row = (new index_page([$rule]))->export_for_template($PAGE->get_renderer('core'))['rows'][0];

        $this->assertSame(get_string('fullcatalog', 'tool_openapi'), $row['scope']);
        $this->assertSame(get_string('enabled', 'tool_openapi'), $row['status']);
    }
}
