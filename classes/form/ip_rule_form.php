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

use tool_openapi\local\ip_range_validator;
use tool_openapi\local\service_functions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating or editing a tool_openapi_ip_rules row.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ip_rule_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'iprange', get_string('iprange', 'tool_openapi'));
        $mform->setType('iprange', PARAM_RAW_TRIMMED);
        $mform->addRule('iprange', null, 'required');
        $mform->addElement('static', 'iprange_desc', '', get_string('iprange_desc', 'tool_openapi'));

        $mform->addElement('text', 'description', get_string('ruledescription', 'tool_openapi'));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'restrictfunctions', get_string('restrictfunctions', 'tool_openapi'), '', null, [0, 1]);
        $mform->setDefault('restrictfunctions', 0);
        $mform->addHelpButton('restrictfunctions', 'restrictfunctions', 'tool_openapi');

        $mform->addElement(
            'autocomplete',
            'allowedfunctions',
            get_string('allowedfunctions', 'tool_openapi'),
            service_functions::all(),
            ['multiple' => true, 'tags' => false]
        );
        $mform->setType('allowedfunctions', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('allowedfunctions', 'allowedfunctions', 'tool_openapi');
        $mform->hideIf('allowedfunctions', 'restrictfunctions', 'notchecked');

        $mform->addElement('advcheckbox', 'enabled', get_string('ruleenabled', 'tool_openapi'), '', null, [0, 1]);
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons(true, get_string('saverule', 'tool_openapi'));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['iprange']) && !ip_range_validator::is_valid($data['iprange'])) {
            $errors['iprange'] = get_string('invalidiprange', 'tool_openapi');
        }

        if (!empty($data['restrictfunctions']) && empty($data['allowedfunctions'])) {
            $errors['allowedfunctions'] = get_string('emptyallowedfunctions', 'tool_openapi');
        }

        return $errors;
    }
}
