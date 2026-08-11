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
 * Version information for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Component name, "<plugin type>_<plugin name>". Must match the folder this
// plugin installs into (<moodle>/admin/tool/openapi/) and the folder name
// moodle-plugin-ci checks out in CI.
$plugin->component = 'tool_openapi';

// Bump this on every change that should trigger an upgrade step, even
// between releases -- Moodle uses THIS field (not $plugin->release) to
// decide whether db/upgrade.php needs to run.
$plugin->version = 2026081100;

// Lowest Moodle core version this plugin installs on, expressed as Moodle
// core's OWN version.php integer. 2024100700 is Moodle 4.5.0's own version
// number (see the org's template-moodle-plugin, which verifies this same
// value against https://moodledev.io/general/releases/4.5).
$plugin->requires = 2024100700;

// Nothing has shipped yet.
$plugin->maturity = MATURITY_ALPHA;

// Human-readable release string. This is the ONE field that drives
// .github/workflows/release.yml: whenever it changes on `main`, that
// workflow tags the commit v<release> and publishes a GitHub Release with
// the plugin zip attached.
$plugin->release = '0.1.0';

// Moodle versions this plugin is tested against, as an inclusive range of
// integer branch codes (405 = 4.5, 502 = 5.2 -- the real latest stable
// release; moodle/moodle has no MOODLE_503_STABLE branch yet, confirmed
// against its own branch list, so 5.3 is not a real, testable version at
// the time this was written). A single `main` line covers the whole range
// for now -- see the plan's Fase 0 for why this repository does not carry
// the template's MOODLE_XXX_STABLE branches yet: cutting one per Moodle
// version before any plugin code has diverged between them would be ten
// empty, identical branches, not ten supported versions. A MOODLE_XXX_STABLE
// branch gets cut the day a specific Moodle version actually needs code the
// others don't -- the same principle this organisation already applies to
// its npm packages' vMAJOR.x branches.
//
// ci.yml's test matrix, per its own documented semantics, only exercises
// the two endpoints of this range (405 and 502) -- not the versions in
// between -- until they are listed explicitly.
$plugin->supported = [405, 502];
