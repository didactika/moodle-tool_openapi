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
 * Everything to do with the generated catalog itself: view it, download it,
 * and force a rebuild of the cached copy.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page implements \renderable, \templatable {
    /**
     * Export for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $downloadurl = new \moodle_url('/admin/tool/openapi/pages/documentation/download.php');
        $purgeurl = new \moodle_url('/admin/tool/openapi/pages/documentation/purge_cache.php', [
            'sesskey' => sesskey(),
        ]);

        return [
            'catalogheading' => get_string('catalogheading', 'tool_openapi'),
            'catalogheadingdesc' => get_string('catalogheading_desc', 'tool_openapi'),
            'cacheheading' => get_string('cacheheading', 'tool_openapi'),
            'cacheheadingdesc' => get_string('cacheheading_desc', 'tool_openapi'),
            'jsonurl' => (new \moodle_url($downloadurl, ['format' => 'json']))->out(false),
            'jsonlabel' => get_string('downloadjson', 'tool_openapi'),
            'yamlurl' => (new \moodle_url($downloadurl, ['format' => 'yaml']))->out(false),
            'yamllabel' => get_string('downloadyaml', 'tool_openapi'),
            'purgeurl' => $purgeurl->out(false),
            'purgelabel' => get_string('purgecache', 'tool_openapi'),
            'viewerlabel' => get_string('viewer', 'tool_openapi'),
            // The interactive viewer is not built yet. Rendering it as a
            // disabled control with an explanation, rather than hiding it,
            // keeps the page's shape stable for when it does land and tells
            // an administrator it is coming instead of leaving them
            // wondering whether they are missing a setting.
            'viewerurl' => '',
            'viewerdisabledreason' => get_string('viewercomingsoon', 'tool_openapi'),
        ];
    }
}
