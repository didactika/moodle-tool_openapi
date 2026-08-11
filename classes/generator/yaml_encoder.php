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
 * Encodes a document_builder document as YAML, for openapi.php's ?format=yaml.
 *
 * Real block-style YAML for maps and sequences (proper indentation, "- "
 * items), but every scalar leaf -- string, int, float, bool, null -- is
 * written with json_encode(), never as a bare YAML plain scalar. JSON is a
 * valid subset of YAML 1.2 (a double-quoted YAML scalar follows the same
 * escaping rules as a JSON string), so this is not a shortcut that risks
 * producing invalid YAML; it is a deliberate way to sidestep every plain-
 * scalar ambiguity that a hand-written quoting-decision would otherwise
 * have to get right: a description starting with "- " or "? ", a title
 * that happens to read as "null" or "off", an operationId with a colon in
 * it, embedded newlines. There is no PHP YAML library available to lean on
 * here (Moodle core does not bundle one -- confirmed against core's own
 * composer.json), so the algorithm was validated by hand against a real
 * parser (js-yaml) on a fixture covering exactly these cases before being
 * ported to PHP.
 *
 * A PHP array can't distinguish an empty list from an empty map, so an
 * empty array is always written as "[]", regardless of which one it
 * conceptually is -- the same call json_encode() already makes for the
 * rest of this codebase's JSON output, so the two output formats stay
 * consistent with each other rather than one trying to be smarter than
 * the other about a case PHP's own type system can't express.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class yaml_encoder {
    /**
     * Encode a document as YAML.
     *
     * @param array $document A document as returned by document_builder::build().
     * @return string
     */
    public static function encode(array $document): string {
        return self::encode_node($document, 0);
    }

    /**
     * Encode one array node (list or map, PHP can't tell an empty one apart) at the given indent.
     *
     * @param array $node
     * @param int $indent
     * @return string
     */
    private static function encode_node(array $node, int $indent): string {
        if ($node === []) {
            return str_repeat('  ', $indent) . "[]\n";
        }

        return array_is_list($node)
            ? self::encode_sequence($node, $indent)
            : self::encode_mapping($node, $indent);
    }

    /**
     * Encode a non-empty list as a block sequence.
     *
     * @param array $items
     * @param int $indent
     * @return string
     */
    private static function encode_sequence(array $items, int $indent): string {
        $pad = str_repeat('  ', $indent);
        $childpad = $pad . '  ';

        $out = '';
        foreach ($items as $item) {
            if (is_array($item) && $item !== []) {
                $nested = rtrim(self::encode_node($item, $indent + 1), "\n");
                $lines = explode("\n", $nested);
                $lines[0] = $pad . '- ' . substr($lines[0], strlen($childpad));
                $out .= implode("\n", $lines) . "\n";
            } else {
                $out .= $pad . '- ' . self::encode_leaf($item) . "\n";
            }
        }

        return $out;
    }

    /**
     * Encode a non-empty associative array as a block mapping.
     *
     * @param array $map
     * @param int $indent
     * @return string
     */
    private static function encode_mapping(array $map, int $indent): string {
        $pad = str_repeat('  ', $indent);

        $out = '';
        foreach ($map as $key => $value) {
            $keystr = self::encode_scalar((string) $key);
            if (is_array($value) && $value !== []) {
                $out .= $pad . $keystr . ":\n" . self::encode_node($value, $indent + 1);
            } else {
                $out .= $pad . $keystr . ': ' . self::encode_leaf($value) . "\n";
            }
        }

        return $out;
    }

    /**
     * Encode a leaf value: an empty array, or a scalar.
     *
     * @param mixed $value
     * @return string
     */
    private static function encode_leaf(mixed $value): string {
        return is_array($value) ? '[]' : self::encode_scalar($value);
    }

    /**
     * Encode one scalar (string, int, float, bool or null) as a YAML flow scalar.
     *
     * @param string|int|float|bool|null $value
     * @return string
     */
    private static function encode_scalar(string|int|float|bool|null $value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
