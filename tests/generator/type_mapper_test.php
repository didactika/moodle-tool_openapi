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

use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Tests for type_mapper.
 *
 * The nested-structure fixture mirrors core_course_get_courses's real
 * execute_parameters() shape, as dumped against live Moodle 4.5 and 5.2
 * installs in the fase 1 spike (.claude/proposal/04-plan-de-fases.md) --
 * hand-built here rather than fetched from the live core function, because
 * that spike also showed the real shape already drifting once between the
 * two supported Moodle versions (5.2 adds a field 4.5 does not have), which
 * would make an exact comparison against the live function version-fragile.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\type_mapper
 */
final class type_mapper_test extends \basic_testcase {

    public function test_returns_null_for_a_function_with_no_return_value(): void {
        $this->assertNull(type_mapper::map(null));
    }

    public function test_maps_scalar_param_types_to_json_schema(): void {
        $cases = [
            [PARAM_INT, ['type' => 'integer']],
            [PARAM_FLOAT, ['type' => 'number']],
            [PARAM_BOOL, ['type' => 'boolean']],
            [PARAM_URL, ['type' => 'string', 'format' => 'uri']],
            [PARAM_EMAIL, ['type' => 'string', 'format' => 'email']],
            // Not in type_mapper's explicit map: must fall back to "string".
            [PARAM_ALPHANUM, ['type' => 'string']],
            [PARAM_COMPONENT, ['type' => 'string']],
        ];

        foreach ($cases as [$paramtype, $expected]) {
            $value = new external_value($paramtype, '', VALUE_REQUIRED, null, NULL_NOT_ALLOWED);
            $this->assertEquals($expected, type_mapper::map($value));
        }
    }

    public function test_allownull_turns_type_into_a_two_element_array(): void {
        $value = new external_value(PARAM_INT, '', VALUE_REQUIRED, null, NULL_ALLOWED);
        $schema = type_mapper::map($value);
        $this->assertSame(['integer', 'null'], $schema['type']);
    }

    public function test_normalises_multiline_descriptions(): void {
        // Real desc string from core_course_get_contents on both 4.5 and 5.2,
        // indentation and all, confirmed by the fase 1 spike.
        $desc = "Type of completion tracking:\n"
            . "                                        0 means none, 1 manual, 2 automatic.";
        $value = new external_value(PARAM_INT, $desc, VALUE_REQUIRED, null, NULL_NOT_ALLOWED);
        $schema = type_mapper::map($value);
        $this->assertSame('Type of completion tracking: 0 means none, 1 manual, 2 automatic.', $schema['description']);
    }

    public function test_required_tristate_matches_moodles_value_constants(): void {
        $structure = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, '', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
                'extra options',
                VALUE_DEFAULT,
                [],
                NULL_NOT_ALLOWED
            ),
            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL, null, NULL_ALLOWED),
        ]);

        $schema = type_mapper::map($structure);

        $this->assertSame(['id'], $schema['required']);
        $this->assertSame([], $schema['properties']['tags']['default']);
        $this->assertArrayNotHasKey('default', $schema['properties']['idnumber']);
        $this->assertSame(['string', 'null'], $schema['properties']['idnumber']['type']);
    }

    public function test_maps_the_nested_structure_confirmed_by_the_fase1_spike(): void {
        $params = new external_function_parameters([
            'options' => new external_single_structure([
                'ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED, null, NULL_ALLOWED),
                    'List of course id.',
                    VALUE_OPTIONAL,
                    null,
                    NULL_NOT_ALLOWED
                ),
            ], 'options - operator OR is used', VALUE_DEFAULT, [], NULL_NOT_ALLOWED),
        ]);

        $schema = type_mapper::map($params);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'options' => [
                    'description' => 'options - operator OR is used',
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'description' => 'List of course id.',
                            'type' => 'array',
                            'items' => [
                                'description' => 'Course id',
                                'type' => ['integer', 'null'],
                            ],
                        ],
                    ],
                    'default' => [],
                ],
            ],
        ], $schema);
    }

    public function test_rejects_an_unsupported_external_description_subclass(): void {
        $this->expectException(\coding_exception::class);
        type_mapper::map(new class ('', VALUE_REQUIRED, null, false) extends external_description {
        });
    }
}
