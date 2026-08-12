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
 * Deletes a tool_openapi_tokens row.
 *
 * Deleted outright, not flagged: a credential an administrator removed
 * should not survive anywhere, which is the same thing core does for its
 * own webservice tokens. The lasting record is the token_deleted event in
 * the site log, which is why it is triggered with a record snapshot before
 * the row goes.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_tokens');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$pagetitle = get_string('deletetoken', 'tool_openapi');
$tokensurl = new moodle_url('/admin/tool/openapi/pages/tokens/index.php');

$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/tokens/delete.php', ['id' => $id]));
$PAGE->navbar->add($pagetitle);
$PAGE->set_title($pagetitle);

$token = $DB->get_record('tool_openapi_tokens', ['id' => $id], '*', IGNORE_MISSING);
if (!$token) {
    redirect($tokensurl);
}

if ($confirm && confirm_sesskey()) {
    $DB->delete_records('tool_openapi_tokens', ['id' => $id]);

    $event = \tool_openapi\event\token_deleted::create([
        'objectid' => $token->id,
        'context' => \context_system::instance(),
        'other' => ['name' => $token->name],
    ]);
    // Required before the observers run: the row is already gone, so
    // get_record_snapshot() has nothing left to read without this.
    $event->add_record_snapshot('tool_openapi_tokens', $token);
    $event->trigger();

    redirect(
        $tokensurl,
        get_string('tokendeleted', 'tool_openapi', $token->name),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$confirmurl = new moodle_url('/admin/tool/openapi/pages/tokens/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('confirmdeletetoken', 'tool_openapi', $token->name), $confirmurl, $tokensurl);
echo $OUTPUT->footer();
