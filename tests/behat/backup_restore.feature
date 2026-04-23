@assignsubmission @assignsubmission_genaiuse
Feature: Backup and restore of assignments with Generative AI use statement submissions
  In order to reuse my assignments
  As a teacher
  I need to be able to back them up and restore them with student AI use declarations.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activity" exists:
      | activity                            | assign          |
      | course                              | C1              |
      | name                                | Test assignment |
      | submissiondrafts                    | 0               |
      | assignsubmission_genaiuse_enabled   | 1               |
      | assignsubmission_onlinetext_enabled | 1               |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I change the window size to "large"

  @javascript
  Scenario: Backup and restore a course with a no AI used submission
    Given I am on the "Test assignment" Activity page logged in as student1
    And I press "Add submission"
    And I set the field "Online text" to "My original work without AI."
    And I click on "No AI Used" "radio"
    And I press "Save changes"
    And I log out
    When I log in as "admin"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 1 restored |
      | Schema | Course short name | C1R                |
    And I am on the "Course 1 restored" "course" page
    And I follow "Test assignment"
    And I go to "Student 1" "Test assignment" activity advanced grading page
    Then I should see "No generative AI was used"

  @javascript
  Scenario: Backup and restore a course with an AI used submission
    Given I am on the "Test assignment" Activity page logged in as student1
    And I press "Add submission"
    And I set the field "Online text" to "My submission with AI help."
    And I click on "#id_genaiuse_aiused_1" "css_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "generating draft text"
    And I set the field "genaiuse_aicontentdesc" to "sample paragraphs"
    And I set the field "genaiuse_aimodification" to "rewrote all sections"
    And I press "Save changes"
    And I log out
    When I log in as "admin"
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 1 restored |
      | Schema | Course short name | C1R                |
    And I am on the "Course 1 restored" "course" page
    And I follow "Test assignment"
    And I go to "Student 1" "Test assignment" activity advanced grading page
    Then I should see "Generative AI was used"
