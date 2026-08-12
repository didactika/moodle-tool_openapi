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
 * Tests for tag_mapper.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\tag_mapper
 */
final class tag_mapper_test extends \basic_testcase {
    /**
     * A function a plugin registered is tagged with that plugin.
     */
    public function test_plugin_function_is_tagged_with_its_component(): void {
        $this->assertSame('mod_quiz', tag_mapper::for_function('mod_quiz', 'mod_quiz_get_quizzes_by_courses'));
    }

    /**
     * Core functions are split by subsystem instead of all landing in the
     * one 'moodle' component they are registered under.
     *
     * @param string $name
     * @param string $expected
     * @dataProvider core_function_provider
     */
    public function test_core_function_is_tagged_with_its_subsystem(string $name, string $expected): void {
        $this->assertSame($expected, tag_mapper::for_function('moodle', $name));
    }

    /**
     * Core function names and the tag each should get.
     *
     * @return array
     */
    public static function core_function_provider(): array {
        return [
            'user' => ['core_user_get_users', 'core_user'],
            'course' => ['core_course_get_courses', 'core_course'],
            'group' => ['core_group_get_course_groups', 'core_group'],
            'enrol' => ['core_enrol_get_enrolled_users', 'core_enrol'],
            'webservice' => ['core_webservice_get_site_info', 'core_webservice'],
            // Must not be filed under core_course: the longest matching
            // subsystem wins, or every courseformat function would end up
            // in the course group.
            'courseformat' => ['core_courseformat_get_state', 'core_courseformat'],
            // No subsystem called 'get' exists, and inventing a 'core_get'
            // group out of a verb would be worse than the generic one.
            'verb, not a subsystem' => ['core_get_string', 'core'],
            'no subsystem at all' => ['core_fetch_notifications', 'core'],
        ];
    }

    /**
     * A real component's description is its own display name.
     */
    public function test_describe_returns_a_components_display_name(): void {
        $this->assertSame(get_string('pluginname', 'mod_quiz'), tag_mapper::describe('mod_quiz'));
    }

    /**
     * A core subsystem tag has no display name to borrow, and is left
     * without a description rather than given an invented one.
     */
    public function test_describe_returns_null_for_a_subsystem_tag(): void {
        $this->assertNull(tag_mapper::describe('core_course'));
        $this->assertNull(tag_mapper::describe('core'));
    }
}
