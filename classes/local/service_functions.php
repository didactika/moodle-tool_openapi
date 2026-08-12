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

namespace tool_openapi\local;

/**
 * Which functions belong to an external service -- shared by
 * generator\service_filter (the per-service document filter) and
 * access\session_gate (a :view-only session must name a service to see
 * anything), so the lookup and its "unknown service" error live in one
 * place instead of two.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class service_functions {
    /**
     * Function names assigned to a service, looked up by shortname.
     *
     * @param string $serviceshortname
     * @return string[]
     * @throws \moodle_exception If no service has that shortname.
     */
    public static function for_shortname(string $serviceshortname): array {
        global $DB;

        $service = $DB->get_record(
            'external_services',
            ['shortname' => $serviceshortname],
            'id',
            IGNORE_MISSING
        );
        if (!$service) {
            throw new \moodle_exception('invalidservice', 'tool_openapi', '', $serviceshortname);
        }

        return self::for_id((int) $service->id);
    }

    /**
     * Function names assigned to a service, looked up by id.
     *
     * @param int $serviceid
     * @return string[]
     */
    public static function for_id(int $serviceid): array {
        global $DB;

        return $DB->get_fieldset_select(
            'external_services_functions',
            'functionname',
            'externalserviceid = ?',
            [$serviceid]
        );
    }

    /**
     * Every registered external function's name, for a search-select field.
     *
     * The site-wide registry (external_functions.name), not any one
     * service's subset -- a token or IP rule's own allowed-functions list
     * is independent of which external service (if any) a function also
     * belongs to.
     *
     * @return string[] Function name => function name, alphabetically sorted.
     */
    public static function all(): array {
        global $DB;

        $names = $DB->get_fieldset_select('external_functions', 'name', '');
        sort($names);

        return array_combine($names, $names);
    }
}
