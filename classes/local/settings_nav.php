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
 * The tab bar shared by every admin page of this plugin.
 *
 * One tabtree definition, reused by settings.php (embedded via
 * admin_setting_description, the same raw-HTML mechanism already used for
 * the cache purge button) and by the three plain pages/*.php screens
 * (echoed directly after $OUTPUT->header()), so the four pages of the
 * plugin always agree on what the other three are called and where they
 * live.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_nav {
    /**
     * Builds the tab bar, marking one tab as the current page.
     *
     * @param string $selected One of tool_openapi_settings, tool_openapi_access_control,
     *                          tool_openapi_tokens, tool_openapi_ip_rules.
     * @return \tabtree
     */
    public static function tabtree(string $selected): \tabtree {
        $tabs = [
            new \tabobject(
                'tool_openapi_settings',
                new \moodle_url('/admin/settings.php', ['section' => 'tool_openapi']),
                get_string('cacheheading', 'tool_openapi')
            ),
            new \tabobject(
                'tool_openapi_access_control',
                new \moodle_url('/admin/tool/openapi/pages/access_control/index.php'),
                get_string('manageaccesscontrol', 'tool_openapi')
            ),
            new \tabobject(
                'tool_openapi_tokens',
                new \moodle_url('/admin/tool/openapi/pages/tokens/index.php'),
                get_string('managetokens', 'tool_openapi')
            ),
            new \tabobject(
                'tool_openapi_ip_rules',
                new \moodle_url('/admin/tool/openapi/pages/ip_rules/index.php'),
                get_string('manageiprules', 'tool_openapi')
            ),
        ];

        return new \tabtree($tabs, $selected);
    }
}
