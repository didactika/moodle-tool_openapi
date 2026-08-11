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
 * Composes every access_gate with OR: the request is authorized if any
 * enabled gate accepts it.
 *
 * Closed by default -- with nothing enabled (a freshly installed site,
 * before an administrator turns any method on), every gate's is_enabled()
 * is false, so authorize() always returns null. There is no "default"
 * method left active, on purpose: see 03-control-de-acceso.md.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class access_checker {
    /**
     * Creates a checker from an explicit list of gates.
     *
     * @param access_gate[] $gates
     */
    public function __construct(
        /** @var access_gate[] Gates tried in order; the first enabled, authorizing gate wins. */
        private readonly array $gates,
    ) {
    }

    /**
     * The real gates, in the order 03-control-de-acceso.md lists them.
     *
     * @return self
     */
    public static function default(): self {
        return new self([
            new session_gate(),
            new ip_gate(),
            new token_gate(),
            new wstoken_gate(),
        ]);
    }

    /**
     * Authorizes a request by trying each enabled gate in order.
     *
     * @param string|null $requestedservice The ?service= query param, if any.
     * @return scope|null Null if no enabled gate authorizes the request.
     */
    public function authorize(?string $requestedservice): ?scope {
        foreach ($this->gates as $gate) {
            if (!$gate->is_enabled()) {
                continue;
            }

            $scope = $gate->authorize($requestedservice);
            if ($scope !== null) {
                return $scope;
            }
        }

        return null;
    }
}
