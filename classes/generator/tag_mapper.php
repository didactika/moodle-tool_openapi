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

namespace tool_openapi\generator;

/**
 * Decides which OpenAPI tag a function belongs to, which is what a viewer
 * groups the catalog by.
 *
 * A plugin's functions can be tagged with the plugin itself: a function
 * registered by mod_quiz has component 'mod_quiz'. Core's cannot -- every
 * core function is registered under the single component 'moodle', which on
 * a stock site is around 400 of them, so tagging by component alone
 * collapses the whole of core into one unusable group.
 *
 * Core function names carry the grouping their component does not:
 * core_user_get_users, core_course_get_courses, core_group_get_groups. The
 * second segment is a core subsystem, so this resolves it against the real
 * subsystem list (core_component::get_core_subsystems()) rather than
 * splitting the name on underscores and hoping. That distinction matters
 * both ways: core_courseformat_* must not be filed under core_course (which
 * is why the longest subsystem wins), and core_get_string must not invent a
 * 'core_get' group out of a verb (which is why anything with no subsystem
 * match falls back to plain 'core' instead).
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tag_mapper {
    /** The component every core function is registered under. */
    private const CORE_COMPONENT = 'moodle';

    /** Tag for core functions that belong to no subsystem. */
    private const CORE_TAG = 'core';

    /**
     * The tag for one function.
     *
     * @param string $component The external_functions row's component.
     * @param string $name The function name, e.g. core_course_get_courses.
     * @return string
     */
    public static function for_function(string $component, string $name): string {
        if ($component !== self::CORE_COMPONENT) {
            return $component;
        }

        foreach (self::subsystem_tags() as $prefix => $tag) {
            if (str_starts_with($name, $prefix)) {
                return $tag;
            }
        }

        return self::CORE_TAG;
    }

    /**
     * A human-readable name for a tag, or null when there is nothing
     * better to show than the tag itself.
     *
     * Only real components have one: a plugin declares its display name as
     * its 'pluginname' string, while a core subsystem has no equivalent, so
     * core_course stays 'core_course' rather than being given an invented
     * label.
     *
     * @param string $tag As returned by for_function().
     * @return string|null
     */
    public static function describe(string $tag): ?string {
        if (!get_string_manager()->string_exists('pluginname', $tag)) {
            return null;
        }

        return get_string('pluginname', $tag);
    }

    /**
     * Subsystem name prefix => tag, longest prefix first so that the most
     * specific subsystem wins (core_courseformat_ before core_course_).
     *
     * @return array
     */
    private static function subsystem_tags(): array {
        $tags = [];
        foreach (array_keys(\core_component::get_core_subsystems()) as $subsystem) {
            $tags['core_' . $subsystem . '_'] = 'core_' . $subsystem;
        }

        uksort($tags, static fn($a, $b) => strlen($b) <=> strlen($a));

        return $tags;
    }
}
