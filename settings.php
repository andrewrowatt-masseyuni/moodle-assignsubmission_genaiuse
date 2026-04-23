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
 * Settings for Generative AI use statement assign submission plugin
 *
 * @package    assignsubmission_genaiuse
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configcheckbox(
    'assignsubmission_genaiuse/default',
    new lang_string('default', 'assignsubmission_genaiuse'),
    new lang_string('default_help', 'assignsubmission_genaiuse'),
    0
));

// Maximum number of evidence files.
$options = [];
for ($i = 1; $i <= 20; $i++) {
    $options[$i] = $i;
}
$settings->add(new admin_setting_configselect(
    'assignsubmission_genaiuse/maxfiles',
    new lang_string('maxfiles', 'assignsubmission_genaiuse'),
    new lang_string('maxfiles_help', 'assignsubmission_genaiuse'),
    5,
    $options
));

// Maximum evidence file size.
$settings->add(new admin_setting_configselect(
    'assignsubmission_genaiuse/maxbytes',
    new lang_string('maxbytes', 'assignsubmission_genaiuse'),
    new lang_string('maxbytes_help', 'assignsubmission_genaiuse'),
    0,
    get_max_upload_sizes()
));

// Pre-submission information for students.
$settings->add(new admin_setting_confightmleditor(
    'assignsubmission_genaiuse/presubmissioninformation',
    new lang_string('presubmissioninformation', 'assignsubmission_genaiuse'),
    new lang_string('presubmissioninformation_help', 'assignsubmission_genaiuse'),
    '',
    PARAM_RAW,
    '',
    '20'
));

// AI use acknowledgement content shown on the submission form when AI use is declared.
$settings->add(new admin_setting_confightmleditor(
    'assignsubmission_genaiuse/genaiuse_aiuseacknowledgementextra',
    new lang_string('genaiuse_aiuseacknowledgementextra', 'assignsubmission_genaiuse'),
    new lang_string('genaiuse_aiuseacknowledgementextra_help', 'assignsubmission_genaiuse'),
    '',
    PARAM_RAW,
    '',
    '20'
));

// Tool use template text (richtext default for the Tool use editor field on the submission form).
$settings->add(new admin_setting_confightmleditor(
    'assignsubmission_genaiuse/toolusetemplatecontent',
    new lang_string('toolusetemplatecontent', 'assignsubmission_genaiuse'),
    new lang_string('toolusetemplatecontent_help', 'assignsubmission_genaiuse'),
    '',
    PARAM_RAW,
    '',
    '20'
    
));

// Tool use template (optional Word document).
$settings->add(new admin_setting_configstoredfile(
    'assignsubmission_genaiuse/toolusetemplate',
    new lang_string('toolusetemplate', 'assignsubmission_genaiuse'),
    new lang_string('toolusetemplate_help', 'assignsubmission_genaiuse'),
    'submission_template',
    0,
    ['accepted_types' => ['.docx']]
));

// OneDrive assistance URL.
$settings->add(new admin_setting_configtext(
    'assignsubmission_genaiuse/onedriveassistance',
    new lang_string('onedriveassistance', 'assignsubmission_genaiuse'),
    new lang_string('onedriveassistance_help', 'assignsubmission_genaiuse'),
    '',
    PARAM_URL
));

// OneDrive recommendation (rich text) shown on the assignment view page when enabled.
$settings->add(new admin_setting_confightmleditor(
    'assignsubmission_genaiuse/onedriverecommendation',
    new lang_string('onedriverecommendation', 'assignsubmission_genaiuse'),
    new lang_string('onedriverecommendation_help', 'assignsubmission_genaiuse'),
    ''
));
