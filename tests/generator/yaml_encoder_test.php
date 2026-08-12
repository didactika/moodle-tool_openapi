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
 * Tests for yaml_encoder.
 *
 * The block-indentation algorithm itself (list-of-lists, list-of-mappings,
 * arbitrary nesting) was validated separately against a real YAML parser
 * (js-yaml, outside this repo, since no PHP YAML library is available to
 * round-trip against here) before being ported to PHP -- see the class's
 * own docblock. These tests pin the exact byte-for-byte output of the
 * ported PHP implementation instead of re-proving the algorithm.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\yaml_encoder
 */
final class yaml_encoder_test extends \basic_testcase {
    /**
     * An empty document is an empty YAML list -- the same call json_encode()
     * already makes for the ambiguous case, see the class docblock.
     */
    public function test_encode_empty_document_is_empty_list(): void {
        $this->assertSame("[]\n", yaml_encoder::encode([]));
    }

    /**
     * A flat mapping of scalars, one "key: value" line per entry.
     */
    public function test_encode_flat_mapping(): void {
        $yaml = yaml_encoder::encode([
            'openapi' => '3.1.0',
            'count' => 2,
            'deprecated' => false,
            'note' => null,
        ]);

        $expected = "\"openapi\": \"3.1.0\"\n\"count\": 2\n\"deprecated\": false\n\"note\": null\n";
        $this->assertSame($expected, $yaml);
    }

    /**
     * A nested mapping opens with "key:" and indents its contents by two spaces.
     */
    public function test_encode_nested_mapping_indents_by_two_spaces(): void {
        $yaml = yaml_encoder::encode(['info' => ['title' => 'test']]);

        $this->assertSame("\"info\":\n  \"title\": \"test\"\n", $yaml);
    }

    /**
     * A sequence of scalars is one "- value" line per item, indented under its key.
     */
    public function test_encode_sequence_of_scalars(): void {
        $yaml = yaml_encoder::encode(['tags' => ['a', 'b']]);

        $this->assertSame("\"tags\":\n  - \"a\"\n  - \"b\"\n", $yaml);
    }

    /**
     * An empty array value is written inline as "[]", never as its own block.
     */
    public function test_encode_empty_nested_array_is_inline_empty_list(): void {
        $yaml = yaml_encoder::encode(['paths' => []]);

        $this->assertSame("\"paths\": []\n", $yaml);
    }

    /**
     * An empty stdClass -- type_mapper's explicit marker for "this is
     * definitely an empty object, not an empty list" -- is written inline
     * as "{}", not guessed at like a genuinely ambiguous empty PHP array.
     */
    public function test_encode_empty_stdclass_is_inline_empty_map(): void {
        $yaml = yaml_encoder::encode(['properties' => new \stdClass()]);

        $this->assertSame("\"properties\": {}\n", $yaml);
    }

    /**
     * A sequence item that is itself a mapping puts "- " before its first
     * key and aligns the rest of that mapping's keys underneath it.
     */
    public function test_encode_sequence_of_mappings_puts_dash_before_first_key(): void {
        $yaml = yaml_encoder::encode(['list' => [['name' => 'a', 'in' => 'query']]]);

        $expected = "\"list\":\n  - \"name\": \"a\"\n    \"in\": \"query\"\n";
        $this->assertSame($expected, $yaml);
    }

    /**
     * Every scalar, however YAML-unsafe as a bare value, is written through
     * the same JSON escaping as every other scalar -- never a bare/plain
     * form. This is what makes strings like "- item", "true" or embedded
     * newlines safe without a plain-scalar safety analysis.
     */
    public function test_encode_scalar_always_uses_json_escaping(): void {
        $tricky = [
            'colon' => 'plain: value',
            'dashlead' => '- not a sequence item',
            'boollike' => 'true',
            'numlike' => '3.0',
            'unicode' => 'café — 日本語',
            'multiline' => "line one\nline two\twith a tab",
            'quotes' => 'she said "hi" and left a \\ behind',
        ];

        foreach ($tricky as $key => $value) {
            $yaml = yaml_encoder::encode([$key => $value]);
            $expectedkey = json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $expectedvalue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->assertSame($expectedkey . ': ' . $expectedvalue . "\n", $yaml);
        }
    }
}
