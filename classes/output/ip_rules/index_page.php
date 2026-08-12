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

namespace tool_openapi\output\ip_rules;

use renderer_base;

/**
 * The IP rule list.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_page implements \renderable, \templatable {
    /**
     * Creates the renderable from raw IP rule rows.
     *
     * @param \stdClass[] $rules As returned by $DB->get_records('tool_openapi_ip_rules', ...).
     */
    public function __construct(
        /** @var \stdClass[] Raw tool_openapi_ip_rules rows. */
        private readonly array $rules,
    ) {
    }

    /**
     * Export for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $rows = [];
        foreach ($this->rules as $rule) {
            $editurl = new \moodle_url('/admin/tool/openapi/pages/ip_rules/edit.php', ['id' => $rule->id]);
            $deleteurl = new \moodle_url('/admin/tool/openapi/pages/ip_rules/delete.php', ['id' => $rule->id]);

            $rows[] = [
                'iprange' => $rule->iprange,
                'description' => $rule->description,
                'scope' => $rule->allowedfunctions === null
                    ? get_string('fullcatalog', 'tool_openapi')
                    : implode(', ', array_filter(array_map('trim', explode("\n", $rule->allowedfunctions)))),
                'status' => $rule->enabled
                    ? get_string('enabled', 'tool_openapi')
                    : get_string('disabled', 'tool_openapi'),
                'actions' => $output->action_icon($editurl, new \pix_icon('t/edit', get_string('editiprule', 'tool_openapi')))
                    . $output->action_icon($deleteurl, new \pix_icon('t/delete', get_string('delete'))),
            ];
        }

        return [
            'heading' => get_string('manageiprules', 'tool_openapi'),
            'backurl' => (new \moodle_url('/admin/tool/openapi/pages/access_control/index.php'))->out(false),
            'backlabel' => get_string('backtoaccesscontrol', 'tool_openapi'),
            'createurl' => (new \moodle_url('/admin/tool/openapi/pages/ip_rules/create.php'))->out(false),
            'createlabel' => get_string('addiprule', 'tool_openapi'),
            'hasrules' => $rows !== [],
            'rows' => $rows,
            'emptymessage' => get_string('noiprules', 'tool_openapi'),
            'headers' => [
                'iprange' => get_string('iprange', 'tool_openapi'),
                'description' => get_string('ruledescription', 'tool_openapi'),
                'scope' => get_string('allowedfunctions', 'tool_openapi'),
                'status' => get_string('status'),
            ],
        ];
    }
}
