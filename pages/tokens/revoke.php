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
 * Revokes a tool_openapi_tokens row.
 *
 * Revokes, never deletes -- see db/install.xml's own comment on
 * tool_openapi_tokens.revoked: keeps the audit trail of what a token did
 * before it was revoked.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');

require_login();
$context = context_system::instance();
require_capability('tool/openapi:manage', $context);

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/tokens/revoke.php', ['id' => $id]));
$PAGE->set_title(get_string('revoke', 'tool_openapi'));
$PAGE->set_heading(get_string('revoke', 'tool_openapi'));
$PAGE->set_pagelayout('admin');

$tokensurl = new moodle_url('/admin/tool/openapi/pages/tokens/index.php');

$token = $DB->get_record('tool_openapi_tokens', ['id' => $id], '*', IGNORE_MISSING);
if (!$token) {
    redirect($tokensurl);
}

if ($confirm && confirm_sesskey()) {
    $DB->set_field('tool_openapi_tokens', 'revoked', 1, ['id' => $id]);

    redirect($tokensurl, get_string('tokenrevoked', 'tool_openapi', $token->name), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$confirmurl = new moodle_url('/admin/tool/openapi/pages/tokens/revoke.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(get_string('confirmrevoketoken', 'tool_openapi', $token->name), $confirmurl, $tokensurl);

echo $OUTPUT->footer();
