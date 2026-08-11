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

/**
 * Authorizes a request whose remote address matches an enabled row in
 * tool_openapi_ip_rules.
 *
 * Uses getremoteaddr(), never $_SERVER['REMOTE_ADDR']) directly -- it
 * already knows how to honour (or ignore, per $CFG) this site's reverse
 * proxy headers, same as every other place in Moodle core where a
 * client's IP matters for security.
 *
 * address_in_subnet() (lib/moodlelib.php) accepts a single IP or a CIDR
 * range in one string, confirmed by reading its real source -- no
 * bespoke parsing needed here.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ip_gate implements access_gate {
    /**
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('tool_openapi', 'enableipgate');
    }

    /**
     * @param string|null $requestedservice Unused: an IP rule's scope is its own row, not the request.
     * @return scope|null
     */
    public function authorize(?string $requestedservice): ?scope {
        global $DB;

        $remoteaddr = getremoteaddr('');
        if ($remoteaddr === '') {
            return null;
        }

        $rules = $DB->get_records('tool_openapi_ip_rules', ['enabled' => 1], 'id ASC');
        foreach ($rules as $rule) {
            if (address_in_subnet($remoteaddr, $rule->iprange)) {
                return scope::from_allowedfunctions_field($rule->allowedfunctions);
            }
        }

        return null;
    }
}
