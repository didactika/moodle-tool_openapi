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
 * Enable/disable toggle for the access-gate rows on pages/access_control/index.php.
 *
 * Progressive enhancement over a plain link: the link itself
 * (pages/access_control/toggle.php with sesskey) already works with no
 * JS, full page reload included. This only intercepts the click and
 * replays the same request with ajax=1, then patches the row in place
 * instead of navigating.
 *
 * @module     tool_openapi/gate_toggle
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {addNotification} from 'core/notification';

/**
 * Patches one gate row's icon, alt text and status badge after a toggle.
 *
 * @param {Element} link The toggle link
 * @param {boolean} enabled The gate's new state
 */
const applyState = async(link, enabled) => {
    const icon = link.querySelector('img.tool_openapi-gate-icon');
    if (icon) {
        icon.src = enabled ? link.dataset.iconOn : link.dataset.iconOff;
        icon.alt = enabled ? link.dataset.labelOn : link.dataset.labelOff;
    }

    const newValue = enabled ? '0' : '1';
    link.dataset.value = newValue;

    const url = new URL(link.href, window.location.origin);
    url.searchParams.set('value', newValue);
    link.href = url.toString();

    const status = link.closest('td')?.querySelector('.tool_openapi-gate-status');
    if (status) {
        const [enabledLabel, disabledLabel] = await Promise.all([
            getString('enabled', 'tool_openapi'),
            getString('disabled', 'tool_openapi'),
        ]);

        status.textContent = enabled ? enabledLabel : disabledLabel;
        status.classList.toggle('badge-success', enabled);
        status.classList.toggle('badge-secondary', !enabled);
    }
};

/**
 * Toggles one gate via AJAX and patches the row, falling back to a
 * notification if the request itself fails.
 *
 * @param {Element} link The toggle link
 */
const toggleGate = async(link) => {
    const url = new URL(link.href, window.location.origin);
    url.searchParams.set('ajax', '1');

    try {
        const response = await fetch(url.toString());
        if (!response.ok) {
            throw new Error('Request failed');
        }

        const body = await response.json();
        await applyState(link, body.enabled);
    } catch (e) {
        const message = await getString('gatetogglefailed', 'tool_openapi');
        await addNotification({message, type: 'error'});
    }
};

/**
 * Initialise the gate-toggle links.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.tool_openapi-gate-toggle');
        if (!link) {
            return;
        }

        e.preventDefault();
        toggleGate(link);
    });
};
