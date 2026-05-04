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
define('ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TEMPLATE', 'submission_template');
define('ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE', 'submission_tooluse');
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
     * Get the moodle_url for the tool use template file, or null if not set.
     *
     * @return \moodle_url|null
     */
    private function get_template_moodle_url() {
        $fs = get_file_storage();
        $syscontextid = \context_system::instance()->id;
        $files = $fs->get_area_files(
            $syscontextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TEMPLATE,
            0,
            'id',
            false
        );

        if (empty($files)) {
            return null;
        }

        $file = reset($files);
        return \moodle_url::make_pluginfile_url(
            $syscontextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TEMPLATE,
            0,
            $file->get_filepath(),
            $file->get_filename(),
            true
        );
    }

    /**
     * Get the HTML for the tool use template download link.
     *
     * @return string HTML with download link, or empty string if no template.
     */
    private function get_template_download_html() {
        $fs = get_file_storage();
        $syscontextid = \context_system::instance()->id;
        $files = $fs->get_area_files(
            $syscontextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TEMPLATE,
            0,
            'id',
            false
        );

        if (empty($files)) {
            return '';
        }

        $file = reset($files);
        $url = \moodle_url::make_pluginfile_url(
            $syscontextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TEMPLATE,
            0,
            $file->get_filepath(),
            $file->get_filename(),
            true
        );

        return \html_writer::tag(
            'p',
            \html_writer::link($url, get_string('downloadtemplate', 'assignsubmission_genaiuse') . ' ' . $file->get_filename())
        );
    }

    /**
     * Count the number of files in a given file area for a submission.
     *
     * @param int $submissionid
     * @param string $filearea The file area constant. Defaults to the evidence area.
     * @return int
     */
    private function count_files($submissionid, $filearea = ASSIGNSUBMISSION_GENAIUSE_FILEAREA) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            $filearea,
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

        $name = get_string('maxfilesassignsettings', 'assignsubmission_genaiuse');
        $mform->addElement('select', 'assignsubmission_genaiuse_maxfiles', $name, $options);
        $mform->addHelpButton('assignsubmission_genaiuse_maxfiles', 'maxfiles', 'assignsubmission_genaiuse');
        $mform->setDefault('assignsubmission_genaiuse_maxfiles', $defaultmaxfiles);
        $mform->hideIf('assignsubmission_genaiuse_maxfiles', 'assignsubmission_genaiuse_enabled', 'notchecked');

        $choices = get_max_upload_sizes(
            $CFG->maxbytes,
            $COURSE->maxbytes,
            get_config('assignsubmission_genaiuse', 'maxbytes')
        );

        $name = get_string('maxbytesassignsettings', 'assignsubmission_genaiuse');
        $mform->addElement('select', 'assignsubmission_genaiuse_maxbytes', $name, $choices);
        $mform->addHelpButton('assignsubmission_genaiuse_maxbytes', 'maxbytes', 'assignsubmission_genaiuse');
        $mform->setDefault('assignsubmission_genaiuse_maxbytes', $defaultmaxbytes);
        $mform->hideIf('assignsubmission_genaiuse_maxbytes', 'assignsubmission_genaiuse_enabled', 'notchecked');

        // Per-assignment: enable OneDrive link field on the submission form.
        $defaultonedrive = $this->assignment->has_instance()
            ? $this->get_config('onedrivelinkenabled')
            : 0;
        $mform->addElement(
            'selectyesno',
            'assignsubmission_genaiuse_onedrivelink',
            get_string('onedrivelink_enabled', 'assignsubmission_genaiuse')
        );
        $mform->addHelpButton(
            'assignsubmission_genaiuse_onedrivelink',
            'onedrivelink_enabled',
            'assignsubmission_genaiuse'
        );
        $mform->setDefault('assignsubmission_genaiuse_onedrivelink', $defaultonedrive);
        $mform->hideIf(
            'assignsubmission_genaiuse_onedrivelink',
            'assignsubmission_genaiuse_enabled',
            'notchecked'
        );
    }

    /**
     * Save the settings for this plugin from the assignment settings form.
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        if (isset($data->assignsubmission_genaiuse_maxfiles)) {
            $this->set_config('maxevidencefiles', $data->assignsubmission_genaiuse_maxfiles);
        }
        if (isset($data->assignsubmission_genaiuse_maxbytes)) {
            $this->set_config('maxsubmissionsizebytes', $data->assignsubmission_genaiuse_maxbytes);
        }
        if (isset($data->assignsubmission_genaiuse_onedrivelink)) {
            $this->set_config('onedrivelinkenabled', $data->assignsubmission_genaiuse_onedrivelink);
        }
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

        // Required/optional badge HTML, used in card headers.
        $requiredbadge = \html_writer::tag(
            'span',
            get_string('cardstatus_required', 'assignsubmission_genaiuse'),
            ['class' => 'submission_genaiuse_badge submission_genaiuse_badge_required']
        );
        $optionalbadge = \html_writer::tag(
            'span',
            get_string('cardstatus_optional', 'assignsubmission_genaiuse'),
            ['class' => 'submission_genaiuse_badge submission_genaiuse_badge_optional']
        );

        $cardheader = fn($title, $badge) =>
            '<div class="card-header submission_genaiuse_cardheader d-flex justify-content-between align-items-center">'
            . \html_writer::tag('h4', $title, ['class' => 'submission_genaiuse_cardtitle h6 mb-0'])
            . $badge
            . '</div>';

        // Plugin-wide wrapper for scoped styling.
        $mform->addElement('html', '<div class="submission_genaiuse fcontainer">');

        $mform->addElement(
            'static',
            'genaiuse_heading1',
            '',
            \html_writer::tag(
                'h3',
                get_string('pluginname', 'assignsubmission_genaiuse'),
                ['class' => 'submission_genaiuse_pageheading']
            )
        );

        // Card 1: Generative AI use declaration (always visible, required).
        $mform->addElement(
            'html',
            '<div class="card submission_genaiuse_card submission_genaiuse_card_required mb-3">'
            . $cardheader(get_string('genaiuse_declaration', 'assignsubmission_genaiuse'), $requiredbadge)
            . '<div class="card-body">'
        );

        // Two radio cards: "AI Used" / "No AI Used". A third hidden sentinel radio with value=''
        // is the default so the field always carries a value — without it, Moodle's hideIf JS
        // (lib/form/form.js _dependencyDefault) skips unchecked radios and `lock` stays false,
        // which would leave both downstream subforms visible. The empty-string value also fails
        // the required rule's `'' != trim($value)` check, so the user is still forced to pick.
        $radiocard = fn($title, $helper) => \html_writer::div(
            \html_writer::div($title, 'submission_genaiuse_radio_title')
            . \html_writer::div($helper, 'submission_genaiuse_radio_helper'),
            'submission_genaiuse_radio_card'
        );

        $radioarray = [];
        $radioarray[] = $mform->createElement('radio', 'genaiuse_aiused', '', '', '');
        $radioarray[] = $mform->createElement(
            'radio',
            'genaiuse_aiused',
            '',
            $radiocard(
                get_string('aiused', 'assignsubmission_genaiuse'),
                get_string('aiused_helper', 'assignsubmission_genaiuse')
            ),
            (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED
        );
        $radioarray[] = $mform->createElement(
            'radio',
            'genaiuse_aiused',
            '',
            $radiocard(
                get_string('noaiused', 'assignsubmission_genaiuse'),
                get_string('noaiused_helper', 'assignsubmission_genaiuse')
            ),
            (string)ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED
        );

        $mform->addGroup(
            $radioarray,
            'genaiuse_aiused_group',
            get_string('genaiuse_declaration', 'assignsubmission_genaiuse'),
            '',
            false,
            ['class' => 'submission_genaiuse_radiocards']
        );
        $mform->addRule('genaiuse_aiused_group', get_string('required'), 'required', null, 'client');

        // Declaration text visible when aiused == 0.
        $noaidecl = '';
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_1', 'assignsubmission_genaiuse', $fullname));
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_2', 'assignsubmission_genaiuse'));
        $noaidecl .= \html_writer::tag('p', get_string('noai_declaration_3', 'assignsubmission_genaiuse'));

        $noaigroup = [];
        $noaigroup[] = $mform->createElement('static', 'genaiuse_noai_text', '', $noaidecl);
        $mform->addGroup($noaigroup, 'genaiuse_noai_group', '', '', false);
        $mform->hideIf('genaiuse_noai_group', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED);

        // Set default/existing value.
        if ($existingrecord) {
            $data->genaiuse_aiused = (string)$existingrecord->aiused;
            $data->genaiuse_aitoolsused = $existingrecord->aitoolsused ?? '';
            $data->genaiuse_aiusecontext = $existingrecord->aiusecontext ?? '';
            $data->genaiuse_aicontentdesc = $existingrecord->aicontentdesc ?? '';
            $data->genaiuse_aimodification = $existingrecord->aimodification ?? '';
            $data->genaiuse_onedrivelink = $existingrecord->onedrivelink ?? '';
            // Pre-tick the acknowledgement on edit if AI use was previously confirmed.
            if ((int)$existingrecord->aiused === ASSIGNSUBMISSION_GENAIUSE_AI_USED) {
                $data->genaiuse_ack_confirmed = 1;
            }
        } else {
            // Check the hidden sentinel radio so the field has a value (empty string) for
            // hideIf evaluation. Neither user-facing radio is pre-selected.
            $mform->setDefault('genaiuse_aiused', '');
        }

        $requiredrule = get_string('fieldrequired', 'assignsubmission_genaiuse');
        $aiusedstr = (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED;

        // AI-used details: each textarea is its own top-level element so the standard mform renderer
        // emits an inline error slot (#id_error_<name>) and applies the is-invalid border. The prose
        // prefix becomes the field label; CSS in styles.css stacks the label above the textarea and
        // makes the row span the full card body.
        $aifields = [
            ['genaiuse_aitoolsused', 'ai_prefix_tools', 'ai_placeholder_tools', $fullname],
            ['genaiuse_aiusecontext', 'ai_prefix_context', 'ai_placeholder_context', null],
            ['genaiuse_aicontentdesc', 'ai_prefix_content', 'ai_placeholder_content', null],
            ['genaiuse_aimodification', 'ai_prefix_modification', 'ai_placeholder_modification', null],
        ];
        foreach ($aifields as [$name, $labelkey, $placeholderkey, $labelarg]) {
            $label = \html_writer::tag(
                'span',
                get_string($labelkey, 'assignsubmission_genaiuse', $labelarg),
                ['class' => 'submission_genaiuse_aifield_label']
            );
            $mform->addElement('textarea', $name, $label, [
                'rows' => 2,
                'cols' => 60,
                'placeholder' => get_string($placeholderkey, 'assignsubmission_genaiuse'),
            ]);
            $mform->setType($name, PARAM_TEXT);
            $mform->addHelpButton($name, $name, 'assignsubmission_genaiuse');
            $mform->hideIf($name, 'genaiuse_aiused', 'neq', $aiusedstr);
            $mform->disabledIf($name, 'genaiuse_aiused', 'neq', $aiusedstr);
        }

        // Acknowledgement: collapsed <details> block as a top-level static element.
        $ackcontent = get_config('assignsubmission_genaiuse', 'genaiuse_aiuseacknowledgementextra');
        $hasack = (string)$ackcontent !== '';
        if ($hasack) {
            $detailshtml = '<details class="genaiuse_acknowledgement_details"><summary>'
                . s(get_string('ack_summary', 'assignsubmission_genaiuse'))
                . '</summary>'
                . \html_writer::tag('div', $ackcontent, ['class' => 'genaiuse_acknowledgement'])
                . '</details>';
            $mform->addElement('static', 'genaiuse_ai_ack_text', '', $detailshtml);
            $mform->hideIf('genaiuse_ai_ack_text', 'genaiuse_aiused', 'neq', $aiusedstr);
        }

        // Required acknowledgement checkbox — top-level so its inline error displays correctly.
        if ($hasack) {
            $mform->addElement(
                'advcheckbox',
                'genaiuse_ack_confirmed',
                '',
                get_string('ack_confirm', 'assignsubmission_genaiuse')
            );
            $mform->setType('genaiuse_ack_confirmed', PARAM_INT);
            $mform->hideIf('genaiuse_ack_confirmed', 'genaiuse_aiused', 'neq', (string)ASSIGNSUBMISSION_GENAIUSE_AI_USED);
        }

        // Close declaration card (card-body + card).
        $mform->addElement('html', '</div></div>');

        // Card 2: Tool use (required when AI used).
        $mform->addElement(
            'html',
            '<div class="card submission_genaiuse_card submission_genaiuse_card_required'
            . ' submission_genaiuse_card_collapsible mb-3">'
            . $cardheader(get_string('tooluse_heading', 'assignsubmission_genaiuse'), $requiredbadge)
            . '<div class="card-body">'
        );

        $templateurl = $this->get_template_moodle_url();
        // Site-wide template content — used both as the editor default for new submissions
        // and as the payload appended by the "Add another tool" button on every submission.
        $templatecontent = (string)get_config('assignsubmission_genaiuse', 'toolusetemplatecontent');

        // Method radio cards: Enter text / Upload document.
        $toolusetextcard = $radiocard(
            get_string('tooluse_method_text_title', 'assignsubmission_genaiuse'),
            get_string('tooluse_method_text_helper', 'assignsubmission_genaiuse')
        );
        $tooluseuploadcard = $radiocard(
            get_string('tooluse_method_upload_title', 'assignsubmission_genaiuse'),
            get_string('tooluse_method_upload_helper', 'assignsubmission_genaiuse')
        );

        $toolusemethodradios = [];
        $toolusemethodradios[] = $mform->createElement('radio', 'genaiuse_tooluse_method', '', '', '');
        $toolusemethodradios[] = $mform->createElement(
            'radio',
            'genaiuse_tooluse_method',
            '',
            $toolusetextcard,
            'text'
        );
        $toolusemethodradios[] = $mform->createElement(
            'radio',
            'genaiuse_tooluse_method',
            '',
            $tooluseuploadcard,
            'upload'
        );
        $mform->addGroup(
            $toolusemethodradios,
            'genaiuse_tooluse_method_group',
            get_string('tooluse_method_label', 'assignsubmission_genaiuse'),
            '',
            false,
            ['class' => 'submission_genaiuse_radiocards']
        );
        $mform->hideIf('genaiuse_tooluse_method_group', 'genaiuse_aiused', 'neq', $aiusedstr);

        // Tool use richtext editor (visible when method = 'text').
        $mform->addElement('editor', 'genaiuse_tooluse_editor', '', ['rows' => 15]);
        $mform->setType('genaiuse_tooluse_editor', PARAM_RAW);

        if ($existingrecord) {
            $data->genaiuse_tooluse_editor = ['text' => $existingrecord->tooluse ?? '', 'format' => FORMAT_HTML];
        } else {
            $mform->setDefault(
                'genaiuse_tooluse_editor',
                ['text' => $templatecontent, 'format' => FORMAT_HTML]
            );
        }
        $mform->hideIf('genaiuse_tooluse_editor', 'genaiuse_aiused', 'neq', $aiusedstr);
        $mform->hideIf('genaiuse_tooluse_editor', 'genaiuse_tooluse_method', 'neq', 'text');

        // Add another tool button — appends template content to the editor (visible when method = 'text').
        // Template payload stashed inside a hidden <template> element to avoid the js_call_amd 1024-char limit.
        if ($templatecontent !== '') {
            $templateelementid = 'genaiuse_tooluse_template_html';
            $addtoolbtn = \html_writer::tag(
                'button',
                '+ ' . s(get_string('tooluse_add_another', 'assignsubmission_genaiuse')),
                [
                    'type' => 'button',
                    'class' => 'btn btn-primary btn-sm submission_genaiuse_addtool',
                    'data-editor-id' => 'id_genaiuse_tooluse_editor',
                    'data-template-id' => $templateelementid,
                ]
            );
            $hiddentpl = \html_writer::tag('template', $templatecontent, ['id' => $templateelementid]);
            $mform->addElement(
                'static',
                'genaiuse_tooluse_addbutton',
                '',
                $addtoolbtn . $hiddentpl
            );
            $mform->hideIf('genaiuse_tooluse_addbutton', 'genaiuse_aiused', 'neq', $aiusedstr);
            $mform->hideIf('genaiuse_tooluse_addbutton', 'genaiuse_tooluse_method', 'neq', 'text');

            global $PAGE;
            $PAGE->requires->js_call_amd(
                'assignsubmission_genaiuse/addtool',
                'init',
                ['.submission_genaiuse_addtool']
            );
        }

        // Upload instructions (visible when method = 'upload', and only if a downloadable template exists).
        if ($templateurl !== null) {
            $tooluseuploadtext = get_string(
                'tooluse_upload_text',
                'assignsubmission_genaiuse',
                \html_writer::link(
                    $templateurl,
                    get_string('tooluse_template_link', 'assignsubmission_genaiuse'),
                    ['target' => '_blank', 'rel' => 'noopener noreferrer']
                )
            );
            $mform->addElement(
                'static',
                'genaiuse_tooluse_upload_text',
                '',
                \html_writer::tag('p', $tooluseuploadtext)
            );
            $mform->hideIf('genaiuse_tooluse_upload_text', 'genaiuse_aiused', 'neq', $aiusedstr);
            $mform->hideIf('genaiuse_tooluse_upload_text', 'genaiuse_tooluse_method', 'neq', 'upload');
        }

        // Tool use file manager (visible when method = 'upload').
        $submissionid = $submission ? $submission->id : 0;
        $toolusefileoptions = $this->get_file_options();
        $data = file_prepare_standard_filemanager(
            $data,
            'genaiuse_tooluse',
            $toolusefileoptions,
            $this->assignment->get_context(),
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE,
            $submissionid
        );

        $mform->addElement('filemanager', 'genaiuse_tooluse_filemanager', '', null, $toolusefileoptions);
        $mform->hideIf('genaiuse_tooluse_filemanager', 'genaiuse_aiused', 'neq', $aiusedstr);
        $mform->hideIf('genaiuse_tooluse_filemanager', 'genaiuse_tooluse_method', 'neq', 'upload');

        // Pre-select the method on edit based on which kind of content already exists.
        if ($existingrecord) {
            if (!empty($existingrecord->tooluse)) {
                $data->genaiuse_tooluse_method = 'text';
            } else if ($this->count_files($existingrecord->submission, ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE) > 0) {
                $data->genaiuse_tooluse_method = 'upload';
            } else {
                $data->genaiuse_tooluse_method = '';
            }
        } else {
            $mform->setDefault('genaiuse_tooluse_method', '');
        }

        $mform->addElement('html', '</div></div>');

        // Card 3: Supporting evidence (optional).
        $mform->addElement(
            'html',
            '<div class="card submission_genaiuse_card submission_genaiuse_card_optional'
            . ' submission_genaiuse_card_collapsible mb-3">'
            . $cardheader(get_string('supportingevidence', 'assignsubmission_genaiuse'), $optionalbadge)
            . '<div class="card-body">'
        );

        $evidenceheadergroup = [];
        /*
        $evidenceheadergroup[] = $mform->createElement(
            'static',
            'genaiuse_evidence_text1',
            '',
            \html_writer::tag('p', get_string('supportingevidence_text1', 'assignsubmission_genaiuse'))
        );
        */

        $fileoptions = $this->get_file_options();
        $data = file_prepare_standard_filemanager(
            $data,
            'genaiuse_evidence',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submissionid
        );

        $evidenceheadergroup[] = $mform->createElement('filemanager', 'genaiuse_evidence_filemanager', '', null, $fileoptions);

        $mform->addGroup($evidenceheadergroup, 'genaiuse_evidence_header_group', get_string('supportingevidence_text1', 'assignsubmission_genaiuse'), '<div class="w-100"></div>', false);
        $mform->hideIf('genaiuse_evidence_header_group', 'genaiuse_aiused', 'eq', '');

        $mform->addElement('html', '</div></div>');

        // Card 4: OneDrive link (optional, only when enabled on the assignment).
        if (!empty($this->get_config('onedrivelinkenabled'))) {
            $mform->addElement(
                'html',
                '<div class="card submission_genaiuse_card submission_genaiuse_card_optional'
                . ' submission_genaiuse_card_collapsible mb-3">'
                . $cardheader(get_string('onedrive', 'assignsubmission_genaiuse'), $optionalbadge)
                . '<div class="card-body">'
            );

            $assistanceurl = get_config('assignsubmission_genaiuse', 'onedriveassistance');
            $onedriveelements = [];
            /*
            $onedriveelements[] = $mform->createElement(
                'static',
                'onedrivelink_instructions',
                '',
                \html_writer::tag('p', get_string('onedrivelink', 'assignsubmission_genaiuse'))
            );
            */

            $onedriveelements[] = $mform->createElement(
                'text',
                'genaiuse_onedrivelink',
                '',
                ['size' => 60]
            );
            $mform->setType('genaiuse_onedrivelink', PARAM_URL);

            if (!empty($assistanceurl)) {
                $onedriveelements[] = $mform->createElement(
                    'static',
                    'genaiuse_onedrivelink_assistance',
                    '',
                    \html_writer::link(
                        $assistanceurl,
                        get_string('onedriveassistance_link', 'assignsubmission_genaiuse'),
                        ['target' => '_blank', 'rel' => 'noopener noreferrer']
                    )
                );
            }

            $mform->addGroup(
                $onedriveelements,
                'genaiuse_onedrivelink_group',
                get_string('onedrivelink', 'assignsubmission_genaiuse'),
                '<div class="w-100"></div>',
                false
            );
            $mform->hideIf('genaiuse_onedrivelink_group', 'genaiuse_aiused', 'eq', '');

            $mform->addElement('html', '</div></div>');
        }

        // Conditional validation: require AI detail fields and acknowledgement only when AI is used.
        $mform->addFormRule(function ($values) use ($requiredrule, $hasack) {
            $errors = [];
            if (
                isset($values['genaiuse_aiused'])
                    && (int)$values['genaiuse_aiused'] === ASSIGNSUBMISSION_GENAIUSE_AI_USED
            ) {
                foreach (
                    [
                    'genaiuse_aitoolsused',
                    'genaiuse_aiusecontext',
                    'genaiuse_aicontentdesc',
                    'genaiuse_aimodification',
                    ] as $field
                ) {
                    if (empty(trim($values[$field] ?? ''))) {
                        $errors[$field] = $requiredrule;
                    }
                }
                if ($hasack && empty($values['genaiuse_ack_confirmed'])) {
                    $errors['genaiuse_ack_confirmed'] = get_string('ack_required', 'assignsubmission_genaiuse');
                }
                if (empty($values['genaiuse_tooluse_method'])) {
                    $errors['genaiuse_tooluse_method_group'] =
                        get_string('tooluse_method_required', 'assignsubmission_genaiuse');
                }
            }
            return empty($errors) ? true : $errors;
        });

        $mform->addElement('html', '</div>'); // Close main plugin div.

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

        $fileoptions = $this->get_file_options();
        $context = $this->assignment->get_context();

        // Save evidence files (regardless of method).
        $data = file_postupdate_standard_filemanager(
            $data,
            'genaiuse_evidence',
            $fileoptions,
            $context,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
            $submission->id
        );

        $aiused = (int)($data->genaiuse_aiused ?? ASSIGNSUBMISSION_GENAIUSE_AI_NOT_USED);
        $method = (string)($data->genaiuse_tooluse_method ?? '');

        // Tool use files: only save them when the user picked the "upload" method.
        // For "text" method or non-AI submissions, clear any previously stored files
        // so the saved record reflects the user's current choice.
        if ($aiused === ASSIGNSUBMISSION_GENAIUSE_AI_USED && $method === 'upload') {
            $data = file_postupdate_standard_filemanager(
                $data,
                'genaiuse_tooluse',
                $fileoptions,
                $context,
                'assignsubmission_genaiuse',
                ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE,
                $submission->id
            );
        } else {
            get_file_storage()->delete_area_files(
                $context->id,
                'assignsubmission_genaiuse',
                ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE,
                $submission->id
            );
        }

        $currentsubmission = $this->get_genaiuse_submission($submission->id);

        $record = new stdClass();
        $record->aiused = $aiused;

        if ($record->aiused == ASSIGNSUBMISSION_GENAIUSE_AI_USED) {
            $record->aitoolsused = $data->genaiuse_aitoolsused ?? '';
            $record->aiusecontext = $data->genaiuse_aiusecontext ?? '';
            $record->aicontentdesc = $data->genaiuse_aicontentdesc ?? '';
            $record->aimodification = $data->genaiuse_aimodification ?? '';
            if ($method === 'text') {
                $tooluseraw = $data->genaiuse_tooluse_editor ?? null;
                $record->tooluse = is_array($tooluseraw) ? ($tooluseraw['text'] ?? '') : ($tooluseraw ?? '');
            } else {
                $record->tooluse = '';
            }
        } else {
            $record->aitoolsused = null;
            $record->aiusecontext = null;
            $record->aicontentdesc = null;
            $record->aimodification = null;
            $record->tooluse = null;
        }

        $record->numfiles = $this->count_files($submission->id);

        if (!empty($this->get_config('onedrivelinkenabled'))) {
            $link = trim((string)($data->genaiuse_onedrivelink ?? ''));
            $record->onedrivelink = $link === '' ? null : $link;
        } else {
            $record->onedrivelink = null;
        }

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

        $user = core_user::get_user($submission->userid);
        if (!$user) {
            return '';
        }
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

            $ackcontent = get_config('assignsubmission_genaiuse', 'genaiuse_aiuseacknowledgementextra');
            if ((string)$ackcontent !== '') {
                $result .= \html_writer::tag('div', $ackcontent, ['class' => 'genaiuse_acknowledgement']);
            }

            // Tool use template download link.
            $templatehtml = $this->get_template_download_html();
            if (!empty($templatehtml)) {
                $result .= $templatehtml;
            }

            if ($record->numfiles > 0) {
                $result .= \html_writer::tag('h4', get_string('supportingevidence', 'assignsubmission_genaiuse'));
                $result .= $this->assignment->render_area_files(
                    'assignsubmission_genaiuse',
                    ASSIGNSUBMISSION_GENAIUSE_FILEAREA,
                    $submission->id
                );
            }

            // Tool use richtext field.
            if (!empty($record->tooluse)) {
                $result .= \html_writer::tag('h4', get_string('tooluse_heading', 'assignsubmission_genaiuse'));
                $result .= format_text($record->tooluse, FORMAT_HTML);
            }
        }

        if (!empty($record->onedrivelink)) {
            $result .= \html_writer::tag(
                'p',
                get_string('onedrivelink', 'assignsubmission_genaiuse') . ': '
                    . \html_writer::link($record->onedrivelink, s($record->onedrivelink))
            );
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
        $fs->delete_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE,
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

        $fs = get_file_storage();
        $fs->delete_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA
        );
        $fs->delete_area_files(
            $this->assignment->get_context()->id,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE
        );

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
        return [
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA => $this->get_name(),
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE => $this->get_name(),
        ];
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

        $toolusefiles = $fs->get_area_files(
            $contextid,
            'assignsubmission_genaiuse',
            ASSIGNSUBMISSION_GENAIUSE_FILEAREA_TOOLUSE,
            $sourcesubmission->id,
            'id',
            false
        );
        foreach ($toolusefiles as $file) {
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
            'genaiuse_aitoolsused' => new external_value(PARAM_TEXT, 'AI tools used.', VALUE_OPTIONAL),
            'genaiuse_aiusecontext' => new external_value(PARAM_TEXT, 'AI use context.', VALUE_OPTIONAL),
            'genaiuse_aicontentdesc' => new external_value(PARAM_TEXT, 'AI content description.', VALUE_OPTIONAL),
            'genaiuse_aimodification' => new external_value(PARAM_TEXT, 'AI output modification.', VALUE_OPTIONAL),
            'genaiuse_onedrivelink' => new external_value(PARAM_URL, 'OneDrive link to final submission.', VALUE_OPTIONAL),
        ];
    }

    /**
     * Return HTML to display in the view assignment page.
     *
     * Returns the site-wide pre-submission information configured by the
     * administrator and, when OneDrive is enabled for this assignment, the
     * site-wide OneDrive recommendation. Returns an empty string if neither
     * produces any content. Displayed on mod/assign/view.php.
     *
     * @return string
     */
    public function view_header() {
        global $OUTPUT;

        $presubmissioninformation = (string)get_config('assignsubmission_genaiuse', 'presubmissioninformation');

        $recommendation = '';
        if (!empty($this->get_config('onedrivelinkenabled'))) {
            $recommendation = (string)get_config('assignsubmission_genaiuse', 'onedriverecommendation');
        }

        if ($presubmissioninformation === '' && $recommendation === '') {
            return '';
        }

        $context = [
            'haspresubmissioninformation' => $presubmissioninformation !== '',
            'presubmissioninformation' => $presubmissioninformation,
            'hasonedriverecommendation' => $recommendation !== '',
            'onedriverecommendation' => $recommendation,
        ];
        return $OUTPUT->render_from_template('assignsubmission_genaiuse/view_header', $context);
    }
}
