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
 * Toggle a fullscreen/popout state on the Tool use rich-text container.
 *
 * Mirrors the popout UX provided by mod_assign for editor fields, but works on
 * the read-only view rendered by view_student.mustache and view_staff.mustache.
 *
 * @module     assignsubmission_genaiuse/tooluse_popout
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const POPOUT_CLASS = 'popout';
const WRAPPER_SELECTOR = '.genaiuse_tooluse_popout_wrapper';
const BUTTON_SELECTOR = '[data-region="genaiuse-tooluse-popout"]';

let listenerBound = false;

/**
 * Toggle the popout state on the wrapper containing the clicked button.
 *
 * @param {HTMLElement} button The popout toggle button that was clicked.
 */
const togglePopout = (button) => {
    const wrapper = button.closest(WRAPPER_SELECTOR);
    if (!wrapper) {
        return;
    }
    wrapper.classList.toggle(POPOUT_CLASS);
};

/**
 * Bind a delegated click listener to handle every popout button on the page.
 *
 * Safe to call multiple times — only the first call installs the listener.
 */
export const init = () => {
    if (listenerBound) {
        return;
    }
    listenerBound = true;
    document.addEventListener('click', (event) => {
        const button = event.target.closest(BUTTON_SELECTOR);
        if (!button) {
            return;
        }
        event.preventDefault();
        togglePopout(button);
    });
};
