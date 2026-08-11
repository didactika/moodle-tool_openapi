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
 * Tests for scope.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\scope
 */
final class scope_test extends \basic_testcase {
    /**
     * full_catalog() allows anything and has no function list.
     */
    public function test_full_catalog_allows_anything(): void {
        $scope = scope::full_catalog();

        $this->assertTrue($scope->is_unrestricted());
        $this->assertNull($scope->allowed_functions());
        $this->assertTrue($scope->allows('core_course_get_courses'));
        $this->assertTrue($scope->allows('anything_at_all'));
    }

    /**
     * limited_to() only allows the exact names given.
     */
    public function test_limited_to_only_allows_named_functions(): void {
        $scope = scope::limited_to(['core_course_get_courses']);

        $this->assertFalse($scope->is_unrestricted());
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
        $this->assertTrue($scope->allows('core_course_get_courses'));
        $this->assertFalse($scope->allows('core_course_get_contents'));
    }

    /**
     * Null or blank means the full catalog, matching the DB field's
     * documented meaning.
     */
    public function test_from_allowedfunctions_field_null_or_blank_is_full_catalog(): void {
        $this->assertTrue(scope::from_allowedfunctions_field(null)->is_unrestricted());
        $this->assertTrue(scope::from_allowedfunctions_field('')->is_unrestricted());
        $this->assertTrue(scope::from_allowedfunctions_field("  \n  \n")->is_unrestricted());
    }

    /**
     * One function name per line, blank lines and surrounding whitespace ignored.
     */
    public function test_from_allowedfunctions_field_parses_one_name_per_line(): void {
        $raw = "core_course_get_courses\n\n  core_course_get_contents  \n";

        $scope = scope::from_allowedfunctions_field($raw);

        $this->assertFalse($scope->is_unrestricted());
        $this->assertSame(
            ['core_course_get_courses', 'core_course_get_contents'],
            $scope->allowed_functions()
        );
    }
}
