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
 * admin_setting_configiplist gives the core for free for a single global
 * admin setting -- not usable here, since each row of
 * tool_openapi_ip_rules is validated as a moodleform field bound to a
 * database table, not a site-wide config value.
 *
 * A structural port of address_in_subnet() (lib/moodlelib.php): the same
 * three notations (bare address, CIDR, range, and the partial-address
 * shorthand), the same branch decisions, but every actual address-format
 * check is delegated to cleanremoteaddr() -- also core, also called
 * directly, never reimplemented -- instead of re-deriving IPv4/IPv6
 * parsing by hand. That keeps the risk surface of this port to "did we
 * copy the control flow right", not "did we also get hex/bitmask parsing
 * right", and was verified by running both this class and the real
 * address_in_subnet() inside an actual Moodle 4.5 install (moodle-docker)
 * against the same table of valid and invalid entries for every notation.
 *
 * One faithfully-ported core quirk: address_in_subnet()'s partial-IPv6
 * branch rejoins the address with "." instead of ":" after trimming a
 * trailing ":" (compare the IPv4 partial branch, which rejoins with "."
 * correctly for IPv4). That is very likely a bug in core, not intentional
 * -- but this validator's job is to predict what the real function
 * accepts, not what it should accept, so it is replicated here rather
 * than silently corrected. In practice this only ever rejects entries
 * with that shape; it can never make this validator wrongly accept
 * something address_in_subnet() would reject.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ip_range_validator {
    /**
     * Whether every comma-separated entry is one address_in_subnet() accepts.
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
     * Dispatch one entry to the notation address_in_subnet() would parse it as.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_entry(string $entry): bool {
        if (strpos($entry, '/') !== false) {
            return self::is_valid_cidr($entry);
        }
        if (strpos($entry, '-') !== false) {
            return self::is_valid_range($entry);
        }

        return strpos($entry, ':') !== false
            ? self::is_valid_partial_ipv6($entry)
            : self::is_valid_partial_ipv4($entry);
    }

    /**
     * "xxx.xxx.xxx.xxx/nn" or an IPv6 equivalent.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_cidr(string $entry): bool {
        $parts = explode('/', $entry);

        $ip = cleanremoteaddr(trim($parts[0]), false);
        if ($ip === null) {
            return false;
        }

        $mask = trim($parts[1] ?? '');
        if (!self::is_number($mask)) {
            return false;
        }

        $maxbits = strpos($ip, ':') !== false ? 128 : 32;

        return (int) $mask >= 0 && (int) $mask <= $maxbits;
    }

    /**
     * "xxx.xxx.xxx.xxx-yyy" or an IPv6 equivalent: a range across the last
     * octet (IPv4) or hextet (IPv6) only.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_range(string $entry): bool {
        $parts = explode('-', $entry);
        if (count($parts) !== 2) {
            return false;
        }

        $ipstart = cleanremoteaddr(trim($parts[0]), false);
        if ($ipstart === null) {
            return false;
        }

        if (strpos($entry, ':') !== false) {
            $hextets = explode(':', $ipstart);
            array_pop($hextets);
            $hextets[] = trim($parts[1]);

            return cleanremoteaddr(implode(':', $hextets), false) !== null;
        }

        $octets = explode('.', $ipstart);
        $octets[3] = trim($parts[1]);

        return cleanremoteaddr(implode('.', $octets), false) !== null;
    }

    /**
     * "xxx.xxx" or "xxx.xxx.": a whole address, or a prefix padded with
     * zeros and re-checked as CIDR, exactly like address_in_subnet()'s own
     * recursive call.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_partial_ipv4(string $entry): bool {
        $parts = explode('.', $entry);
        $count = count($parts);
        if ($parts[$count - 1] === '') {
            array_pop($parts);
            $count--;
            $entry = implode('.', $parts);
        }

        if ($count === 4) {
            return cleanremoteaddr($entry, false) !== null;
        }
        if ($count > 4) {
            return false;
        }

        $zeros = array_fill(0, 4 - $count, '0');

        return cleanremoteaddr($entry . '.' . implode('.', $zeros), false) !== null;
    }

    /**
     * The IPv6 equivalent of is_valid_partial_ipv4() -- see the class
     * docblock for the "." rejoin quirk this deliberately replicates.
     *
     * @param string $entry
     * @return bool
     */
    private static function is_valid_partial_ipv6(string $entry): bool {
        $parts = explode(':', $entry);
        $count = count($parts);
        if ($parts[$count - 1] === '') {
            array_pop($parts);
            $count--;
            $entry = implode('.', $parts);
        }

        if (cleanremoteaddr($entry, false) !== null) {
            return true;
        }
        if ($count > 8) {
            return false;
        }

        $zeros = array_fill(0, 8 - $count, '0');

        return cleanremoteaddr($entry . ':' . implode(':', $zeros), false) !== null;
    }

    /**
     * Same semantics as core's is_number(): a string is a number only if it
     * is the exact canonical decimal form of an int (no leading zeros, no
     * leading "+", no decimals) -- confirmed by reading is_number() itself
     * (lib/moodlelib.php), not assumed from its name.
     *
     * @param string $value
     * @return bool
     */
    private static function is_number(string $value): bool {
        return (string) (int) $value === $value;
    }
}
