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
 * Enable/disable handling for the access-gate switches on
 * pages/access_control/index.php (core/toggle, a real Moodle switch --
 * see that page's own docblock for why, not a bespoke widget).
 *
 * @module     tool_openapi/gate_toggle
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {addNotification} from 'core/notification';

/**
 * Sends the new state to pages/access_control/toggle.php, reverting the
 * switch if the request fails so the UI never shows a state that was not
 * actually saved.
 *
 * @param {HTMLInputElement} checkbox The switch's checkbox input
 */
const toggleGate = async(checkbox) => {
    const url = new URL(checkbox.dataset.actionurl, window.location.origin);
    url.searchParams.set('gate', checkbox.dataset.gate);
    url.searchParams.set('sesskey', checkbox.dataset.sesskey);
    url.searchParams.set('value', checkbox.checked ? '1' : '0');

    try {
        const response = await fetch(url.toString());
        if (!response.ok) {
            throw new Error('Request failed');
        }
    } catch (e) {
        checkbox.checked = !checkbox.checked;
        const message = await getString('gatetogglefailed', 'tool_openapi');
        await addNotification({message, type: 'error'});
    }
};

/**
 * Initialise the gate-toggle switches.
 *
 * The switch is matched by its own data attribute alone, never by a
 * Bootstrap class: core/toggle renders custom-control-input on Moodle 4.5
 * and form-check-input on 5.x, so a class-based selector silently stops
 * matching on half the versions this plugin supports -- and a switch that
 * matches nothing looks like it works while saving nothing.
 */
export const init = () => {
    document.addEventListener('change', (e) => {
        const checkbox = e.target.closest('input[type="checkbox"][data-gate]');
        if (checkbox) {
            toggleGate(checkbox);
        }
    });
};
