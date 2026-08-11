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
 * Turns a document_builder document (OpenAPI 3.1 / JSON Schema 2020-12)
 * into OpenAPI 3.0.3.
 *
 * One generator, two outputs: type_mapper only ever produces one
 * incompatibility between the two OpenAPI versions -- a nullable field's
 * "type": [X, "null"] (valid in 3.1, not in 3.0) -- so this is a single
 * recursive rewrite of that one pattern into 3.0's own "type": X,
 * "nullable": true, not a second, parallel generator.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class openapi_downgrader {
    /**
     * Downgrade a document_builder document to OpenAPI 3.0.3.
     *
     * @param array $document A document as returned by document_builder::build().
     * @return array
     */
    public static function to_3_0(array $document): array {
        $document['openapi'] = '3.0.3';
        $document['paths'] = self::downgrade_tree($document['paths']);
        $document['components'] = self::downgrade_tree($document['components']);

        return $document;
    }

    /**
     * Rewrite this node's [type, "null"] pair if present, then recurse.
     *
     * @param mixed $node
     * @return mixed
     */
    private static function downgrade_tree(mixed $node): mixed {
        if (!is_array($node)) {
            return $node;
        }

        if (isset($node['type']) && is_array($node['type'])) {
            $realtypes = array_values(array_diff($node['type'], ['null']));
            $node['type'] = $realtypes[0];
            $node['nullable'] = true;
        }

        foreach ($node as $key => $value) {
            $node[$key] = self::downgrade_tree($value);
        }

        return $node;
    }
}
