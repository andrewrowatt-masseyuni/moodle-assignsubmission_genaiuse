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
 * Uncheck the acknowledgement when the user toggles the AI use declaration.
 *
 * On edit, the acknowledgement checkbox is pre-ticked from the saved submission.
 * If the user then flips between "AI Used" and "No AI used" the previously-ticked
 * acknowledgement no longer reflects the new declaration, so we force a re-tick.
 *
 * @module     assignsubmission_genaiuse/ackreset
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const checkbox = document.getElementById('id_genaiuse_ack_confirmed');
    if (!checkbox) {
        return;
    }

    const radios = document.querySelectorAll('input[type="radio"][name="genaiuse_aiused"]');
    if (!radios.length) {
        return;
    }

    // Track the value selected at page load so we can detect a real toggle.
    let lastValue = '';
    radios.forEach((radio) => {
        if (radio.checked) {
            lastValue = radio.value;
        }
    });

    radios.forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!radio.checked) {
                return;
            }
            const newValue = radio.value;
            // Only reset when toggling between the two real choices — picking a
            // value for the first time from the empty sentinel must not wipe it.
            if (lastValue !== '' && newValue !== '' && newValue !== lastValue) {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            }
            lastValue = newValue;
        });
    });
};
