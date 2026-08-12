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
 * Two tabs, matching the two things an administrator does here: decide who
 * may read the catalog (access control), and get at the catalog itself
 * (documentation). Tokens and IP rules are not tabs of their own -- they
 * configure one access method each and are reached from that method's row
 * in pages/access_control, so they render this same bar with the access
 * control tab still selected rather than dropping the user somewhere with
 * no visible context.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings_nav {
    /** Tab id for the access control page, and for its sub-pages. */
    public const TAB_ACCESS = 'tool_openapi_access';

    /** Tab id for the documentation page. */
    public const TAB_DOCS = 'tool_openapi_docs';

    /**
     * Builds the tab bar, marking one tab as the current page.
     *
     * @param string $selected One of the TAB_* constants.
     * @return \tabtree
     */
    public static function tabtree(string $selected): \tabtree {
        $tabs = [
            new \tabobject(
                self::TAB_ACCESS,
                new \moodle_url('/admin/tool/openapi/pages/access_control/index.php'),
                get_string('manageaccesscontrol', 'tool_openapi')
            ),
            new \tabobject(
                self::TAB_DOCS,
                new \moodle_url('/admin/tool/openapi/pages/documentation/index.php'),
                get_string('managedocumentation', 'tool_openapi')
            ),
        ];

        return new \tabtree($tabs, $selected);
    }
}
