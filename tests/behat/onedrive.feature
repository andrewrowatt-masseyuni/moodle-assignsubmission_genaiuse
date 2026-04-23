@assignsubmission @assignsubmission_genaiuse
Feature: OneDrive link submission field for Generative AI use statement

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
    And I change the window size to "large"

  @javascript
  Scenario: OneDrive link field is hidden when the assignment has OneDrive disabled
    Given the following "activity" exists:
      | activity                            | assign             |
      | course                              | C1                 |
      | name                                | No OneDrive assign |
      | submissiondrafts                    | 0                  |
      | assignsubmission_genaiuse_enabled   | 1                  |
      | assignsubmission_onlinetext_enabled | 1                  |
    When I am on the "No OneDrive assign" Activity page logged in as student1
    And I press "Add submission"
    Then I should not see "OneDrive link"

  @javascript
  Scenario: Student sees the OneDrive link field and recommendation when enabled
    Given the following config values are set as admin:
      | onedriveassistance     | https://support.microsoft.com/onedrive        | assignsubmission_genaiuse |
      | onedriverecommendation | <p>Please use OneDrive for your drafts.</p>   | assignsubmission_genaiuse |
    And the following "activity" exists:
      | activity                                | assign                |
      | course                                  | C1                    |
      | name                                    | OneDrive assign       |
      | submissiondrafts                        | 0                     |
      | assignsubmission_genaiuse_enabled       | 1                     |
      | assignsubmission_genaiuse_onedrivelink  | 1                     |
      | assignsubmission_onlinetext_enabled     | 1                     |
    When I am on the "OneDrive assign" Activity page logged in as student1
    Then I should see "Please use OneDrive for your drafts."
    And I press "Add submission"
    And I should see "OneDrive link"
    And I should see "How to create a OneDrive link"

  @javascript
  Scenario: Student can submit a OneDrive link when the field is enabled
    Given the following "activity" exists:
      | activity                                | assign                |
      | course                                  | C1                    |
      | name                                    | OneDrive assign       |
      | submissiondrafts                        | 0                     |
      | assignsubmission_genaiuse_enabled       | 1                     |
      | assignsubmission_genaiuse_onedrivelink  | 1                     |
      | assignsubmission_onlinetext_enabled     | 1                     |
    And I am on the "OneDrive assign" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My draft submission."
    And I click on "No AI Used" "radio"
    And I set the field "genaiuse_onedrivelink" to "https://example.com/onedrive/share/abc"
    And I press "Save changes"
    Then I should see "No generative AI was used"
