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
 * Creates a new tool_openapi_tokens row.
 *
 * Split out of tokens/index.php so that page can stay a plain list --
 * same create-here, list-there split as pages/ip_rules/create.php.
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

$tokensurl = new moodle_url('/admin/tool/openapi/pages/tokens/index.php');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/tokens/create.php'));
$PAGE->set_title(get_string('createtoken', 'tool_openapi'));
$PAGE->set_heading(get_string('createtoken', 'tool_openapi'));
$PAGE->set_pagelayout('admin');

$form = new \tool_openapi\form\token_form();

if ($form->is_cancelled()) {
    redirect($tokensurl);
} else if ($data = $form->get_data()) {
    $plaintext = random_string(32);
    $restricted = !empty($data->restrictfunctions) && !empty($data->allowedfunctions);

    $id = $DB->insert_record('tool_openapi_tokens', (object) [
        'name' => $data->name,
        'tokenhash' => hash('sha256', $plaintext),
        'allowedfunctions' => $restricted ? implode("\n", $data->allowedfunctions) : null,
        'iprestriction' => trim($data->iprestriction) === '' ? null : $data->iprestriction,
        'createdby' => $USER->id,
        'timecreated' => time(),
        'lastused' => null,
        'revoked' => 0,
    ]);

    \cache::make('tool_openapi', 'newtoken')->set($id, $plaintext);

    redirect(new moodle_url('/admin/tool/openapi/pages/tokens/index.php', ['newtoken' => $id]));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
