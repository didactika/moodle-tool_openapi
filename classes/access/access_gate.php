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
 * One way to authorize a request for the OpenAPI document.
 *
 * access_checker tries every enabled gate and accepts the first one that
 * authorizes -- see that class for the OR composition and the
 * closed-by-default rule.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface access_gate {
    /**
     * Whether an administrator has switched this method on. A gate that
     * is not enabled is never consulted, regardless of the request.
     *
     * @return bool
     */
    public function is_enabled(): bool;

    /**
     * Decide whether this gate authorizes the current request.
     *
     * @param string|null $requestedservice The ?service= query param, if any.
     * @return scope|null Null if this gate does not authorize the request.
     */
    public function authorize(?string $requestedservice): ?scope;
}
