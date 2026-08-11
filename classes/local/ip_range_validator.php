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
 * Format validation for tool_openapi_ip_rules.iprange, the check that
 * admin_setting_configiplist gives the core for free -- see
 * 02-arquitectura.md's note on why that setting type could not be reused.
 *
 * Deliberately covers only the two most common of the three notations
 * address_in_subnet() (lib/moodlelib.php) accepts: a bare address, and
 * CIDR notation (xxx.xxx.xxx.xxx/nn). It does not validate the range
 * (xxx.xxx.xxx.xxx-yyy) or partial-address (xxx.xxx.) notations that
 * function also accepts -- replicating its full parsing (three notations,
 * IPv4 and IPv6, without a real PHP install to test the port against) is
 * a correctness risk this class avoids by staying honestly narrower than
 * the function it front-ends: ip_gate still matches every notation
 * address_in_subnet() supports at request time, this validator just does
 * not give upfront form feedback for the two rarer ones, rather than
 * silently mis-validating them.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ip_range_validator {
    /**
     * Whether every comma-separated entry is a valid bare address or CIDR range.
     *
     * @param string $iprange
     * @return bool
     */
    public static function is_valid(string $iprange): bool {
        $entries = array_map('trim', explode(',', $iprange));

        foreach ($entries as $entry) {
            if ($entry === '' || !self::is_valid_entry($entry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether one entry is a valid bare address or CIDR range.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_entry(string $entry): bool {
        if (strpos($entry, '/') === false) {
            return filter_var($entry, FILTER_VALIDATE_IP) !== false;
        }

        [$address, $bits] = explode('/', $entry, 2);
        if (!ctype_digit($bits)) {
            return false;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return (int) $bits <= 32;
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return (int) $bits <= 128;
        }

        return false;
    }
}
