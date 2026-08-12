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
 * Settings for tool_openapi
 *
 * Registered under the 'tools' admin category, not a category of its own --
 * an admin tool gets that category for free, unlike a local plugin. See
 * 02-arquitectura.md.
 *
 * The settings page itself is deliberately minimal so far: just the cache
 * purge action fase 4 needs, plus links to the tokens/IP rules admin
 * pages. The access-method toggles (enablesessiongate and friends) and
 * the viewer/Swagger UI page are fase 5's own scope -- see
 * 06-comandos-pendientes.md for why that split is not a half-finished
 * vertical slice: every gate is already closed by default via get_config()
 * returning falsy when unset, so nothing here is required for the access
 * engine or the endpoint to behave correctly, only for an administrator to
 * turn a method on from the UI instead of by hand.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_externalpage(
        'tool_openapi_tokens',
        get_string('managetokens', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/tokens.php'),
        'tool/openapi:manage'
    ));

    $ADMIN->add('tools', new admin_externalpage(
        'tool_openapi_ip_rules',
        get_string('manageiprules', 'tool_openapi'),
        new moodle_url('/admin/tool/openapi/pages/ip_rules.php'),
        'tool/openapi:manage'
    ));

    $settings = new admin_settingpage('tool_openapi', get_string('pluginname', 'tool_openapi'), 'tool/openapi:manage');

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'tool_openapi/cacheheading',
            get_string('cacheheading', 'tool_openapi'),
            get_string('cacheheading_desc', 'tool_openapi')
        ));

        $purgeurl = new moodle_url('/admin/tool/openapi/pages/purge_cache.php', ['sesskey' => sesskey()]);
        $settings->add(new admin_setting_description(
            'tool_openapi/purgecache',
            get_string('purgecache', 'tool_openapi'),
            \html_writer::link($purgeurl, get_string('purgecache', 'tool_openapi'), ['class' => 'btn btn-secondary'])
        ));
    }

    $ADMIN->add('tools', $settings);
}
