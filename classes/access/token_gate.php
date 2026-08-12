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
 * Authorizes a request bearing a plugin-issued token.
 *
 * Read via the Authorization: Bearer header, never a ?token= query
 * parameter -- a query parameter ends up in web server access logs,
 * browser history and proxy tooling, a header does not. Only the
 * SHA-256 of the token is ever stored or compared, never the plaintext.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class token_gate implements access_gate {
    /**
     * Whether the plugin-token gate is turned on.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('tool_openapi', 'enabletokengate');
    }

    /**
     * Authorizes via a plugin-issued Bearer token.
     *
     * @param string|null $requestedservice Unused: a plugin token's scope is its own row, not the request.
     * @return scope|null
     */
    public function authorize(?string $requestedservice): ?scope {
        global $DB;

        $token = self::bearer_token();
        if ($token === null) {
            return null;
        }

        $record = $DB->get_record(
            'tool_openapi_tokens',
            ['tokenhash' => hash('sha256', $token), 'revoked' => 0],
            '*',
            IGNORE_MISSING
        );
        if (!$record) {
            return null;
        }

        $DB->set_field('tool_openapi_tokens', 'lastused', time(), ['id' => $record->id]);

        return scope::from_allowedfunctions_field($record->allowedfunctions);
    }

    /**
     * The token from an "Authorization: Bearer <token>" header, if present.
     *
     * @return string|null
     */
    private static function bearer_token(): ?string {
        $header = self::authorization_header();
        if ($header === null || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($header, strlen('Bearer ')));

        return $token === '' ? null : $token;
    }

    /**
     * The raw Authorization header value, if any.
     *
     * @return string|null
     */
    private static function authorization_header(): ?string {
        if (function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return $value;
                }
            }
        }

        return $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    }
}
