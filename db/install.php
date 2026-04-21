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
 * Post-install hook for assignsubmission_genaiuse.
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Fix the assignsubmission plugin sort order after installing genaiuse.
 *
 * Core assumes three submission plugins when building the default sort
 * order: onlinetext moves up twice, comments moves down twice. Because
 * 'genaiuse' sorts alphabetically between 'file' and 'onlinetext', its
 * presence pushes onlinetext one slot further down at install time, so
 * onlinetext's two upward swaps stop short of passing comments. That
 * reverses plugin0/plugin1 in the grading table and breaks core
 * mod_assign tests (test_gradingtable_status_rendering and
 * test_gradingtable_group_submissions_rendering).
 *
 * @return bool
 */
function xmldb_assignsubmission_genaiuse_install() {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/adminlib.php');

    $pluginmanager = new assign_plugin_manager('assignsubmission');

    // Move genaiuse to the bottom so it is out of onlinetext's path.
    while (true) {
        $plugins = $pluginmanager->get_sorted_plugins_list();
        if (end($plugins) === 'genaiuse') {
            break;
        }
        $pluginmanager->move_plugin('genaiuse', 'down');
    }

    // Moving genaiuse away from slot 1 slides comments up one position,
    // which would again leave onlinetext unable to overtake it. Push
    // comments back down one slot to restore the expected ordering.
    $pluginmanager->move_plugin('comments', 'down');

    return true;
}
