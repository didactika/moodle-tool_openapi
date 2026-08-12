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

namespace tool_openapi\output\tokens;

use renderer_base;

/**
 * The token list.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_page implements \renderable, \templatable {
    /**
     * Creates the renderable from raw token rows.
     *
     * @param \stdClass[] $tokens As returned by $DB->get_records('tool_openapi_tokens', ...).
     */
    public function __construct(
        /** @var \stdClass[] Raw tool_openapi_tokens rows. */
        private readonly array $tokens,
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
        foreach ($this->tokens as $token) {
            $deleteurl = new \moodle_url('/admin/tool/openapi/pages/tokens/delete.php', ['id' => $token->id]);

            $rows[] = [
                'name' => $token->name,
                'scope' => $token->allowedfunctions === null
                    ? get_string('fullcatalog', 'tool_openapi')
                    : implode(', ', array_filter(array_map('trim', explode("\n", $token->allowedfunctions)))),
                'iprestriction' => $token->iprestriction ?? get_string('noiprestriction', 'tool_openapi'),
                'created' => userdate($token->timecreated),
                'lastused' => $token->lastused
                    ? userdate($token->lastused)
                    : get_string('never', 'tool_openapi'),
                'deleteicon' => $output->action_icon(
                    $deleteurl,
                    new \pix_icon('t/delete', get_string('deletetoken', 'tool_openapi'))
                ),
            ];
        }

        return [
            'heading' => get_string('managetokens', 'tool_openapi'),
            'createurl' => (new \moodle_url('/admin/tool/openapi/pages/tokens/create.php'))->out(false),
            'createlabel' => get_string('createtoken', 'tool_openapi'),
            'hastokens' => $rows !== [],
            'rows' => $rows,
            'emptymessage' => get_string('notokens', 'tool_openapi'),
            'headers' => [
                'name' => get_string('tokenname', 'tool_openapi'),
                'scope' => get_string('allowedfunctions', 'tool_openapi'),
                'iprestriction' => get_string('iprange', 'tool_openapi'),
                'created' => get_string('created', 'tool_openapi'),
                'lastused' => get_string('lastused', 'tool_openapi'),
            ],
        ];
    }
}
