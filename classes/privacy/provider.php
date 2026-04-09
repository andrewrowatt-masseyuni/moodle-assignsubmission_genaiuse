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
 * Privacy provider for assignsubmission_genaiuse.
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_genaiuse\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use mod_assign\privacy\assign_plugin_request_data;

// phpcs:disable Universal.OOStructures.AlphabeticExtendsImplements

/**
 * Privacy provider for assignsubmission_genaiuse.
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \mod_assign\privacy\assignsubmission_provider,
    \mod_assign\privacy\assignsubmission_user_provider,
    \core_privacy\local\metadata\provider {
    /**
     * Return meta data about this plugin.
     *
     * @param collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {
        $detail = [
            'assignment' => 'privacy:metadata:assignmentid',
            'submission' => 'privacy:metadata:submissionpurpose',
            'aiused' => 'privacy:metadata:aiused',
            'aitoolsused' => 'privacy:metadata:aitoolsused',
            'aiusecontext' => 'privacy:metadata:aiusecontext',
            'aicontentdesc' => 'privacy:metadata:aicontentdesc',
            'aimodification' => 'privacy:metadata:aimodification',
        ];
        $collection->add_database_table('assignsubmission_genaiuse', $detail, 'privacy:metadata:tablepurpose');
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');
        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param int $userid The user ID that we are finding contexts for.
     * @param contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // This is already fetched from mod_assign.
    }

    /**
     * This is also covered by the mod_assign provider and its queries.
     *
     * @param \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
        // No need.
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the assign_submission table then please fill in this method.
     *
     * @param \core_privacy\local\request\userlist $userlist The userlist object.
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // Not required.
    }

    /**
     * Export all user data for this plugin.
     *
     * @param assign_plugin_request_data $exportdata Data used to determine which context and user to export.
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        if ($exportdata->get_user() != null) {
            return null;
        }

        global $DB;

        $submission = $exportdata->get_pluginobject();
        $context = $exportdata->get_context();

        $currentpath = $exportdata->get_subcontext();
        $currentpath[] = get_string('privacy:path', 'assignsubmission_genaiuse');

        $record = $DB->get_record('assignsubmission_genaiuse', ['submission' => $submission->id]);
        if ($record) {
            $submissiondata = new \stdClass();
            $submissiondata->aiused = $record->aiused;
            $submissiondata->aitoolsused = $record->aitoolsused ?? '';
            $submissiondata->aiusecontext = $record->aiusecontext ?? '';
            $submissiondata->aicontentdesc = $record->aicontentdesc ?? '';
            $submissiondata->aimodification = $record->aimodification ?? '';

            writer::with_context($context)
                ->export_area_files(
                    $currentpath,
                    'assignsubmission_genaiuse',
                    ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
                    $submission->id
                )
                ->export_data($currentpath, $submissiondata);
        }
    }

    /**
     * Delete all submission data for the context.
     *
     * @param assign_plugin_request_data $requestdata Data useful for deleting user data.
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files(
            $requestdata->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA
        );

        $DB->delete_records('assignsubmission_genaiuse', ['assignment' => $requestdata->get_assignid()]);
    }

    /**
     * Delete submission data for a specific user.
     *
     * @param assign_plugin_request_data $deletedata Details about the user and context.
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;

        $submissionid = $deletedata->get_pluginobject()->id;

        $fs = get_file_storage();
        $fs->delete_area_files(
            $deletedata->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submissionid
        );

        $DB->delete_records('assignsubmission_genaiuse', [
            'assignment' => $deletedata->get_assignid(),
            'submission' => $submissionid,
        ]);
    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     *
     * @param assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;

        if (empty($deletedata->get_submissionids())) {
            return;
        }

        $fs = get_file_storage();
        [$sql, $params] = $DB->get_in_or_equal($deletedata->get_submissionids(), SQL_PARAMS_NAMED);
        $fs->delete_area_files_select(
            $deletedata->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $sql,
            $params
        );

        $params['assignid'] = $deletedata->get_assignid();
        $DB->delete_records_select('assignsubmission_genaiuse', "assignment = :assignid AND submission $sql", $params);
    }
}
