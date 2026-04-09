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
 * Main class for Generative AI use statement submission plugin
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_external\external_value;

define('ASSIGNSUBMISSION_GENAIUSE_FILEAREA', 'submission_evidence');
define('ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED', 0);
define('ASSIGNSUBMISSION_GENAIUSE_AI_USED', 1);

/**
 * Library class for generative AI use statement submission plugin.
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_genaiuse extends assign_submission_plugin {
    /**
     * Get the name of this plugin.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_genaiuse');
    }

    /**
     * Get the submission record for a given submission id.
     *
     * @param int $submissionid
     * @return mixed stdClass|false
     */
    private function get_genaiuse_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_genaiuse', ['submission' => $submissionid]);
    }

    /**
     * Get file options for the evidence file manager.
     *
     * @return array
     */
    private function get_file_options() {
        global $CFG;
        $maxbytes = $this->get_config('maxsubmissionsizebytes');
        if (empty($maxbytes) || $maxbytes == 0) {
            $maxbytes = get_config('assignsubmission_genaiuse', 'maxbytes');
        }
        if (empty($maxbytes) || $maxbytes == 0) {
            $maxbytes = $CFG->maxbytes;
        }
        $maxfiles = $this->get_config('maxevidencefiles');
        if (empty($maxfiles)) {
            $maxfiles = get_config('assignsubmission_genaiuse', 'maxfiles');
        }
        if (empty($maxfiles)) {
            $maxfiles = 5;
        }
        return [
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => $maxfiles,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL,
        ];
    }

    /**
     * Count the number of evidence files for a submission.
     *
     * @param int $submissionid
     * @return int
     */
    private function count_files($submissionid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submissionid,
            'id',
            false
        );
        return count($files);
    }

    /**
     * Get the default setting for this plugin in the assignment settings form.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        // Per-assignment max evidence files setting.
        if ($this->assignment->has_instance()) {
            $defaultmaxfiles = $this->get_config('maxevidencefiles');
            $defaultmaxbytes = $this->get_config('maxsubmissionsizebytes');
        } else {
            $defaultmaxfiles = get_config('assignsubmission_genaiuse', 'maxfiles');
            $defaultmaxbytes = get_config('assignsubmission_genaiuse', 'maxbytes');
        }
        if (empty($defaultmaxfiles)) {
            $defaultmaxfiles = 5;
        }

        $options = [];
        $sitemaxfiles = get_config('assignsubmission_genaiuse', 'maxfiles');
        if (empty($sitemaxfiles)) {
            $sitemaxfiles = 10;
        }
        for ($i = 1; $i <= $sitemaxfiles; $i++) {
            $options[$i] = $i;
        }

        $name = get_string('maxfiles', 'assignsubmission_genaiuse');
        $mform->addElement('select', 'assignsubmission_genaiuse_maxfiles', $name, $options);
        $mform->addHelpButton('assignsubmission_genaiuse_maxfiles', 'maxfiles', 'assignsubmission_genaiuse');
        $mform->setDefault('assignsubmission_genaiuse_maxfiles', $defaultmaxfiles);
        $mform->hideIf('assignsubmission_genaiuse_maxfiles', 'assignsubmission_genaiuse_enabled', 'notchecked');

        $choices = get_max_upload_sizes(
            $CFG->maxbytes,
            $COURSE->maxbytes,
            get_config('assignsubmission_genaiuse', 'maxbytes')
        );

        $name = get_string('maxbytes', 'assignsubmission_genaiuse');
        $mform->addElement('select', 'assignsubmission_genaiuse_maxbytes', $name, $choices);
        $mform->addHelpButton('assignsubmission_genaiuse_maxbytes', 'maxbytes', 'assignsubmission_genaiuse');
        $mform->setDefault('assignsubmission_genaiuse_maxbytes', $defaultmaxbytes);
        $mform->hideIf('assignsubmission_genaiuse_maxbytes', 'assignsubmission_genaiuse_enabled', 'notchecked');
    }

    /**
     * Save the settings for this plugin from the assignment settings form.
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxevidencefiles', $data->assignsubmission_genaiuse_maxfiles);
        $this->set_config('maxsubmissionsizebytes', $data->assignsubmission_genaiuse_maxbytes);
        return true;
    }

    /**
     * Add form elements for the submission form.
     *
     * Overrides get_form_elements_for_user to access $userid for fullname().
     *
     * @param stdClass|null $submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param int $userid
     * @return bool
     */
    public function get_form_elements_for_user($submission, MoodleQuickForm $mform, stdClass $data, $userid) {
        $user = core_user::get_user($userid);
        $fullname = fullname($user);

        // Load existing submission data.
        $existingrecord = null;
        if ($submission) {
            $existingrecord = $this->get_genaiuse_submission($submission->id);
        }

        // --- Radio buttons: No AI Used / AI Used ---
        $mform->addElement(
            'radio',
            'genaiuse_aiused',
            get_string('pluginname', 'assignsubmission_genaiuse'),
            get_string('noaiused', 'assignsubmission_genaiuse'),
            ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED
        );

        // --- "No AI Used" declaration (visible when aiused == 0) ---
        $noaidecl = '';
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_1', 'assignsubmission_genaiuse', $fullname));
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_2', 'assignsubmission_genaiuse'));
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_3', 'assignsubmission_genaiuse'));

        $noaigroup = [];
        $noaigroup[] = $mform->createElement('static', 'genaiuse_noai_text', '', $noaidecl);
        $mform->addGroup($noaigroup, 'genaiuse_noai_group', '', '', false);
        $mform->hideIf('genaiuse_noai_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED);

        $mform->addElement(
            'radio',
            'genaiuse_aiused',
            '',
            get_string('aiused', 'assignsubmission_genaiuse'),
            ASSIGNSUBMISSION_GENAIUSE_AI_USED
        );

        // Set default/existing value.
        if ($existingrecord) {
            $data->genaiuse_aiused = $existingrecord->aiused;
            $data->genaiuse_aitoolsused = $existingrecord->aitoolsused ?? '';
            $data->genaiuse_aiusecontext = $existingrecord->aiusecontext ?? '';
            $data->genaiuse_aicontentdesc = $existingrecord->aicontentdesc ?? '';
            $data->genaiuse_aimodification = $existingrecord->aimodification ?? '';
        } else {
            $mform->setDefault('genaiuse_aiused', ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED);
        }

        // --- "AI Used" form fields (visible when aiused == 1) ---

        global $OUTPUT;

        // Field 1: AI tools used.
        $prefix1group = [];
        $prefix1group[] = $mform->createElement(
            'static',
            'genaiuse_ai_prefix1',
            '',
            \html_writer::tag('span', get_string('ai_prefix_tools', 'assignsubmission_genaiuse', $fullname))
            . $OUTPUT->help_icon('genaiuse_aitoolsused', 'assignsubmission_genaiuse')
        );
        $mform->addGroup($prefix1group, 'genaiuse_ai_prefix1_group', '', '', false);
        $mform->hideIf('genaiuse_ai_prefix1_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        $mform->addElement('textarea', 'genaiuse_aitoolsused', '', ['rows' => 2, 'cols' => 60,
            'placeholder' => get_string('ai_placeholder_tools', 'assignsubmission_genaiuse')]);
        $mform->setType('genaiuse_aitoolsused', PARAM_TEXT);
        $mform->addRule('genaiuse_aitoolsused', get_string('fieldrequired', 'assignsubmission_genaiuse'), 'required', null, 'client');
        $mform->hideIf('genaiuse_aitoolsused', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);
        $mform->disabledIf('genaiuse_aitoolsused', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        // Field 2: AI use context.
        $prefix2group = [];
        $prefix2group[] = $mform->createElement(
            'static',
            'genaiuse_ai_prefix2',
            '',
            \html_writer::tag('span', get_string('ai_prefix_context', 'assignsubmission_genaiuse'))
            . $OUTPUT->help_icon('genaiuse_aiusecontext', 'assignsubmission_genaiuse')
        );
        $mform->addGroup($prefix2group, 'genaiuse_ai_prefix2_group', '', '', false);
        $mform->hideIf('genaiuse_ai_prefix2_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        $mform->addElement('textarea', 'genaiuse_aiusecontext', '', ['rows' => 2, 'cols' => 60,
            'placeholder' => get_string('ai_placeholder_context', 'assignsubmission_genaiuse')]);
        $mform->setType('genaiuse_aiusecontext', PARAM_TEXT);
        $mform->addRule('genaiuse_aiusecontext', get_string('fieldrequired', 'assignsubmission_genaiuse'), 'required', null, 'client');
        $mform->hideIf('genaiuse_aiusecontext', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);
        $mform->disabledIf('genaiuse_aiusecontext', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        // Field 3: AI content description.
        $prefix3group = [];
        $prefix3group[] = $mform->createElement(
            'static',
            'genaiuse_ai_prefix3',
            '',
            \html_writer::tag('span', get_string('ai_prefix_content', 'assignsubmission_genaiuse'))
            . $OUTPUT->help_icon('genaiuse_aicontentdesc', 'assignsubmission_genaiuse')
        );
        $mform->addGroup($prefix3group, 'genaiuse_ai_prefix3_group', '', '', false);
        $mform->hideIf('genaiuse_ai_prefix3_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        $mform->addElement('textarea', 'genaiuse_aicontentdesc', '', ['rows' => 2, 'cols' => 60,
            'placeholder' => get_string('ai_placeholder_content', 'assignsubmission_genaiuse')]);
        $mform->setType('genaiuse_aicontentdesc', PARAM_TEXT);
        $mform->addRule('genaiuse_aicontentdesc', get_string('fieldrequired', 'assignsubmission_genaiuse'), 'required', null, 'client');
        $mform->hideIf('genaiuse_aicontentdesc', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);
        $mform->disabledIf('genaiuse_aicontentdesc', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        // Field 4: AI modification.
        $prefix4group = [];
        $prefix4group[] = $mform->createElement(
            'static',
            'genaiuse_ai_prefix4',
            '',
            \html_writer::tag('span', get_string('ai_prefix_modification', 'assignsubmission_genaiuse'))
            . $OUTPUT->help_icon('genaiuse_aimodification', 'assignsubmission_genaiuse')
        );
        $mform->addGroup($prefix4group, 'genaiuse_ai_prefix4_group', '', '', false);
        $mform->hideIf('genaiuse_ai_prefix4_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        $mform->addElement('textarea', 'genaiuse_aimodification', '', ['rows' => 2, 'cols' => 60,
            'placeholder' => get_string('ai_placeholder_modification', 'assignsubmission_genaiuse')]);
        $mform->setType('genaiuse_aimodification', PARAM_TEXT);
        $mform->addRule('genaiuse_aimodification', get_string('fieldrequired', 'assignsubmission_genaiuse'), 'required', null, 'client');
        $mform->hideIf('genaiuse_aimodification', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);
        $mform->disabledIf('genaiuse_aimodification', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        // Acknowledgement paragraphs (7 items as numbered list).
        $ackhtml = \html_writer::start_tag('ol', ['class' => 'genaiuse_acknowledgement']);
        for ($i = 1; $i <= 7; $i++) {
            $ackhtml .= \html_writer::tag('li', get_string('ai_ack_' . $i, 'assignsubmission_genaiuse'));
        }
        $ackhtml .= \html_writer::end_tag('ol');

        $ackgroup = [];
        $ackgroup[] = $mform->createElement('static', 'genaiuse_ai_ack_text', '', $ackhtml);
        $mform->addGroup($ackgroup, 'genaiuse_ai_ack_group', '', '', false);
        $mform->hideIf('genaiuse_ai_ack_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        // --- Supporting evidence file upload (visible when aiused == 1) ---
        $evidenceheadergroup = [];
        $evidenceheadergroup[] = $mform->createElement(
            'static',
            'genaiuse_evidence_header_text',
            '',
            \html_writer::tag('h4', get_string('supportingevidence', 'assignsubmission_genaiuse'))
        );
        $mform->addGroup($evidenceheadergroup, 'genaiuse_evidence_header_group', '', '', false);
        $mform->hideIf('genaiuse_evidence_header_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;

        $data = file_prepare_standard_filemanager(
            $data,
            'genaiuse_evidence',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submissionid
        );

        $mform->addElement('filemanager', 'genaiuse_evidence_filemanager', '', null, $fileoptions);
        $mform->hideIf('genaiuse_evidence_filemanager', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);

        return true;
    }

    /**
     * Save the submission data.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB;

        // Save evidence files.
        $fileoptions = $this->get_file_options();
        $data = file_postupdate_standard_filemanager(
            $data,
            'genaiuse_evidence',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submission->id
        );

        $currentsubmission = $this->get_genaiuse_submission($submission->id);

        $record = new stdClass();
        $record->aiused = (int)($data->genaiuse_aiused ?? ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED);

        if ($record->aiused == ASSIGNSUBMISSION_GENAIUSE_AI_USED) {
            $record->aitoolsused = $data->genaiuse_aitoolsused ?? '';
            $record->aiusecontext = $data->genaiuse_aiusecontext ?? '';
            $record->aicontentdesc = $data->genaiuse_aicontentdesc ?? '';
            $record->aimodification = $data->genaiuse_aimodification ?? '';
        } else {
            $record->aitoolsused = null;
            $record->aiusecontext = null;
            $record->aicontentdesc = null;
            $record->aimodification = null;
        }

        $record->numfiles = $this->count_files($submission->id);

        if ($currentsubmission) {
            $record->id = $currentsubmission->id;
            return $DB->update_record('assignsubmission_genaiuse', $record);
        } else {
            $record->submission = $submission->id;
            $record->assignment = $this->assignment->get_instance()->id;
            $record->id = $DB->insert_record('assignsubmission_genaiuse', $record);
            return $record->id > 0;
        }
    }

    /**
     * Determine if a submission is empty before saving.
     *
     * @param stdClass $data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        return !isset($data->genaiuse_aiused);
    }

    /**
     * Is this submission empty?
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return !$this->get_genaiuse_submission($submission->id);
    }

    /**
     * Display a summary in the submission status table.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        $record = $this->get_genaiuse_submission($submission->id);
        if (!$record) {
            return '';
        }
        $showviewlink = true;
        if ($record->aiused == ASSIGNSUBMISSION_GENAIUSE_AI_USED) {
            return get_string('aiusedstatement', 'assignsubmission_genaiuse');
        }
        return get_string('noaiusedstatement', 'assignsubmission_genaiuse');
    }

    /**
     * Display the full submission.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $DB;

        $record = $this->get_genaiuse_submission($submission->id);
        if (!$record) {
            return '';
        }

        $user = $DB->get_record('user', ['id' => $submission->userid]);
        $fullname = fullname($user);
        $result = '';

        if ($record->aiused == ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED) {
            $result .= \html_writer::tag('p', get_string('noai_declaration_1', 'assignsubmission_genaiuse', $fullname));
            $result .= \html_writer::tag('p', get_string('noai_declaration_2', 'assignsubmission_genaiuse'));
            $result .= \html_writer::tag('p', get_string('noai_declaration_3', 'assignsubmission_genaiuse'));
        } else {
            $result .= \html_writer::tag(
                'p',
                get_string('ai_prefix_tools', 'assignsubmission_genaiuse', $fullname) . ' '
                . \html_writer::tag('strong', s($record->aitoolsused))
            );
            $result .= \html_writer::tag(
                'p',
                get_string('ai_prefix_context', 'assignsubmission_genaiuse') . ' '
                . \html_writer::tag('strong', s($record->aiusecontext))
            );
            $result .= \html_writer::tag(
                'p',
                get_string('ai_prefix_content', 'assignsubmission_genaiuse') . ' '
                . \html_writer::tag('strong', s($record->aicontentdesc))
            );
            $result .= \html_writer::tag(
                'p',
                get_string('ai_prefix_modification', 'assignsubmission_genaiuse') . ' '
                . \html_writer::tag('strong', s($record->aimodification))
            );

            $result .= \html_writer::start_tag('ol', ['class' => 'genaiuse_acknowledgement']);
            for ($i = 1; $i <= 7; $i++) {
                $result .= \html_writer::tag('li', get_string('ai_ack_' . $i, 'assignsubmission_genaiuse'));
            }
            $result .= \html_writer::end_tag('ol');

            if ($record->numfiles > 0) {
                $result .= \html_writer::tag('h4', get_string('supportingevidence', 'assignsubmission_genaiuse'));
                $result .= $this->assignment->render_area_files(
                    'assignsubmission_genaiuse',
                    ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
                    $submission->id
                );
            }
        }

        return $result;
    }

    /**
     * Remove submission data.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function remove(stdClass $submission) {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submission->id
        );

        $DB->delete_records('assignsubmission_genaiuse', ['submission' => $submission->id]);
        return true;
    }

    /**
     * The assignment has been deleted - clean up.
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records(
            'assignsubmission_genaiuse',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        return true;
    }

    /**
     * Get file areas for this plugin.
     *
     * @return array
     */
    public function get_file_areas() {
        return [ASSIGNSUBMISSION_GENAIUSE_FILEAREA => $this->get_name()];
    }

    /**
     * Copy submission data from a previous submission.
     *
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     * @return bool
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy files.
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $contextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $sourcesubmission->id,
            'id',
            false
        );
        foreach ($files as $file) {
            $fs->create_file_from_storedfile(['itemid' => $destsubmission->id], $file);
        }

        // Copy the DB record.
        $record = $this->get_genaiuse_submission($sourcesubmission->id);
        if ($record) {
            unset($record->id);
            $record->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_genaiuse', $record);
        }

        return true;
    }

    /**
     * Return a description of external params suitable for uploading from a webservice.
     *
     * @return array
     */
    public function get_external_parameters() {
        return [
            'genaiuse_aiused' => new external_value(PARAM_INT, 'Whether AI was used (0 or 1).', VALUE_OPTIONAL),
            'genaiuse_aitoolsused' => new external_value(PARAM_RAW, 'AI tools used.', VALUE_OPTIONAL),
            'genaiuse_aiusecontext' => new external_value(PARAM_RAW, 'AI use context.', VALUE_OPTIONAL),
            'genaiuse_aicontentdesc' => new external_value(PARAM_RAW, 'AI content description.', VALUE_OPTIONAL),
            'genaiuse_aimodification' => new external_value(PARAM_RAW, 'AI output modification.', VALUE_OPTIONAL),
        ];
    }
}
