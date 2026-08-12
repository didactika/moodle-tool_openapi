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
 * Tests for openapi_downgrader.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\openapi_downgrader
 */
final class openapi_downgrader_test extends \basic_testcase {
    /**
     * Wrap one request body schema in a minimal, otherwise-empty document.
     *
     * @param array $schema
     * @return array
     */
    private function document_with_schema(array $schema): array {
        return [
            'openapi' => '3.1.0',
            'info' => [],
            'paths' => ['/x' => ['post' => ['requestBody' => ['content' => ['application/json' => [
                'schema' => $schema,
            ]]]]]],
            'components' => ['schemas' => []],
        ];
    }

    /**
     * Pull the request body schema back out of a downgraded document.
     *
     * @param array $document
     * @return array
     */
    private function schema_from(array $document): array {
        return $document['paths']['/x']['post']['requestBody']['content']['application/json']['schema'];
    }

    /**
     * The openapi field itself is rewritten to 3.0.3.
     */
    public function test_rewrites_the_openapi_version(): void {
        $document = ['openapi' => '3.1.0', 'info' => [], 'paths' => [], 'components' => []];
        $this->assertSame('3.0.3', openapi_downgrader::to_3_0($document)['openapi']);
    }

    /**
     * A nullable scalar's [type, "null"] array becomes nullable: true.
     */
    public function test_nullable_type_array_becomes_nullable_true(): void {
        $document = $this->document_with_schema(['type' => ['integer', 'null']]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'integer', 'nullable' => true], $schema);
    }

    /**
     * A non-nullable scalar type is left untouched.
     */
    public function test_non_nullable_types_are_left_alone(): void {
        $document = $this->document_with_schema(['type' => 'string']);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'string'], $schema);
    }

    /**
     * A single-value "const" (2020-12 JSON Schema, used for wsfunction's
     * fixed value) becomes a one-element "enum" -- 3.0's dialect has no
     * "const" keyword.
     */
    public function test_const_becomes_a_one_element_enum(): void {
        $document = $this->document_with_schema(['type' => 'string', 'const' => 'core_course_get_courses']);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'string', 'enum' => ['core_course_get_courses']], $schema);
        $this->assertArrayNotHasKey('const', $schema);
    }

    /**
     * A nullable field nested inside an object inside an array is still found.
     */
    public function test_recurses_into_nested_object_and_array_schemas(): void {
        $document = $this->document_with_schema([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'idnumber' => ['type' => ['string', 'null']],
                ],
            ],
        ]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(
            ['type' => 'string', 'nullable' => true],
            $schema['items']['properties']['idnumber']
        );
    }
}
