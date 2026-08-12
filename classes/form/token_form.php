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

namespace tool_openapi\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating a new tool_openapi_tokens row.
 *
 * Tokens are never edited once created -- only revoked (see
 * pages/revoke_token.php) -- so this form only ever runs in create mode,
 * unlike ip_rule_form which also handles editing an existing row.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('tokenname', 'tool_openapi'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement(
            'textarea',
            'allowedfunctions',
            get_string('allowedfunctions', 'tool_openapi'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('allowedfunctions', PARAM_RAW_TRIMMED);
        $mform->addElement('static', 'allowedfunctions_desc', '', get_string('allowedfunctions_desc', 'tool_openapi'));

        $this->add_action_buttons(true, get_string('createtoken', 'tool_openapi'));
    }
}
