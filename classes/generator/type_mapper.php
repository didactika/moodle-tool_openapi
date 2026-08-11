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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Converts a Moodle external API parameter/return description into JSON Schema.
 *
 * Stateless by design: every method is static and nothing here touches the
 * database or a cache. It only ever runs during a full catalog
 * (re)generation, the same rare event (plugin install/upgrade/uninstall,
 * or an administrator forcing it) that already purges tool_openapi's own
 * cache -- a live site's webservice traffic never reaches this class.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class type_mapper {
    /**
     * Moodle PARAM_* constants that need a JSON Schema type other than the
     * "string" fallback in map(). Most of Moodle's PARAM_* validators
     * constrain the *content* of a string (alphanumeric, a safe path, a
     * component name, ...), not its JSON representation, so leaving them
     * out here and falling back to "string" is correct, not incomplete.
     *
     * @var array<string, array<string, string>>
     */
    private const SCALAR_TYPES = [
        PARAM_INT => ['type' => 'integer'],
        PARAM_FLOAT => ['type' => 'number'],
        PARAM_LOCALISEDFLOAT => ['type' => 'number'],
        PARAM_BOOL => ['type' => 'boolean'],
        PARAM_URL => ['type' => 'string', 'format' => 'uri'],
        PARAM_LOCALURL => ['type' => 'string', 'format' => 'uri'],
        PARAM_EMAIL => ['type' => 'string', 'format' => 'email'],
    ];

    /**
     * Convert one node of a Moodle parameter/return description tree into
     * JSON Schema, recursing into its children.
     *
     * @param external_description|null $description Null for a function with no return value.
     * @return array|null The JSON Schema fragment, or null when $description is null.
     * @throws \coding_exception If $description is a subclass this method does not know how to map.
     */
    public static function map(?external_description $description): ?array {
        if ($description === null) {
            return null;
        }

        $schema = [];
        if ($description->desc !== '') {
            $schema['description'] = self::normalise_description($description->desc);
        }

        if ($description instanceof external_multiple_structure) {
            $schema['type'] = 'array';
            $schema['items'] = self::map($description->content);
        } else if ($description instanceof external_single_structure) {
            $schema['type'] = 'object';
            $schema['properties'] = [];
            $required = [];
            foreach ($description->keys as $key => $child) {
                $schema['properties'][$key] = self::map($child);
                if ($child->required === VALUE_REQUIRED) {
                    $required[] = $key;
                } else if ($child->required === VALUE_DEFAULT) {
                    $schema['properties'][$key]['default'] = $child->default;
                }
            }
            if ($required !== []) {
                $schema['required'] = $required;
            }
        } else if ($description instanceof external_value) {
            $schema += self::SCALAR_TYPES[$description->type] ?? ['type' => 'string'];
        } else {
            // Every real external_description subclass Moodle ships (including
            // external_format_value and external_warnings) is one of the three
            // above; a plugin cannot make validate_parameters()/
            // clean_returnvalue() accept a fourth kind, so reaching this means
            // something Moodle itself would already have rejected.
            throw new \coding_exception('Unsupported external_description subclass: ' . get_class($description));
        }

        if ($description->allownull) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        return $schema;
    }

    /**
     * Collapse the literal whitespace Moodle's own source code leaves in
     * multi-line external_value descriptions into a single line.
     *
     * @param string $desc
     * @return string
     */
    private static function normalise_description(string $desc): string {
        return trim(preg_replace('/\s+/', ' ', $desc));
    }
}
