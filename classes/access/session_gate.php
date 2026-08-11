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
 * Authorizes a request carrying an active Moodle session.
 *
 * Checks isloggedin()/has_capability() directly rather than calling
 * require_login(): require_login() always redirects an unauthenticated
 * request to the login page, which is wrong twice over here -- this
 * endpoint answers with a JSON 403, never a redirect, and a gate tried
 * opportunistically as one of several OR'd options must be able to say
 * "not via this gate" and let the next one try, not force a navigation.
 *
 * tool/openapi:viewfullcatalog grants the full catalog. Plain
 * tool/openapi:view only grants a specific service's functions, named via
 * ?service= -- a capability is a boolean, it cannot carry an arbitrary
 * function list the way a token or IP rule row can, so for a person (as
 * opposed to a machine) the per-service recorte is the only restriction
 * that makes sense. See 02-arquitectura.md.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class session_gate implements access_gate {
    /**
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('tool_openapi', 'enablesessiongate');
    }

    /**
     * @param string|null $requestedservice
     * @return scope|null
     */
    public function authorize(?string $requestedservice): ?scope {
        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $context = \context_system::instance();
        if (has_capability('tool/openapi:viewfullcatalog', $context)) {
            return scope::full_catalog();
        }
        if (!has_capability('tool/openapi:view', $context) || $requestedservice === null) {
            return null;
        }

        try {
            return scope::limited_to(service_functions::for_shortname($requestedservice));
        } catch (\moodle_exception) {
            return null;
        }
    }
}
