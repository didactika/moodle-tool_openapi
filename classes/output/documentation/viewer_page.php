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

namespace tool_openapi\output\documentation;

use renderer_base;

/**
 * The interactive viewer: an empty container plus everything the browser
 * side needs to fill it.
 *
 * The document is fetched from this plugin's own download endpoint rather
 * than from openapi.php, so the viewer keeps working with every access gate
 * switched off -- the gates decide who may read the catalog from outside,
 * not whether an administrator can look at it here.
 *
 * The library URL is handed to the template so the page can load it with a
 * plain script tag. It deliberately does not go through
 * $PAGE->requires->js(): that routes every .js file through
 * lib/javascript.php, which runs it through core_minify, and minifying an
 * already-minified 1.5MB bundle produces truncated, unparseable output.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewer_page implements \renderable, \templatable {
    /** Id of the element Swagger UI renders into. */
    public const ELEMENT_ID = 'tool_openapi-viewer';

    /**
     * Export for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG;

        $specurl = new \moodle_url('/admin/tool/openapi/pages/documentation/download.php', [
            'format' => 'json',
            'inline' => 1,
        ]);

        $library = new \moodle_url('/admin/tool/openapi/thirdparty/swagger-ui/swagger-ui-bundle.js');

        return [
            'heading' => get_string('viewerheading', 'tool_openapi'),
            'headingdesc' => get_string('viewerheading_desc', 'tool_openapi'),
            'tokenhint' => $output->notification(
                get_string('viewertokenhint', 'tool_openapi'),
                \core\output\notification::NOTIFY_INFO,
                false
            ),
            'backurl' => (new \moodle_url('/admin/tool/openapi/pages/documentation/index.php'))->out(false),
            'backlabel' => get_string('backtodocumentation', 'tool_openapi'),
            'elementid' => self::ELEMENT_ID,
            'specurl' => $specurl->out(false),
            'libraryurl' => $library->out(false),
            'endpoint' => $CFG->wwwroot . '/webservice/rest/server.php',
        ];
    }
}
