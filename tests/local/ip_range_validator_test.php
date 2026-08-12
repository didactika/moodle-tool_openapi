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
 * Tests for ip_range_validator.
 *
 * Every case here (and quite a few more) was cross-checked against a real
 * Moodle 4.5 install's actual address_in_subnet() before this class was
 * written -- for every "valid" case, a constructed probe address was
 * confirmed to really match via that real function, not just accepted by
 * this port. See the class's own docblock.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\local\ip_range_validator
 */
final class ip_range_validator_test extends \basic_testcase {
    /**
     * A bare IPv4 or IPv6 address is valid on its own.
     */
    public function test_bare_address_is_valid(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.1'));
        $this->assertTrue(ip_range_validator::is_valid('2001:db8::1'));
    }

    /**
     * CIDR notation is valid when the mask fits the address family.
     */
    public function test_cidr_notation_is_valid(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.0/24'));
        $this->assertTrue(ip_range_validator::is_valid('2001:db8::/32'));
    }

    /**
     * A mask past the address family's own bit width is rejected.
     */
    public function test_cidr_mask_out_of_range_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.0/33'));
        $this->assertFalse(ip_range_validator::is_valid('2001:db8::/129'));
    }

    /**
     * Several comma-separated entries are valid only if every one is.
     */
    public function test_comma_separated_entries_all_must_be_valid(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.1, 198.51.100.0/24'));
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.1, not-an-address'));
    }

    /**
     * Garbage input, blank entries and a non-numeric mask are all rejected.
     */
    public function test_malformed_input_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('not-an-address'));
        $this->assertFalse(ip_range_validator::is_valid(''));
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.1,'));
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.0/abc'));
    }

    /**
     * A leading-zero mask is rejected: real is_number() (lib/moodlelib.php)
     * requires the exact canonical decimal form, "08" is not "8".
     */
    public function test_cidr_mask_with_leading_zero_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.0/08'));
    }

    /**
     * A double CIDR mask is valid: real address_in_subnet() splits on every
     * "/" but only ever reads the first two parts via list(), silently
     * ignoring anything past the second.
     */
    public function test_cidr_with_extra_slash_segment_ignores_the_extra_segment(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.0/24/1'));
    }

    /**
     * A range across the last octet or hextet only, IPv4 and IPv6.
     */
    public function test_range_notation_is_valid(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.10-20'));
        $this->assertTrue(ip_range_validator::is_valid('2001:db8::1-a'));
    }

    /**
     * A range needs exactly two "-"-separated parts, and a real start address.
     */
    public function test_range_notation_malformed_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.10-20-30'));
        $this->assertFalse(ip_range_validator::is_valid('bogus-20'));
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.999-1000'));
    }

    /**
     * A partial address (1-3 octets, or 1-7 hextets), with or without a
     * trailing separator, is valid -- it is padded with zeros and checked
     * as a CIDR range, same as address_in_subnet() does internally.
     */
    public function test_partial_address_is_valid(): void {
        $this->assertTrue(ip_range_validator::is_valid('192.0.2'));
        $this->assertTrue(ip_range_validator::is_valid('192.0.2.'));
        $this->assertTrue(ip_range_validator::is_valid('192'));
        $this->assertTrue(ip_range_validator::is_valid('2001:db8'));
    }

    /**
     * Too many octets/hextets, or garbage ones, are rejected.
     */
    public function test_partial_address_malformed_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('192.0.2.1.5'));
        $this->assertFalse(ip_range_validator::is_valid('999.0.2'));
        $this->assertFalse(ip_range_validator::is_valid('2001:xyz'));
    }

    /**
     * A real core quirk: address_in_subnet()'s IPv6 partial branch rejoins
     * with "." instead of ":" after trimming a trailing ":", so an entry
     * that looks like a valid partial address with a trailing colon is
     * actually never matched by the real function -- and must not be
     * accepted here either. See the class's own docblock.
     */
    public function test_ipv6_trailing_colon_quirk_is_invalid(): void {
        $this->assertFalse(ip_range_validator::is_valid('2001:db8:'));
    }
}
