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

use tool_openapi\output\access_control\page;

/**
 * Tests for the access control renderable.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\output\access_control\page
 */
final class access_control_page_test extends \advanced_testcase {
    /**
     * All 4 gates are listed, in the order access_checker tries them.
     */
    public function test_lists_every_gate(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertCount(4, $data['rows']);
        $this->assertSame(get_string('gatesession', 'tool_openapi'), $data['rows'][0]['name']);
        $this->assertSame(get_string('gateip', 'tool_openapi'), $data['rows'][1]['name']);
        $this->assertSame(get_string('gatetoken', 'tool_openapi'), $data['rows'][2]['name']);
        $this->assertSame(get_string('gatewstoken', 'tool_openapi'), $data['rows'][3]['name']);
    }

    /**
     * Only the methods with something of their own to configure get a cog.
     * The session gate is decided by capabilities, managed from Moodle's own
     * roles pages, so it deliberately has none.
     */
    public function test_only_configurable_gates_get_a_cog(): void {
        global $PAGE;

        $this->resetAfterTest();

        $rows = (new page())->export_for_template($PAGE->get_renderer('core'))['rows'];

        $this->assertSame('', $rows[0]['configicon']);
        $this->assertStringContainsString('ip_rules', $rows[1]['configicon']);
        $this->assertStringContainsString('tool/openapi/pages/tokens', $rows[2]['configicon']);
        // Moodle's own manage-tokens page, not the settings section that
        // embeds it: the cog lands where the tokens actually are.
        $this->assertStringContainsString('/admin/webservice/tokens.php', $rows[3]['configicon']);
    }

    /**
     * A gate's switch reflects its stored config value.
     */
    public function test_switch_reflects_stored_config(): void {
        global $PAGE;

        $this->resetAfterTest();

        set_config('enablesessiongate', 1, 'tool_openapi');
        set_config('enableipgate', 0, 'tool_openapi');

        $rows = (new page())->export_for_template($PAGE->get_renderer('core'))['rows'];

        $this->assertStringContainsString('checked', $rows[0]['toggle']);
        $this->assertStringNotContainsString('checked', $rows[1]['toggle']);
    }
}
