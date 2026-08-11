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
}
