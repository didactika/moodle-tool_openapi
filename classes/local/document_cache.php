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

use tool_openapi\generator\document_builder;

/**
 * The cached full catalog document, per db/caches.php's 'document' definition.
 *
 * Kept out of openapi.php and regenerate_spec_task.php directly so both
 * share one definition of "how the document is cached" instead of each
 * poking the cache API on its own.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_cache {
    /** @var string The single key this cache ever holds. */
    private const CACHE_KEY = 'document';

    /**
     * The full catalog document, building and caching it on a miss.
     *
     * @return array
     */
    public static function get(): array {
        $cache = self::cache();

        $document = $cache->get(self::CACHE_KEY);
        if ($document === false) {
            $document = document_builder::build();
            $cache->set(self::CACHE_KEY, $document);
        }

        return $document;
    }

    /**
     * Empty the cache, forcing the next get() to rebuild the document.
     */
    public static function purge(): void {
        self::cache()->purge();
    }

    /**
     * The underlying application cache instance.
     *
     * @return \cache
     */
    private static function cache(): \cache {
        return \cache::make('tool_openapi', 'document');
    }
}
