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
 * Language strings for tool_openapi
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['active'] = 'Active';
$string['addiprule'] = 'Add IP rule';
$string['allowedfunctions'] = 'Allowed functions';
$string['allowedfunctions_desc'] = 'One exact function name per line, e.g. core_course_get_courses. Leave blank to allow the full catalog.';
$string['cacheheading'] = 'Cache';
$string['cacheheading_desc'] = 'The full webservice catalog is cached; see the plugin documentation for when it is purged automatically.';
$string['cachepurged'] = 'The OpenAPI catalog cache has been purged.';
$string['confirmdeleteiprule'] = 'Delete the IP rule for \'{$a}\'? This cannot be undone.';
$string['confirmrevoketoken'] = 'Revoke the token \'{$a}\'? Any integrator using it will lose access immediately.';
$string['created'] = 'Created';
$string['createtoken'] = 'Create token';
$string['disabled'] = 'Disabled';
$string['editiprule'] = 'Edit IP rule';
$string['enabled'] = 'Enabled';
$string['fullcatalog'] = 'Full catalog';
$string['invalidiprange'] = 'Not a valid IP address or CIDR range.';
$string['invalidservice'] = 'No external service exists with shortname \'{$a}\'.';
$string['iprange'] = 'IP address or CIDR range';
$string['iprange_desc'] = 'A single address (192.0.2.1) or a CIDR range (192.0.2.0/24). Several entries can be comma-separated.';
$string['ipruledeleted'] = 'The IP rule has been deleted.';
$string['iprulesaved'] = 'The IP rule has been saved.';
$string['lastused'] = 'Last used';
$string['manageiprules'] = 'IP rules';
$string['managetokens'] = 'Tokens';
$string['never'] = 'Never';
$string['noiprules'] = 'No IP rules yet.';
$string['notokens'] = 'No tokens yet.';
$string['openapi:manage'] = 'Manage OpenAPI documentation settings, tokens and IP rules';
$string['openapi:view'] = 'View the OpenAPI documentation';
$string['openapi:viewfullcatalog'] = 'View the full webservice catalog with a Moodle session';
$string['pluginname'] = 'OpenAPI documentation';
$string['purgecache'] = 'Purge OpenAPI catalog cache';
$string['regeneratespectask'] = 'Regenerate the cached OpenAPI catalog document';
$string['revoke'] = 'Revoke';
$string['revoked'] = 'Revoked';
$string['ruledescription'] = 'Description';
$string['ruleenabled'] = 'Enabled';
$string['saverule'] = 'Save rule';
$string['tokencreatedonce'] = 'This token is shown only once. Copy it now -- it cannot be retrieved again after you leave this page.';
$string['tokenname'] = 'Name';
$string['tokenrevoked'] = 'The token \'{$a}\' has been revoked.';
