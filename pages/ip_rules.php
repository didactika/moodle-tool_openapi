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
 * Lists tool_openapi_ip_rules rows and creates new ones.
 *
 * Editing an existing row is edit_ip_rule.php, deleting is
 * delete_ip_rule.php -- same split as pages/tokens.php's own create-here,
 * act-elsewhere pattern.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

require_login();
$context = context_system::instance();
require_capability('tool/openapi:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/ip_rules.php'));
$PAGE->set_title(get_string('manageiprules', 'tool_openapi'));
$PAGE->set_heading(get_string('manageiprules', 'tool_openapi'));
$PAGE->set_pagelayout('admin');

$form = new \tool_openapi\form\ip_rule_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/openapi/pages/ip_rules.php'));
} else if ($data = $form->get_data()) {
    $DB->insert_record('tool_openapi_ip_rules', (object) [
        'iprange' => $data->iprange,
        'description' => $data->description,
        'allowedfunctions' => trim($data->allowedfunctions) === '' ? null : $data->allowedfunctions,
        'enabled' => $data->enabled,
        'timecreated' => time(),
    ]);

    redirect(
        new moodle_url('/admin/tool/openapi/pages/ip_rules.php'),
        get_string('iprulesaved', 'tool_openapi'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$rules = $DB->get_records('tool_openapi_ip_rules', null, 'id ASC');

if ($rules) {
    $table = new html_table();
    $table->head = [
        get_string('iprange', 'tool_openapi'),
        get_string('ruledescription', 'tool_openapi'),
        get_string('allowedfunctions', 'tool_openapi'),
        get_string('status'),
        '',
    ];

    foreach ($rules as $rule) {
        $scope = $rule->allowedfunctions === null
            ? get_string('fullcatalog', 'tool_openapi')
            : implode(', ', array_filter(array_map('trim', explode("\n", $rule->allowedfunctions))));

        $editurl = new moodle_url('/admin/tool/openapi/pages/edit_ip_rule.php', ['id' => $rule->id]);
        $deleteurl = new moodle_url('/admin/tool/openapi/pages/delete_ip_rule.php', ['id' => $rule->id]);
        $actions = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-outline-secondary btn-sm mr-1'])
            . html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-outline-danger btn-sm']);

        $table->data[] = [
            $rule->iprange,
            $rule->description,
            $scope,
            $rule->enabled ? get_string('enabled', 'tool_openapi') : get_string('disabled', 'tool_openapi'),
            $actions,
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', get_string('noiprules', 'tool_openapi'));
}

echo $OUTPUT->heading(get_string('addiprule', 'tool_openapi'), 3);
$form->display();

echo $OUTPUT->footer();
