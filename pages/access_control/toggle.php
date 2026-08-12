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
 * Turns one access gate on or off.
 *
 * Same one-shot action pattern as pages/purge_cache.php (require_sesskey,
 * no confirmation screen -- a gate toggle is trivially reversible by
 * clicking it again). Serves both the plain-link fallback (redirect back
 * to access_control/index.php) and amd/src/gate_toggle.js's AJAX call
 * (ajax=1, JSON body instead of a redirect) from the same script, so
 * there is exactly one place that decides whether a gate key is real.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');

require_login();
require_capability('tool/openapi:manage', \context_system::instance());
require_sesskey();

$gate = required_param('gate', PARAM_ALPHANUMEXT);
$value = required_param('value', PARAM_BOOL);
$isajax = optional_param('ajax', 0, PARAM_BOOL);

$allowedgates = ['enablesessiongate', 'enableipgate', 'enabletokengate', 'enablewstokengate'];
if (!in_array($gate, $allowedgates, true)) {
    throw new \invalid_parameter_exception('Unknown gate: ' . $gate);
}

set_config($gate, $value ? 1 : 0, 'tool_openapi');

if ($isajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['enabled' => (bool) $value]);
    exit;
}

redirect(
    new moodle_url('/admin/tool/openapi/pages/access_control/index.php'),
    get_string('changessaved'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
