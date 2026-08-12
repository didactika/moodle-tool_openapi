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

namespace tool_openapi\event;

/**
 * A plugin token was created.
 *
 * The token value and its hash are deliberately never part of this event:
 * an event is written to the site log and handed to any observer, so
 * putting a credential in one would leak it to everything that can read
 * logs. Core takes the same care -- it nulls privatetoken on the record
 * snapshot before triggering webservice_token_created.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_created extends \core\event\base {
    /**
     * Event initialisation.
     */
    protected function init() {
        $this->data['objecttable'] = 'tool_openapi_tokens';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtokencreated', 'tool_openapi');
    }

    /**
     * Description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' created the OpenAPI token with id '$this->objectid'.";
    }

    /**
     * Where to go to see the affected record.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/openapi/pages/tokens/index.php');
    }

    /**
     * Backup/restore mapping.
     *
     * A site's own API credentials are not part of any course backup, so
     * there is nothing to map on restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'tool_openapi_tokens', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
