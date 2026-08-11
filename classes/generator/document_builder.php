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

use core_external\external_api;
use core_external\external_description;

/**
 * Assembles the full OpenAPI document from this site's external_functions catalog.
 *
 * One operation per installed webservice function, POST-only (the one HTTP
 * method Moodle's REST protocol accepts for every function, regardless of
 * that function's own read/write type) under a synthetic path named after
 * the function. Moodle itself has a single real endpoint
 * (webservice/rest/server.php) with wsfunction as a selector, which OpenAPI
 * has no native way to express as one path with many operations, so this
 * documents each function as its own path instead, matching how Swagger UI
 * (and every other OpenAPI consumer) expects to browse an API.
 *
 * Only ever runs during a full catalog (re)generation -- see type_mapper's
 * docblock for why that keeps this off any live request path.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_builder {
    /**
     * Build the OpenAPI 3.1 document for every installed webservice function.
     *
     * @return array
     */
    public static function build(): array {
        global $DB;

        $paths = [];
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            $info = external_api::external_function_info($function);
            $paths['/' . $info->name] = self::build_path_item($info);
        }

        return [
            'openapi' => '3.1.0',
            'info' => self::build_info(),
            'paths' => $paths,
            'components' => ['schemas' => self::build_shared_schemas()],
        ];
    }

    /**
     * The document's info block, tied to the Moodle site's own version --
     * this documents whatever the site currently exposes, not tool_openapi's
     * own version.
     *
     * @return array
     */
    private static function build_info(): array {
        global $CFG, $SITE;

        return [
            'title' => format_string($SITE->fullname) . ' web services',
            'version' => $CFG->release,
            'description' => 'Generated from this site\'s external_functions catalog by tool_openapi.',
        ];
    }

    /**
     * The path item (containing the single POST operation) for one function.
     *
     * @param \stdClass $info As returned by external_api::external_function_info().
     * @return array
     */
    private static function build_path_item(\stdClass $info): array {
        $operation = [
            'operationId' => $info->name,
            'tags' => [$info->component],
            'deprecated' => $info->deprecated ?? false,
            'x-moodle-capabilities' => empty($info->capabilities) ? [] : explode(',', $info->capabilities),
            'x-moodle-ajax-allowed' => $info->allowed_from_ajax ?? false,
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => ['schema' => type_mapper::map($info->parameters_desc)],
                ],
            ],
            'responses' => self::build_responses($info->returns_desc),
        ];

        if (!empty($info->description)) {
            $operation['summary'] = $info->description;
        }

        return ['post' => $operation];
    }

    /**
     * The responses object for one function: its real return shape on
     * success, and the standard Moodle webservice error shape otherwise.
     *
     * @param external_description|null $returns
     * @return array
     */
    private static function build_responses(?external_description $returns): array {
        $success = ['description' => 'Successful response.'];
        if ($returns !== null) {
            $success['content'] = [
                'application/json' => ['schema' => type_mapper::map($returns)],
            ];
        }

        return [
            '200' => $success,
            'default' => [
                'description' => 'Moodle webservice error.',
                'content' => [
                    'application/json' => ['schema' => ['$ref' => '#/components/schemas/WebserviceError']],
                ],
            ],
        ];
    }

    /**
     * Schemas shared across every operation, referenced by $ref.
     *
     * @return array
     */
    private static function build_shared_schemas(): array {
        return [
            'WebserviceError' => [
                'type' => 'object',
                'properties' => [
                    'exception' => ['type' => 'string'],
                    'errorcode' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                ],
                'required' => ['exception', 'errorcode', 'message'],
            ],
        ];
    }
}
