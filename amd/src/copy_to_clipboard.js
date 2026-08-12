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
 * Copy-to-clipboard button for pages/tokens/index.php's one-time token reveal.
 *
 * Same clipboard-then-execCommand-fallback approach as
 * local_servicemanager/token_manager.js, trimmed down to just the copy
 * behaviour this plugin needs (no per-row targets, a single token shown
 * once per page load).
 *
 * @module     tool_openapi/copy_to_clipboard
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {addNotification} from 'core/notification';

/**
 * Fallback copy method using execCommand, for browsers/contexts where the
 * async Clipboard API is unavailable (e.g. not a secure context).
 *
 * @param {string} text Text to copy
 * @return {boolean} Whether the copy succeeded
 */
const fallbackCopy = (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    textArea.style.opacity = '0';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    let success = false;
    try {
        success = document.execCommand('copy');
    } catch (e) {
        success = false;
    }

    document.body.removeChild(textArea);

    return success;
};

/**
 * Show a moment of success feedback on the button.
 *
 * @param {Element} button The button element
 */
const showCopySuccess = async(button) => {
    const original = button.textContent;
    const copiedLabel = await getString('copied', 'tool_openapi');

    button.textContent = copiedLabel;
    button.classList.add('btn-success');
    button.classList.remove('btn-outline-secondary');

    setTimeout(() => {
        button.textContent = original;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
};

/**
 * Copy text to the clipboard, preferring the async Clipboard API and
 * falling back to execCommand when it is not available.
 *
 * @param {string} text Text to copy
 * @param {Element} button The button element, for visual feedback
 */
const copyToClipboard = async(text, button) => {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            await showCopySuccess(button);
            return;
        } catch (e) {
            // Fall through to the execCommand fallback below.
        }
    }

    if (fallbackCopy(text)) {
        await showCopySuccess(button);
    } else {
        const message = await getString('copyfailed', 'tool_openapi');
        await addNotification({message, type: 'error'});
    }
};

/**
 * Initialise the copy-to-clipboard button.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        const button = e.target.closest('.tool_openapi-copy-token');
        if (!button) {
            return;
        }

        e.preventDefault();
        const token = button.getAttribute('data-token');
        if (token) {
            copyToClipboard(token, button);
        }
    });
};
