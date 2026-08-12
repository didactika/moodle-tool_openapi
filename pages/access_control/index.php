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
 * Turns each of the 4 access gates on or off.
 *
 * One row per gate, an enable/disable icon-link exactly like Moodle's own
 * admin/webservice/protocols.php uses for enabling a protocol -- not a
 * bespoke widget. The link is a full GET to access_control/toggle.php and
 * works with no JS at all; amd/src/gate_toggle.js progressively
 * upgrades it into an in-place AJAX toggle.
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
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/access_control/index.php'));
$PAGE->set_title(get_string('manageaccesscontrol', 'tool_openapi'));
$PAGE->set_heading(get_string('manageaccesscontrol', 'tool_openapi'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->js_call_amd('tool_openapi/gate_toggle', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree('tool_openapi_access_control'));

echo $OUTPUT->heading(get_string('accesscontrolheading', 'tool_openapi'), 3);
echo html_writer::tag('p', get_string('accesscontrolheading_desc', 'tool_openapi'));

$gates = [
    'enablesessiongate' => ['gatesession', 'gatesession_desc'],
    'enableipgate' => ['gateip', 'gateip_desc'],
    'enabletokengate' => ['gatetoken', 'gatetoken_desc'],
    'enablewstokengate' => ['gatewstoken', 'gatewstoken_desc'],
];

$table = new html_table();
$table->head = ['', '', get_string('status')];

foreach ($gates as $configkey => [$namestring, $descstring]) {
    $enabled = (bool) get_config('tool_openapi', $configkey);

    $toggleurl = new moodle_url('/admin/tool/openapi/pages/access_control/toggle.php', [
        'gate' => $configkey,
        'value' => $enabled ? 0 : 1,
        'sesskey' => sesskey(),
    ]);

    $icon = html_writer::empty_tag('img', [
        'src' => $enabled ? $OUTPUT->image_url('t/hide', 'moodle') : $OUTPUT->image_url('t/show', 'moodle'),
        'alt' => $enabled ? get_string('disablegate', 'tool_openapi') : get_string('enablegate', 'tool_openapi'),
        'class' => 'tool_openapi-gate-icon',
        'width' => 16,
        'height' => 16,
    ]);

    $toggle = html_writer::link($toggleurl, $icon, [
        'class' => 'tool_openapi-gate-toggle',
        'data-gate' => $configkey,
        'data-value' => $enabled ? '0' : '1',
        'data-icon-on' => $OUTPUT->image_url('t/hide', 'moodle')->out(false),
        'data-icon-off' => $OUTPUT->image_url('t/show', 'moodle')->out(false),
        'data-label-on' => get_string('disablegate', 'tool_openapi'),
        'data-label-off' => get_string('enablegate', 'tool_openapi'),
    ]);

    $status = html_writer::span(
        $enabled ? get_string('enabled', 'tool_openapi') : get_string('disabled', 'tool_openapi'),
        'tool_openapi-gate-status badge ' . ($enabled ? 'badge-success' : 'badge-secondary')
    );

    $table->data[] = [
        get_string($namestring, 'tool_openapi'),
        get_string($descstring, 'tool_openapi'),
        $status . ' ' . $toggle,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
