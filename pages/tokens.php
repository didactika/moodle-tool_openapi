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
 * Create and revoke tool_openapi_tokens rows.
 *
 * Tokens are shown in plaintext exactly once, right after creation --
 * same Post/Redirect/Get pattern local_servicemanager uses for its own
 * service tokens (db/caches.php's 'newtoken', session-scoped, TTL 300s).
 * Only the hash is ever stored or shown again after this one request.
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
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/tokens.php'));
$PAGE->set_title(get_string('managetokens', 'tool_openapi'));
$PAGE->set_heading(get_string('managetokens', 'tool_openapi'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->js_call_amd('tool_openapi/copy_to_clipboard', 'init');

$form = new \tool_openapi\form\token_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/openapi/pages/tokens.php'));
} else if ($data = $form->get_data()) {
    $plaintext = random_string(32);

    $id = $DB->insert_record('tool_openapi_tokens', (object) [
        'name' => $data->name,
        'tokenhash' => hash('sha256', $plaintext),
        'allowedfunctions' => trim($data->allowedfunctions) === '' ? null : $data->allowedfunctions,
        'createdby' => $USER->id,
        'timecreated' => time(),
        'lastused' => null,
        'revoked' => 0,
    ]);

    \cache::make('tool_openapi', 'newtoken')->set($id, $plaintext);

    redirect(new moodle_url('/admin/tool/openapi/pages/tokens.php', ['newtoken' => $id]));
}

$newtokenid = optional_param('newtoken', 0, PARAM_INT);
$newtokenvalue = null;
if ($newtokenid) {
    $tokencache = \cache::make('tool_openapi', 'newtoken');
    $cached = $tokencache->get($newtokenid);
    if ($cached !== false) {
        $newtokenvalue = $cached;
        $tokencache->delete($newtokenid);
    }
}

echo $OUTPUT->header();

if ($newtokenvalue !== null) {
    echo html_writer::start_div('alert alert-success');
    echo html_writer::tag('p', get_string('tokencreatedonce', 'tool_openapi'));
    echo html_writer::start_div('input-group');
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'value' => $newtokenvalue,
        'readonly' => 'readonly',
    ]);
    echo html_writer::start_div('input-group-append');
    echo html_writer::tag(
        'button',
        get_string('copytoken', 'tool_openapi'),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary tool_openapi-copy-token',
            'data-token' => $newtokenvalue,
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

$tokens = $DB->get_records('tool_openapi_tokens', null, 'timecreated DESC');

if ($tokens) {
    $table = new html_table();
    $table->head = [
        get_string('tokenname', 'tool_openapi'),
        get_string('allowedfunctions', 'tool_openapi'),
        get_string('created', 'tool_openapi'),
        get_string('lastused', 'tool_openapi'),
        get_string('status'),
        '',
    ];

    foreach ($tokens as $token) {
        $scope = $token->allowedfunctions === null
            ? get_string('fullcatalog', 'tool_openapi')
            : implode(', ', array_filter(array_map('trim', explode("\n", $token->allowedfunctions))));

        $status = $token->revoked
            ? get_string('revoked', 'tool_openapi')
            : get_string('active', 'tool_openapi');

        $actions = '';
        if (!$token->revoked) {
            $revokeurl = new moodle_url('/admin/tool/openapi/pages/revoke_token.php', ['id' => $token->id]);
            $actions = html_writer::link(
                $revokeurl,
                get_string('revoke', 'tool_openapi'),
                ['class' => 'btn btn-outline-danger btn-sm']
            );
        }

        $table->data[] = [
            $token->name,
            $scope,
            userdate($token->timecreated),
            $token->lastused ? userdate($token->lastused) : get_string('never', 'tool_openapi'),
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', get_string('notokens', 'tool_openapi'));
}

echo $OUTPUT->heading(get_string('createtoken', 'tool_openapi'), 3);
$form->display();

echo $OUTPUT->footer();
