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

namespace tool_openapi\local;

use tool_openapi\generator\document_builder;

/**
 * Tests for document_cache.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\local\document_cache
 */
final class document_cache_test extends \advanced_testcase {
    /**
     * get() returns the same document document_builder would build directly.
     */
    public function test_get_returns_the_built_document(): void {
        $this->resetAfterTest();

        $this->assertSame(document_builder::build(), document_cache::get());
    }

    /**
     * get() leaves the document sitting in the underlying cache.
     */
    public function test_get_populates_the_underlying_cache(): void {
        $this->resetAfterTest();

        $document = document_cache::get();

        $this->assertSame($document, \cache::make('tool_openapi', 'document')->get('document'));
    }

    /**
     * purge() empties the underlying cache.
     */
    public function test_purge_empties_the_underlying_cache(): void {
        $this->resetAfterTest();

        document_cache::get();
        document_cache::purge();

        $this->assertFalse(\cache::make('tool_openapi', 'document')->get('document'));
    }

    /**
     * A get() after purge() rebuilds and re-populates the cache.
     */
    public function test_get_after_purge_rebuilds_the_document(): void {
        $this->resetAfterTest();

        document_cache::get();
        document_cache::purge();
        $document = document_cache::get();

        $this->assertSame(document_builder::build(), $document);
        $this->assertSame($document, \cache::make('tool_openapi', 'document')->get('document'));
    }
}
