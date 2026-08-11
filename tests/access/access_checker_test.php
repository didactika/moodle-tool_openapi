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
 * Tests for access_checker, against fake gates -- the composition rules
 * (OR, closed by default) do not depend on any real gate's own logic, so
 * testing them against fakes keeps this independent of session/IP/token
 * setup, which each gate's own test already covers.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\access_checker
 */
final class access_checker_test extends \basic_testcase {
    /**
     * A gate that is enabled and always authorizes with the given scope.
     *
     * @param scope $scope
     * @return access_gate
     */
    private function always_authorizes(scope $scope): access_gate {
        return new class ($scope) implements access_gate {
            /**
             * Stores the scope this fake gate always returns.
             *
             * @param scope $scope
             */
            public function __construct(
                /** @var scope Scope returned by authorize(). */
                private readonly scope $scope,
            ) {
            }

            /**
             * Always enabled.
             *
             * @return bool
             */
            public function is_enabled(): bool {
                return true;
            }

            /**
             * Always authorizes with the stored scope.
             *
             * @param string|null $requestedservice
             * @return scope|null
             * @SuppressWarnings(PHPMD.UnusedFormalParameter)
             */
            public function authorize(?string $requestedservice): ?scope {
                return $this->scope;
            }
        };
    }

    /**
     * A gate that is enabled but never authorizes.
     *
     * @return access_gate
     */
    private function never_authorizes(): access_gate {
        return new class implements access_gate {
            /**
             * Always enabled.
             *
             * @return bool
             */
            public function is_enabled(): bool {
                return true;
            }

            /**
             * Never authorizes.
             *
             * @param string|null $requestedservice
             * @return scope|null
             * @SuppressWarnings(PHPMD.UnusedFormalParameter)
             */
            public function authorize(?string $requestedservice): ?scope {
                return null;
            }
        };
    }

    /**
     * A gate that would authorize, but is switched off.
     *
     * @return access_gate
     */
    private function disabled_but_would_authorize(): access_gate {
        return new class implements access_gate {
            /**
             * Always disabled.
             *
             * @return bool
             */
            public function is_enabled(): bool {
                return false;
            }

            /**
             * Would authorize with the full catalog if ever consulted.
             *
             * @param string|null $requestedservice
             * @return scope|null
             * @SuppressWarnings(PHPMD.UnusedFormalParameter)
             */
            public function authorize(?string $requestedservice): ?scope {
                return scope::full_catalog();
            }
        };
    }

    /**
     * With no gates at all, the request is denied -- the most important
     * security property of this plugin, per 03-control-de-acceso.md.
     */
    public function test_no_gates_denies_the_request(): void {
        $checker = new access_checker([]);

        $this->assertNull($checker->authorize(null));
    }

    /**
     * With gates that exist but are all disabled, the request is still
     * denied -- a freshly installed site, before any method is turned on.
     */
    public function test_only_disabled_gates_denies_the_request(): void {
        $checker = new access_checker([$this->disabled_but_would_authorize()]);

        $this->assertNull($checker->authorize(null));
    }

    /**
     * The first enabled gate to authorize wins; later gates are not tried.
     */
    public function test_first_authorizing_enabled_gate_wins(): void {
        $checker = new access_checker([
            $this->never_authorizes(),
            $this->always_authorizes(scope::limited_to(['core_course_get_courses'])),
            $this->always_authorizes(scope::full_catalog()),
        ]);

        $scope = $checker->authorize(null);

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * A gate that would authorize is skipped entirely while disabled.
     */
    public function test_disabled_gate_is_never_consulted_even_if_others_deny(): void {
        $checker = new access_checker([
            $this->disabled_but_would_authorize(),
            $this->never_authorizes(),
        ]);

        $this->assertNull($checker->authorize(null));
    }
}
