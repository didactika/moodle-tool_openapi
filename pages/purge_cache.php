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

/**
 * Purges the cached OpenAPI catalog document, then returns to settings.
 *
 * Not a self-invalidating cache in the usual sense -- see db/caches.php --
 * this is only the manual override for an administrator who does not want
 * to wait for the next scheduled regenerate_spec_task run.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

require_login();
require_capability('tool/openapi:manage', \context_system::instance());
require_sesskey();

\tool_openapi\local\document_cache::purge();

redirect(
    new moodle_url('/admin/settings.php', ['section' => 'tool_openapi']),
    get_string('cachepurged', 'tool_openapi'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
