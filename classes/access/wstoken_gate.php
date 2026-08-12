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

namespace tool_openapi\access;

use tool_openapi\local\service_functions;

/**
 * Authorizes a request bearing an existing Moodle webservice token
 * (external_tokens), reused for reading documentation instead of issuing a
 * second secret to an integrator that already has one.
 *
 * The scope is always that token's own service functions, computed live
 * from external_services_functions -- never the full catalog, not even for
 * an administrator's token, and never stored on any tool_openapi table.
 * Enabling this gate adds no new authorization surface: anyone who could
 * already obtain a legitimate wstoken already had access to call those
 * functions; seeing them documented gives nothing they did not already
 * have.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wstoken_gate implements access_gate {
    /**
     * Whether the wstoken-reuse gate is turned on.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('tool_openapi', 'enablewstokengate');
    }

    /**
     * Authorizes via an existing Moodle webservice token.
     *
     * @param string|null $requestedservice Unused: the scope is always the token's own service.
     * @return scope|null
     */
    public function authorize(?string $requestedservice): ?scope {
        global $DB;

        $token = optional_param('wstoken', '', PARAM_ALPHANUM);
        if ($token === '') {
            return null;
        }

        $record = $DB->get_record('external_tokens', ['token' => $token], '*', IGNORE_MISSING);
        if (!$record) {
            return null;
        }
        if (!empty($record->validuntil) && $record->validuntil < time()) {
            return null;
        }

        $service = $DB->get_record(
            'external_services',
            ['id' => $record->externalserviceid, 'enabled' => 1],
            'id',
            IGNORE_MISSING
        );
        if (!$service) {
            return null;
        }

        return scope::limited_to(service_functions::for_id((int) $service->id));
    }
}
