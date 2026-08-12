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
 * editing is ip_rules/edit.php, deleting is ip_rules/delete.php.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_ip_rules');

$rules = $DB->get_records('tool_openapi_ip_rules', null, 'id ASC');
$renderable = new \tool_openapi\output\ip_rules\index_page($rules);

echo $OUTPUT->header();
echo $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree(\tool_openapi\local\settings_nav::TAB_ACCESS));
echo $OUTPUT->render_from_template('tool_openapi/ip_rules/index', $renderable->export_for_template($OUTPUT));
echo $OUTPUT->footer();
