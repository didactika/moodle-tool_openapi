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

namespace tool_openapi\task;

use tool_openapi\local\document_cache;

/**
 * Tests for regenerate_spec_task.
 *
 * Neither test compares the cached document against a second, independent
 * document_builder::build() call -- see document_cache_test's docblock for
 * why that comparison is inherently flaky (at least one core external
 * function's parameter default is a live time() value, captured fresh on
 * every build). Checking that a known, always-installed function's path
 * survived is the real invariant these tests need.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\task\regenerate_spec_task
 */
final class regenerate_spec_task_test extends \advanced_testcase {
    /**
     * Running the task leaves a real, populated document cached.
     */
    public function test_execute_leaves_the_document_cached(): void {
        $this->resetAfterTest();

        (new regenerate_spec_task())->execute();

        $cached = \cache::make('tool_openapi', 'document')->get('document');
        $this->assertArrayHasKey('/core_webservice_get_site_info', $cached['paths']);
    }

    /**
     * Running the task after the cache is already warm still leaves it
     * correctly populated -- the purge-then-rebuild is not a no-op that
     * only works on an empty cache.
     */
    public function test_execute_rebuilds_an_already_warm_cache(): void {
        $this->resetAfterTest();
        document_cache::get();

        (new regenerate_spec_task())->execute();

        $cached = \cache::make('tool_openapi', 'document')->get('document');
        $this->assertArrayHasKey('/core_webservice_get_site_info', $cached['paths']);
    }
}
