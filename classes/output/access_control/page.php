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

namespace tool_openapi\output\access_control;

use renderer_base;

/**
 * The access methods table: one row per gate, each with a switch and,
 * where the method has anything to configure, a cog linking to it.
 *
 * The page also names the one endpoint these methods govern. Without it an
 * administrator is left switching things on with no way to tell what they
 * just opened, and the plugin's own pages -- which are governed by the
 * manage capability, not by any of this -- are easy to mistake for what is
 * being gated.
 *
 * The switches carry their state in data attributes rather than
 * extraattributes, which core added later and 4.5 does not have.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page implements \renderable, \templatable {
    /**
     * The 4 gates, in the order access_checker tries them.
     *
     * Each entry is [config key => [name string, description string,
     * config URL or null]]. A null URL means the method has nothing of its
     * own to configure: the session gate is decided purely by capabilities,
     * which are managed from Moodle's own roles pages, not here.
     *
     * @return array
     */
    private static function gates(): array {
        return [
            'enablesessiongate' => ['gatesession', 'gatesession_desc', null],
            'enableipgate' => [
                'gateip',
                'gateip_desc',
                new \moodle_url('/admin/tool/openapi/pages/ip_rules/index.php'),
            ],
            'enabletokengate' => [
                'gatetoken',
                'gatetoken_desc',
                new \moodle_url('/admin/tool/openapi/pages/tokens/index.php'),
            ],
            'enablewstokengate' => [
                'gatewstoken',
                'gatewstoken_desc',
                // Moodle's own token page: a reused webservice token is
                // created and revoked there, never here. The manage page
                // itself, not the admin setting section that merely embeds
                // it, so the cog lands where the tokens actually are.
                new \moodle_url('/admin/webservice/tokens.php'),
            ],
        ];
    }

    /**
     * Export for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG;

        $toggleurl = new \moodle_url('/admin/tool/openapi/pages/access_control/toggle.php');

        $rows = [];
        foreach (self::gates() as $configkey => [$namestring, $descstring, $configurl]) {
            $name = get_string($namestring, 'tool_openapi');

            $row = [
                'name' => $name,
                'description' => get_string($descstring, 'tool_openapi'),
                'toggle' => $output->render_from_template('core/toggle', [
                    'id' => 'tool_openapi-gate-' . $configkey,
                    'checked' => (bool) get_config('tool_openapi', $configkey),
                    'dataattributes' => [
                        ['name' => 'gate', 'value' => $configkey],
                        ['name' => 'sesskey', 'value' => sesskey()],
                        ['name' => 'actionurl', 'value' => $toggleurl->out(false)],
                    ],
                    'label' => $name,
                    'labelclasses' => 'sr-only',
                ]),
                'configicon' => '',
            ];

            if ($configurl !== null) {
                $row['configicon'] = $output->action_icon(
                    $configurl,
                    new \pix_icon('i/settings', get_string('configuregate', 'tool_openapi', $name))
                );
            }

            $rows[] = $row;
        }

        return [
            'heading' => get_string('accesscontrolheading', 'tool_openapi'),
            'headingdesc' => get_string('accesscontrolheading_desc', 'tool_openapi'),
            'endpointheading' => get_string('endpointheading', 'tool_openapi'),
            'endpointdesc' => get_string('endpointheading_desc', 'tool_openapi', 'tool/openapi:manage'),
            'endpointurl' => $CFG->wwwroot . '/admin/tool/openapi/openapi.php',
            'rows' => $rows,
        ];
    }
}
