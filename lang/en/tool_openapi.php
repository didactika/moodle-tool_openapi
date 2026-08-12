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

$string['accesscontrolheading'] = 'Access methods';
$string['accesscontrolheading_desc'] = 'Turn each way of authorizing a request to the OpenAPI document on or off. All four are disabled on a fresh install.';
$string['active'] = 'Active';
$string['addiprule'] = 'Add IP rule';
$string['allowedfunctions'] = 'Allowed functions';
$string['allowedfunctions_help'] = 'Search and select the functions this should have access to.';
$string['cacheheading'] = 'Cache';
$string['cacheheading_desc'] = 'The full webservice catalog is cached; see the plugin documentation for when it is purged automatically.';
$string['cachepurged'] = 'The OpenAPI catalog cache has been purged.';
$string['confirmdeleteiprule'] = 'Delete the IP rule for \'{$a}\'? This cannot be undone.';
$string['confirmrevoketoken'] = 'Revoke the token \'{$a}\'? Any integrator using it will lose access immediately.';
$string['copied'] = 'Copied';
$string['copyfailed'] = 'Could not copy the token, please copy it manually.';
$string['copytoken'] = 'Copy';
$string['created'] = 'Created';
$string['createtoken'] = 'Create token';
$string['disabled'] = 'Disabled';
$string['downloadjson'] = 'Download catalog (JSON)';
$string['downloadyaml'] = 'Download catalog (YAML)';
$string['editiprule'] = 'Edit IP rule';
$string['emptyallowedfunctions'] = 'Select at least one function, or turn off the restriction to allow the full catalog.';
$string['enabled'] = 'Enabled';
$string['fullcatalog'] = 'Full catalog';
$string['gateip'] = 'IP address';
$string['gateip_desc'] = 'A request from an allowed IP address or CIDR range is authorized without any credential.';
$string['gatesession'] = 'Moodle session';
$string['gatesession_desc'] = 'A logged-in user with the right capability can view the catalog through their browser session.';
$string['gatetogglefailed'] = 'Could not change the setting, please try again.';
$string['gatetoken'] = 'Plugin token';
$string['gatetoken_desc'] = 'A request bearing a token issued by this plugin, sent via the Authorization: Bearer header.';
$string['gatewstoken'] = 'Existing webservice token';
$string['gatewstoken_desc'] = 'A request bearing an existing Moodle webservice token, reused for reading documentation instead of issuing a second secret.';
$string['invalidiprange'] = 'Not a valid IP address or CIDR range.';
$string['invalidservice'] = 'No external service exists with shortname \'{$a}\'.';
$string['iprange'] = 'IP address or CIDR range';
$string['iprange_desc'] = 'A single address (192.0.2.1) or a CIDR range (192.0.2.0/24). Several entries can be comma-separated.';
$string['ipruledeleted'] = 'The IP rule has been deleted.';
$string['iprulesaved'] = 'The IP rule has been saved.';
$string['lastused'] = 'Last used';
$string['manageaccesscontrol'] = 'Access control';
$string['manageiprules'] = 'IP rules';
$string['managetokens'] = 'Tokens';
$string['never'] = 'Never';
$string['noiprestriction'] = 'Any';
$string['noiprules'] = 'No IP rules yet.';
$string['notokens'] = 'No tokens yet.';
$string['openapi:manage'] = 'Manage OpenAPI documentation settings, tokens and IP rules';
$string['openapi:view'] = 'View the OpenAPI documentation';
$string['openapi:viewfullcatalog'] = 'View the full webservice catalog with a Moodle session';
$string['pluginname'] = 'OpenAPI documentation';
$string['purgecache'] = 'Purge OpenAPI catalog cache';
$string['regeneratespectask'] = 'Regenerate the cached OpenAPI catalog document';
$string['restrictfunctions'] = 'Restrict to specific webservices';
$string['restrictfunctions_help'] = 'Off by default, meaning access to every function in the catalog. Turn this on to pick exactly which functions are allowed instead.';
$string['revoke'] = 'Revoke';
$string['revoked'] = 'Revoked';
$string['ruledescription'] = 'Description';
$string['ruleenabled'] = 'Enabled';
$string['saverule'] = 'Save rule';
$string['tokencreatedonce'] = 'This token is shown only once. Copy it now -- it cannot be retrieved again after you leave this page.';
$string['tokenname'] = 'Name';
$string['tokenrevoked'] = 'The token \'{$a}\' has been revoked.';
