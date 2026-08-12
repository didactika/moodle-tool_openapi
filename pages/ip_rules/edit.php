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
 * Edits an existing tool_openapi_ip_rules row.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_ip_rules');

$id = required_param('id', PARAM_INT);

$pagetitle = get_string('editiprule', 'tool_openapi');
$rulesurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/index.php');

$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/ip_rules/edit.php', ['id' => $id]));
$PAGE->navbar->add($pagetitle);
$PAGE->set_title($pagetitle);

$rule = $DB->get_record('tool_openapi_ip_rules', ['id' => $id], '*', IGNORE_MISSING);
if (!$rule) {
    redirect($rulesurl);
}

$isrestricted = $rule->allowedfunctions !== null;

$form = new \tool_openapi\form\ip_rule_form();
$form->set_data([
    'id' => $rule->id,
    'iprange' => $rule->iprange,
    'description' => $rule->description,
    'restrictfunctions' => $isrestricted ? 1 : 0,
    'allowedfunctions' => $isrestricted ? explode("\n", $rule->allowedfunctions) : [],
    'enabled' => $rule->enabled,
]);

if ($form->is_cancelled()) {
    redirect($rulesurl);
} else if ($data = $form->get_data()) {
    $restricted = !empty($data->restrictfunctions) && !empty($data->allowedfunctions);

    $DB->update_record('tool_openapi_ip_rules', (object) [
        'id' => $data->id,
        'iprange' => $data->iprange,
        'description' => $data->description,
        'allowedfunctions' => $restricted ? implode("\n", $data->allowedfunctions) : null,
        'enabled' => $data->enabled,
    ]);

    redirect($rulesurl, get_string('iprulesaved', 'tool_openapi'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
