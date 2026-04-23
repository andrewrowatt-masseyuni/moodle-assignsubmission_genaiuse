@assignsubmission @assignsubmission_genaiuse
Feature: Basic tests for Generative AI use statement

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

    And I change the window size to "large"

  @javascript
  Scenario: Plugin assignsubmission_genaiuse appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Generative AI use statement"
    And I should see "assignsubmission_genaiuse"

  @javascript
  Scenario: No AI use option is selected by default on a new submission
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    Then the field "Generative AI use declaration" matches value "Choose..."
    And I should not see "I acknowledge that any undeclared use of generative AI"
    And I should not see "When using AI, I have ensured that the work produced"

  @javascript
  Scenario: Student submits assignment declaring no AI was used
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "This is my original submission text."
    And I set the field "Generative AI use declaration" to "No AI Used"
    Then I should see "no generative AI tools or systems"
    And I should see "academic dishonesty"
    When I press "Save changes"
    And I am on the "Test assignment" Activity page
    Then I should see "No generative AI was used"

  @javascript
  Scenario: Student submits assignment declaring AI was used
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "This is my submission text with AI assistance."
    And I set the field "Generative AI use declaration" to "AI Used"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT (https://chat.openai.com)"
    And I set the field "genaiuse_aiusecontext" to "brainstorming ideas and generating draft text"
    And I set the field "genaiuse_aicontentdesc" to "an outline structure and sample paragraphs"
    And I set the field "genaiuse_aimodification" to "rewrote key sections and verified facts"
    And I press "Save changes"
    And I am on the "Test assignment" Activity page
    Then I should see "Generative AI was used"

  @javascript
  Scenario: Teacher can view student no AI used declaration
    Given I am on the "Test assignment" Activity page logged in as student1
    And I press "Add submission"
    And I set the field "Online text" to "My original work."
    And I set the field "Generative AI use declaration" to "No AI Used"
    And I press "Save changes"
    And I log out
    When I am on the "Test assignment" Activity page logged in as teacher1
    And I go to "Student 1" "Test assignment" activity advanced grading page
    Then I should see "No generative AI was used"

  @javascript
  Scenario: Teacher can view student AI used declaration details
    Given I am on the "Test assignment" Activity page logged in as student1
    And I press "Add submission"
    And I set the field "Online text" to "My submission with AI help."
    And I set the field "Generative AI use declaration" to "AI Used"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "generating draft text"
    And I set the field "genaiuse_aicontentdesc" to "sample paragraphs"
    And I set the field "genaiuse_aimodification" to "rewrote all sections"
    And I press "Save changes"
    And I log out
    When I am on the "Test assignment" Activity page logged in as teacher1
    And I go to "Student 1" "Test assignment" activity advanced grading page
    Then I should see "Generative AI was used"
