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
 * Lists tool_openapi_ip_rules rows. Creating one is ip_rules/create.php,
 * editing is ip_rules/edit.php, deleting is ip_rules/delete.php -- this
 * page only ever lists.
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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/ip_rules/index.php'));
$PAGE->set_title(get_string('manageiprules', 'tool_openapi'));
$PAGE->set_heading(get_string('manageiprules', 'tool_openapi'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree('tool_openapi_ip_rules'));

$createurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/create.php');
echo html_writer::div(
    html_writer::link($createurl, get_string('addiprule', 'tool_openapi'), ['class' => 'btn btn-primary']),
    'd-flex justify-content-end mb-3'
);

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

        $editurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/edit.php', ['id' => $rule->id]);
        $deleteurl = new moodle_url('/admin/tool/openapi/pages/ip_rules/delete.php', ['id' => $rule->id]);
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
    echo html_writer::div(
        html_writer::tag('p', get_string('noiprules', 'tool_openapi'), ['class' => 'mb-0']),
        'text-center text-muted py-5 border rounded'
    );
}

echo $OUTPUT->footer();
