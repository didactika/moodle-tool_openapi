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

/**
 * Admin tree registration for tool_openapi
 *
 * Every page of this plugin is registered here, and that registration is
 * load-bearing rather than cosmetic: admin_externalpage_setup() refuses to
 * run for a section that is not in the tree (it throws 'sectionerror'), and
 * it is what gives each page its site-name heading, its title and its
 * breadcrumb. Pages that skip it end up with no breadcrumb and their own
 * heading where the site name should be.
 *
 * Tokens and IP rules are registered hidden (the 5th admin_externalpage
 * argument): they are reached from the cog on their access method in
 * pages/access_control, not from the admin tree, but they still need to be
 * locatable for admin_externalpage_setup() to work. Same approach core
 * takes for its own webservice service pages, see admin/settings/server.php.
 *
 * There is no admin_settingpage: every gate is stored via set_config() from
 * pages/access_control/toggle.php, so the plugin has no setting an
 * administrator edits through a settings form.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_category('tool_openapi', get_string('pluginname', 'tool_openapi')));

    $ADMIN->add('tool_openapi', new admin_externalpage(
        'tool_openapi_access',
        get_string('manageaccesscontrol', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/access_control/index.php'),
        'tool/openapi:manage'
    ));

    $ADMIN->add('tool_openapi', new admin_externalpage(
        'tool_openapi_docs',
        get_string('managedocumentation', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/documentation/index.php'),
        'tool/openapi:manage'
    ));

    $ADMIN->add('tool_openapi', new admin_externalpage(
        'tool_openapi_tokens',
        get_string('managetokens', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/tokens/index.php'),
        'tool/openapi:manage',
        true
    ));

    $ADMIN->add('tool_openapi', new admin_externalpage(
        'tool_openapi_ip_rules',
        get_string('manageiprules', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/ip_rules/index.php'),
        'tool/openapi:manage',
        true
    ));
}
