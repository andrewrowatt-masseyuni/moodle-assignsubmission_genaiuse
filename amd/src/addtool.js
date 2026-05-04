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
 * Append a template block to the Tool use editor when the "Add another tool" button is clicked.
 *
 * @module     assignsubmission_genaiuse/addtool
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Append `content` to the editor identified by `editorId` and scroll the new content into view.
 *
 * Handles TinyMCE (the default editor in Moodle 4.x). Falls back to writing into a plain textarea
 * when no rich-text editor is initialised for that ID (e.g. when the user has disabled editors).
 *
 * @param {string} editorId DOM ID of the underlying textarea element.
 * @param {string} content  HTML to append.
 */
const appendToEditor = (editorId, content) => {
    const tinymce = window.tinymce;
    if (tinymce) {
        const editor = tinymce.get(editorId);
        if (editor) {
            editor.focus();
            const body = editor.getBody();
            // Move the cursor to the end of the document before inserting.
            editor.selection.select(body, true);
            editor.selection.collapse(false);
            editor.execCommand('mceInsertContent', false, content);
            editor.selection.scrollIntoView();
            return;
        }
    }

    const textarea = document.getElementById(editorId);
    if (textarea) {
        textarea.value += content;
        textarea.scrollTop = textarea.scrollHeight;
        textarea.focus();
    }
};

/**
 * Wire up click handlers for "Add another tool" buttons.
 *
 * Each button carries `data-editor-id` (target editor textarea) and `data-template-id`
 * (DOM id of a hidden <template> element holding the HTML to append). The HTML payload
 * is read from the DOM at click time rather than passed through js_call_amd, which has
 * a ~1024-character argument limit.
 *
 * @param {string} buttonSelector CSS selector for the buttons.
 */
export const init = (buttonSelector) => {
    document.querySelectorAll(buttonSelector).forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const editorId = button.dataset.editorId;
            const templateId = button.dataset.templateId;
            if (!editorId || !templateId) {
                return;
            }
            const tpl = document.getElementById(templateId);
            if (!tpl) {
                return;
            }
            appendToEditor(editorId, tpl.innerHTML);
        });
    });
};
