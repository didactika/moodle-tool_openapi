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
 * Registered under the tokens section rather than one of its own, then
 * re-pointed at this script with an extra breadcrumb entry -- the same
 * thing core's admin/tool/filetypes/edit.php does, so the page keeps the
 * site name as its heading and reads as "... / Tokens / Create token"
 * instead of losing its place in the admin tree.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_tokens');

$pagetitle = get_string('createtoken', 'tool_openapi');
$tokensurl = new moodle_url('/admin/tool/openapi/pages/tokens/index.php');

$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/tokens/create.php'));
$PAGE->navbar->add($pagetitle);
$PAGE->set_title($pagetitle);

$form = new \tool_openapi\form\token_form();

if ($form->is_cancelled()) {
    redirect($tokensurl);
} else if ($data = $form->get_data()) {
    $plaintext = random_string(32);
    $restrictfunctions = !empty($data->restrictfunctions) && !empty($data->allowedfunctions);
    $restrictip = !empty($data->restrictip) && trim($data->iprestriction) !== '';

    $id = $DB->insert_record('tool_openapi_tokens', (object) [
        'name' => $data->name,
        'tokenhash' => hash('sha256', $plaintext),
        'allowedfunctions' => $restrictfunctions ? implode("\n", $data->allowedfunctions) : null,
        'iprestriction' => $restrictip ? $data->iprestriction : null,
        'createdby' => $USER->id,
        'timecreated' => time(),
        'lastused' => null,
    ]);

    \tool_openapi\event\token_created::create([
        'objectid' => $id,
        'context' => \context_system::instance(),
        'other' => ['name' => $data->name],
    ])->trigger();

    // The plaintext never touches the database, only this session-scoped
    // cache, so index.php can show it exactly once and then drop it. Just
    // the secret, nothing else: that cache is declared simpledata, which
    // means scalars only, and the token's name is already on the row
    // index.php is about to load anyway.
    \cache::make('tool_openapi', 'newtoken')->set($id, $plaintext);

    redirect(new moodle_url('/admin/tool/openapi/pages/tokens/index.php', ['newtoken' => $id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
