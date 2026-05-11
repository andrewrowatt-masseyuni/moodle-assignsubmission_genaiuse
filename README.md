[![Moodle Plugin CI](https://github.com/andrewrowatt-masseyuni/moodle-assignsubmission_genaiuse/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/andrewrowatt-masseyuni/moodle-assignsubmission_genaiuse/actions/workflows/moodle-ci.yml)
# Generative AI Use Statement (assignsubmission_genaiuse)

An assignment submission plugin for Moodle that requires students to declare whether they used generative AI tools when completing their assignment.

## Features

- Students must declare **No AI Used** or **AI Used** as part of their submission.
- When AI is declared, students provide details including:
  - AI tools used (e.g. ChatGPT, Copilot)
  - Context of AI use (e.g. brainstorming, drafting)
  - Description of AI-generated content
  - How the content was modified
- Optional file upload for supporting evidence (e.g. chat logs, screenshots).
- Optional OneDrive link workflow (when enabled per assignment):
  - Students answer whether they have a OneDrive link
  - Students can paste a OneDrive link to their final submission
  - Optional guidance link can be shown to help students create a OneDrive share link
- Teachers can view AI use declarations on the grading page.

## Requirements

- Moodle 4.5 (2024100700)

## Installation

1. Copy the `genaiuse` folder to `mod/assign/submission/genaiuse` in your Moodle installation.
2. Visit **Site administration > Notifications** to complete the installation.
3. Enable the plugin in **Site administration > Plugins > Assignment > Submission plugins > Manage assignment submission plugins**.

## Configuration

Site-level settings are available under **Site administration > Plugins > Assignment > Submission plugins > Generative AI use statement**:

- **Enabled by default** - Whether the plugin is enabled by default for new assignments.
- **Maximum number of evidence files** - Maximum number of evidence files a student can upload (1-20, default 5).
- **Maximum file size** - Maximum size for each evidence file upload.
- **OneDrive assistance** - Optional URL shown to students as "How to create a OneDrive link".
- **OneDrive recommendation** - Optional rich-text recommendation shown on the assignment view page when OneDrive is enabled.

Per-assignment settings are available when editing an assignment under the **Submission types** section, including:

- Enabling/disabling this submission plugin for the assignment
- Enabling/disabling the OneDrive link field for that assignment

## Screenshots

### Submission screen example (Snap theme)

![Submission screen example](<docs/images/Submission screen example - 2026-05-08_05-51-55.png>)

### Completed example - AI Used

![Completed example - AI Used](<docs/images/Completed example - AI Used - 2026-05-08_06-59-38.png>)

### Completed example - No AI Used

![Completed example No AI Used](<docs/images/Completed example - No AI Used - 2026-05-08_06-54-01.png>)

## License

This plugin is licensed under the [GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html).

## Author

Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
