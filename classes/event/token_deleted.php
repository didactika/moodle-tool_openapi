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
 * A plugin token was deleted.
 *
 * Deleting a token removes the row for good, so this event is the only
 * lasting record that the token ever existed -- which is the whole reason
 * the table no longer keeps a "revoked" flag. Callers must
 * add_record_snapshot() before triggering: once the row is gone,
 * get_record_snapshot() has no way to fetch it for an observer.
 *
 * As with token_created, neither the token nor its hash goes into the
 * event -- see that class's docblock.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_deleted extends \core\event\base {
    /**
     * Event initialisation.
     */
    protected function init() {
        $this->data['objecttable'] = 'tool_openapi_tokens';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtokendeleted', 'tool_openapi');
    }

    /**
     * Description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the OpenAPI token with id '$this->objectid'.";
    }

    /**
     * Where to go after the fact.
     *
     * The token list, not the token: the record this event is about no
     * longer exists.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/openapi/pages/tokens/index.php');
    }

    /**
     * Backup/restore mapping.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'tool_openapi_tokens', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
