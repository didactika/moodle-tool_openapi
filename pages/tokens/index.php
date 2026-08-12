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
 * Lists tool_openapi_tokens rows. Creating one is tokens/create.php,
 * deleting is tokens/delete.php -- this page only lists and reveals.
 *
 * A token is shown in plaintext exactly once, right after creation:
 * create.php stashes it in the session-scoped 'newtoken' cache
 * (db/caches.php, TTL 300s) and redirects here with ?newtoken=<id>, so a
 * refresh cannot show it again. Only the hash is ever stored.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_tokens');

$tokens = $DB->get_records('tool_openapi_tokens', null, 'timecreated DESC');

$newtokenid = optional_param('newtoken', 0, PARAM_INT);
$newtoken = null;
if ($newtokenid && isset($tokens[$newtokenid])) {
    $tokencache = \cache::make('tool_openapi', 'newtoken');
    $cached = $tokencache->get($newtokenid);
    if ($cached !== false) {
        $newtoken = $cached;
        $tokencache->delete($newtokenid);
    }
}

$renderable = new \tool_openapi\output\tokens\index_page($tokens);

echo $OUTPUT->header();
echo $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree(\tool_openapi\local\settings_nav::TAB_ACCESS));

if ($newtoken !== null) {
    echo $OUTPUT->render_from_template('tool_openapi/tokens/reveal', [
        'message' => get_string('tokencreatedonce', 'tool_openapi'),
        'tokenname' => $tokens[$newtokenid]->name,
        'token' => $newtoken,
    ]);
}

echo $OUTPUT->render_from_template('tool_openapi/tokens/index', $renderable->export_for_template($OUTPUT));
echo $OUTPUT->footer();
