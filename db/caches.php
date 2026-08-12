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
 * Cache definitions for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // The full catalog document (document_builder::build()). Application-wide,
    // never invalidated by hand in normal operation: Moodle's own plugin
    // install/upgrade/uninstall cycle (upgrade_noncore()) already purges
    // every application cache at the only moment external_functions can
    // actually change, which is why there is no event observer here. The
    // per-service filter (generator\service_filter) is deliberately never
    // cached at all -- see that class's own docblock.
    'document' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'staticacceleration' => true,
    ],

    // Holds a freshly generated plugin token's plaintext -- and nothing
    // else, since simpledata means scalars only -- keyed by
    // tool_openapi_tokens.id, so pages/tokens/index.php can show it exactly once
    // after a Post/Redirect/Get -- same pattern local_servicemanager uses
    // for its own service tokens (db/caches.php's 'newtoken'). Session
    // scoped and short-lived so the plaintext does not linger in the
    // session longer than it takes to redirect and render it once.
    'newtoken' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 300,
    ],
];
