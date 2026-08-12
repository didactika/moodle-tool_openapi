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
 * Periodically rebuilds the cached full catalog document.
 *
 * The cache is already correct without this -- see db/caches.php -- but
 * a purge (by this task's own run, or by any plugin install/upgrade/
 * uninstall on the site) leaves the cache empty, not regenerated, so
 * whichever request hits openapi.php next pays the full build cost. This
 * task pays that cost itself on a schedule, so no real request has to.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regenerate_spec_task extends \core\task\scheduled_task {
    /**
     * The task's name, shown in Site administration > Server > Scheduled tasks.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('regeneratespectask', 'tool_openapi');
    }

    /**
     * Purge and immediately rebuild the cached document.
     */
    public function execute(): void {
        document_cache::purge();
        document_cache::get();
    }
}
