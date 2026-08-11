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
 * Tests for document_builder.
 *
 * Runs against this test site's real, live external_functions catalog --
 * unlike type_mapper_test, there is no way around that here (this class's
 * whole job is reading that table) -- so assertions stick to structural
 * facts true on any Moodle install, never an exact document comparison,
 * for the same version-drift reason recorded in type_mapper_test.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\document_builder
 */
final class document_builder_test extends \advanced_testcase {
    /**
     * The document has a valid OpenAPI 3.1 envelope tied to this site.
     */
    public function test_build_returns_a_valid_openapi_envelope(): void {
        global $CFG;

        $this->resetAfterTest();

        $doc = document_builder::build();

        $this->assertSame('3.1.0', $doc['openapi']);
        $this->assertSame($CFG->release, $doc['info']['version']);
        $this->assertArrayHasKey('paths', $doc);
        $this->assertArrayHasKey('components', $doc);
    }

    /**
     * Every installed function gets at most one path -- not exactly one:
     * a real site can have a function that fails to introspect (see
     * build_path_or_skip's docblock), which build() skips rather than
     * crashing on.
     */
    public function test_every_installed_function_becomes_a_path(): void {
        global $DB;

        $this->resetAfterTest();

        $installed = $DB->count_records('external_functions');
        $doc = document_builder::build();

        $this->assertGreaterThan(0, count($doc['paths']));
        $this->assertLessThanOrEqual($installed, count($doc['paths']));
    }

    /**
     * core_webservice_get_site_info exists on every Moodle site and returns
     * data, so it exercises both requestBody and a real response schema.
     */
    public function test_documents_a_known_stable_function(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();

        $this->assertArrayHasKey('/core_webservice_get_site_info', $doc['paths']);
        $operation = $doc['paths']['/core_webservice_get_site_info']['post'];

        $this->assertSame('core_webservice_get_site_info', $operation['operationId']);
        $this->assertArrayHasKey('schema', $operation['requestBody']['content']['application/json']);
        $this->assertArrayHasKey('content', $operation['responses']['200']);
        $this->assertSame(
            ['$ref' => '#/components/schemas/WebserviceError'],
            $operation['responses']['default']['content']['application/json']['schema']
        );
    }

    /**
     * A function Moodle itself reports as deprecated is flagged as such --
     * found dynamically rather than hardcoded, since which functions are
     * deprecated changes release to release.
     */
    public function test_flags_a_deprecated_function_when_one_exists(): void {
        global $DB;

        $this->resetAfterTest();

        $deprecatedname = null;
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            try {
                $info = \core_external\external_api::external_function_info($function);
            } catch (\Throwable $e) {
                continue;
            }
            if ($info->deprecated ?? false) {
                $deprecatedname = $info->name;
                break;
            }
        }

        if ($deprecatedname === null) {
            $this->markTestSkipped('No deprecated external function installed on this site.');
        }

        $doc = document_builder::build();
        $this->assertTrue($doc['paths']['/' . $deprecatedname]['post']['deprecated']);
    }

    /**
     * The shared error schema referenced by every operation is well-formed.
     */
    public function test_shared_error_schema_is_well_formed(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'exception' => ['type' => 'string'],
                'errorcode' => ['type' => 'string'],
                'message' => ['type' => 'string'],
            ],
            'required' => ['exception', 'errorcode', 'message'],
        ], $doc['components']['schemas']['WebserviceError']);
    }
}
