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
 * The only entry this plugin registers in $ADMIN at all -- access
 * control/tokens/IP rules are real pages (each with its own
 * require_login()/require_capability(), same as this one), but they are
 * reachable only via the shared tab bar (classes/local/settings_nav.php),
 * not from the admin tree. An admin_category grouping all 4 was tried and
 * reverted: clicking a category in Admin tools lands on Moodle's generic
 * "pick a page" listing instead of anywhere useful, and there is no core
 * hook to redirect a category to one of its children. This way "OpenAPI
 * documentation" in Admin tools goes straight to the first tab, no
 * intermediate landing page.
 *
 * Deliberately minimal: a shared tab bar, then the cache purge action and
 * catalog download links. The access-method toggles (enablesessiongate
 * and friends) live on their own page, see pages/access_control/index.php
 * -- every gate is already closed by default via get_config() returning
 * falsy when unset, so nothing here is required for the access engine or
 * the endpoint to behave correctly, only for an administrator to turn a
 * method on from the UI instead of by hand.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_openapi', get_string('pluginname', 'tool_openapi'), 'tool/openapi:manage');

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_description(
            'tool_openapi/tabs',
            '',
            $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree('tool_openapi_settings'))
        ));

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

        $downloadjsonurl = new moodle_url('/admin/tool/openapi/pages/download.php', ['format' => 'json']);
        $downloadyamlurl = new moodle_url('/admin/tool/openapi/pages/download.php', ['format' => 'yaml']);
        $settings->add(new admin_setting_description(
            'tool_openapi/download',
            '',
            \html_writer::link($downloadjsonurl, get_string('downloadjson', 'tool_openapi'), ['class' => 'btn btn-secondary mr-2'])
                . \html_writer::link($downloadyamlurl, get_string('downloadyaml', 'tool_openapi'), ['class' => 'btn btn-secondary'])
        ));
    }

    $ADMIN->add('tools', $settings);
}
