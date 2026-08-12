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
 * has no native way to express as one path with many operations (a given
 * path+method pair can only ever describe one Operation Object), so this
 * documents each function as its own path instead, matching how Swagger UI
 * (and every other OpenAPI consumer) expects to browse an API. Every
 * operation carries an x-moodle-real-endpoint extension and a note in its
 * own description pointing at the one real URL, so a consumer (or a future
 * Swagger UI requestInterceptor -- see the viewer page's own plan) knows
 * this path is an organisational convenience, not the literal request target.
 *
 * The request shape documented here (requestBody as
 * application/x-www-form-urlencoded, wstoken/wsfunction/moodlewsrestformat
 * as query parameters) is not a guess: webservice/rest/lib.php's
 * parse_request() does array_merge($_GET, $_POST) and never reads a JSON
 * body -- confirmed reading that file directly, not assumed from Moodle's
 * public docs, since PHP only auto-populates $_POST for
 * x-www-form-urlencoded/multipart bodies, never for application/json.
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
     * The one real Moodle REST endpoint every synthetic path documented
     * here actually resolves to -- see the class docblock.
     */
    private const REAL_ENDPOINT = '/webservice/rest/server.php';

    /**
     * Build the OpenAPI 3.1 document for every installed webservice function.
     *
     * @return array
     */
    public static function build(): array {
        global $CFG, $DB;

        $paths = [];
        $tags = [];
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            $item = self::build_path_or_skip($function);
            if ($item !== null) {
                $paths['/' . $function->name] = $item;
                foreach ($item['post']['tags'] as $tag) {
                    $tags[$tag] = true;
                }
            }
        }

        return [
            'openapi' => '3.1.0',
            'info' => self::build_info(),
            'servers' => [['url' => $CFG->wwwroot]],
            'tags' => self::build_tags(array_keys($tags)),
            'paths' => $paths,
            'components' => [
                'schemas' => self::build_shared_schemas(),
                'parameters' => self::build_shared_parameters(),
            ],
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
     * The document's root tag list: every tag actually used by an
     * operation, in alphabetical order.
     *
     * Declared explicitly rather than left implicit because a viewer that
     * meets a tag only on an operation has nothing to order the groups by
     * and no description to show above them, so the catalog comes out in
     * whatever order the paths happen to be in.
     *
     * @param string[] $names Tags used by at least one operation.
     * @return array
     */
    private static function build_tags(array $names): array {
        sort($names);

        $tags = [];
        foreach ($names as $name) {
            $tag = ['name' => $name];

            $description = tag_mapper::describe($name);
            if ($description !== null) {
                $tag['description'] = $description;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Introspect one function and build its path item, or null if it can't
     * be introspected.
     *
     * Confirmed for real, not hypothetical: a stock Moodle 4.5 site can
     * have an installed function (mod_quiz's add_random_questions, seen in
     * CI) whose implementation class does not autoload and has no working
     * legacy classpath fallback either, so external_function_info() throws.
     * One such function must not take down the whole catalog.
     *
     * Deliberately not reported via debugging(): the broken registration
     * belongs to the other plugin, not to tool_openapi, and Moodle's own
     * PHPUnit harness fails any test that triggers an unexpected
     * debugging() call, which would make this catalog untestable against
     * a real site that happens to have one such function installed.
     *
     * @param \stdClass $function A raw external_functions row.
     * @return array|null
     */
    private static function build_path_or_skip(\stdClass $function): ?array {
        try {
            $info = external_api::external_function_info($function);
        } catch (\Throwable) {
            return null;
        }

        return self::build_path_item($info);
    }

    /**
     * The path item (containing the single POST operation) for one function.
     *
     * @param \stdClass $info As returned by external_api::external_function_info().
     * @return array
     */
    private static function build_path_item(\stdClass $info): array {
        $realendpointnote = 'Real request target: POST ' . self::REAL_ENDPOINT
            . ' with wsfunction=' . $info->name . ' merged into the query string or form body -- '
            . 'this path exists for documentation only, Moodle has no per-function URL.';

        $operation = [
            'operationId' => $info->name,
            'tags' => [tag_mapper::for_function($info->component, $info->name)],
            'deprecated' => $info->deprecated ?? false,
            'description' => $realendpointnote,
            'x-moodle-real-endpoint' => self::REAL_ENDPOINT,
            'x-moodle-capabilities' => empty($info->capabilities) ? [] : explode(',', $info->capabilities),
            'x-moodle-ajax-allowed' => $info->allowed_from_ajax ?? false,
            'parameters' => [
                ['$ref' => '#/components/parameters/wstoken'],
                ['$ref' => '#/components/parameters/moodlewsrestformat'],
                [
                    'name' => 'wsfunction',
                    'in' => 'query',
                    'required' => true,
                    'schema' => ['type' => 'string', 'const' => $info->name],
                ],
            ],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/x-www-form-urlencoded' => ['schema' => type_mapper::map($info->parameters_desc)],
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

    /**
     * Parameters identical across every operation, referenced by $ref
     * instead of repeated in each of the (possibly hundreds of) operations.
     * wsfunction is not here -- its value differs per operation, see
     * build_path_item().
     *
     * @return array
     */
    private static function build_shared_parameters(): array {
        return [
            'wstoken' => [
                'name' => 'wstoken',
                'in' => 'query',
                'required' => true,
                'description' => 'A token issued from this site\'s OpenAPI documentation Tokens page, '
                    . 'or any other valid Moodle webservice token.',
                'schema' => ['type' => 'string'],
            ],
            'moodlewsrestformat' => [
                'name' => 'moodlewsrestformat',
                'in' => 'query',
                'required' => false,
                'description' => 'Response format. Moodle defaults to xml when this is omitted.',
                'schema' => ['type' => 'string', 'enum' => ['json', 'xml'], 'default' => 'json'],
            ],
        ];
    }
}
