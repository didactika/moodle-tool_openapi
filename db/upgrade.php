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
 * Upgrade steps for tool_openapi.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade steps for tool_openapi.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_openapi_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026081201) {
        $table = new xmldb_table('tool_openapi_tokens');
        $field = new xmldb_field('iprestriction', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'allowedfunctions');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026081201, 'tool', 'openapi');
    }

    if ($oldversion < 2026081202) {
        // Tokens are deleted outright now rather than flagged, so that a
        // token an administrator removed cannot linger in the table -- the
        // same thing core does for its own webservice tokens. What used to
        // be the audit value of keeping the row is served properly by the
        // token_created/token_deleted events instead.
        $table = new xmldb_table('tool_openapi_tokens');
        $field = new xmldb_field('revoked');

        if ($dbman->field_exists($table, $field)) {
            $DB->delete_records('tool_openapi_tokens', ['revoked' => 1]);
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026081202, 'tool', 'openapi');
    }

    return true;
}
