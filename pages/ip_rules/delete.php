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
 * Deletes a tool_openapi_ip_rules row.
 *
 * Deleted for real, unlike tokens -- an IP rule is site configuration
 * (which addresses are allowed), not a record of what a credential did,
 * so there is no audit trail to preserve by revoking instead.
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
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/ip_rules/delete.php', ['id' => $id]));
$PAGE->set_title(get_string('delete'));
$PAGE->set_heading(get_string('delete'));
$PAGE->set_pagelayout('admin');

$rulesurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/index.php');

$rule = $DB->get_record('tool_openapi_ip_rules', ['id' => $id], '*', IGNORE_MISSING);
if (!$rule) {
    redirect($rulesurl);
}

if ($confirm && confirm_sesskey()) {
    $DB->delete_records('tool_openapi_ip_rules', ['id' => $id]);

    redirect($rulesurl, get_string('ipruledeleted', 'tool_openapi'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$confirmurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(get_string('confirmdeleteiprule', 'tool_openapi', $rule->iprange), $confirmurl, $rulesurl);

echo $OUTPUT->footer();
