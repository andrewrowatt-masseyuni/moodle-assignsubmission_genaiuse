@assignsubmission @assignsubmission_genaiuse
Feature: Form validation for the Generative AI use statement
  In order to ensure students provide a complete declaration
  As a student
  I am prevented from submitting an incomplete generative AI use statement

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
      | course                               | C1              |
      | name                                 | Test assignment |
      | submissiondrafts                     | 0               |
      | assignsubmission_genaiuse_enabled    | 1               |
      | assignsubmission_onlinetext_enabled  | 1               |
    And I change the window size to "large"

  @javascript
  Scenario: Submission cannot be saved without selecting an AI use option
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I press "Save changes"
    Then I should see "Required" in the "#fgroup_id_error_genaiuse_aiused_group" "css_element"
    And I should not see "No generative AI was used"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: AI Used selected without any details shows required errors on each AI field
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I press "Save changes"
    Then I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aitoolsused" "css_element"
    And I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aiusecontext" "css_element"
    And I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aicontentdesc" "css_element"
    And I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aimodification" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Partially completed AI Used details only triggers required errors on the empty fields
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I press "Save changes"
    Then I should not see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aitoolsused" "css_element"
    And I should not see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aiusecontext" "css_element"
    And I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aicontentdesc" "css_element"
    And I should see "This field is required. Use N/A if this field is not applicable." in the "#id_error_genaiuse_aimodification" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Tool use method must be selected when AI Used is declared
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I set the field "genaiuse_aicontentdesc" to "Outline structure"
    And I set the field "genaiuse_aimodification" to "Rewrote sections and verified facts"
    And I set the field "genaiuse_ack_confirmed" to "1"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No supporting evidence supplied']" "xpath_element"
    And I press "Save changes"
    Then I should see "Please choose how you will provide tool use details." in the "#fgroup_id_error_genaiuse_tooluse_method_group" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Acknowledgement is required when AI Used is selected even without configured extra acknowledgement content
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I set the field "genaiuse_aicontentdesc" to "Outline structure"
    And I set the field "genaiuse_aimodification" to "Rewrote sections and verified facts"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='Enter text']" "xpath_element"
    And I set the field "genaiuse_tooluse_editor[text]" to "tool use text"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No supporting evidence supplied']" "xpath_element"
    And I press "Save changes"
    Then I should see "You must confirm that you have read the AI use acknowledgement." in the "#id_error_genaiuse_ack_confirmed" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Enter text method requires non-empty tool use editor content
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I set the field "genaiuse_aicontentdesc" to "Outline structure"
    And I set the field "genaiuse_aimodification" to "Rewrote sections and verified facts"
    And I set the field "genaiuse_ack_confirmed" to "1"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='Enter text']" "xpath_element"
    And I set the field "genaiuse_tooluse_editor[text]" to "   "
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No supporting evidence supplied']" "xpath_element"
    And I press "Save changes"
    Then I should see "Please enter tool use details." in the "#id_error_genaiuse_tooluse_editor" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Upload document method requires at least one uploaded tool use file
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I set the field "genaiuse_aicontentdesc" to "Outline structure"
    And I set the field "genaiuse_aimodification" to "Rewrote sections and verified facts"
    And I set the field "genaiuse_ack_confirmed" to "1"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='Upload document']" "xpath_element"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No supporting evidence supplied']" "xpath_element"
    And I press "Save changes"
    Then I should see "Please upload at least one tool use document." in the "#id_error_genaiuse_tooluse_filemanager" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Acknowledgement field is not shown when no acknowledgement content is configured
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    Then I should not see "Read the AI use acknowledgement"
    When I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No AI Used']" "xpath_element"
    Then I should not see "Read the AI use acknowledgement"
    And I should see "I have read the acknowledgement above and agree to it."

  @javascript
  Scenario: Acknowledgement is required when AI Used is selected and acknowledgement content is configured
    Given the following config values are set as admin:
      | genaiuse_aiuseacknowledgementextra | <p>I will only use AI in line with the assessment instructions.</p> | assignsubmission_genaiuse |
    And I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My submission text."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='AI Used']" "xpath_element"
    And I set the field "genaiuse_aitoolsused" to "ChatGPT"
    And I set the field "genaiuse_aiusecontext" to "Drafting initial ideas"
    And I set the field "genaiuse_aicontentdesc" to "Outline structure"
    And I set the field "genaiuse_aimodification" to "Rewrote sections and verified facts"
    And I press "Save changes"
    Then I should see "You must confirm that you have read the AI use acknowledgement." in the "#id_error_genaiuse_ack_confirmed" "css_element"
    And I should not see "Generative AI was used"

  @javascript
  Scenario: Acknowledgement is required when No AI Used is selected and acknowledgement content is configured
    Given the following config values are set as admin:
      | genaiuse_aiuseacknowledgementextra | <p>I will only use AI in line with the assessment instructions.</p> | assignsubmission_genaiuse |
    And I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My original submission."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No AI Used']" "xpath_element"
    And I should see "I have read the acknowledgement above and agree to it."
    When I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No supporting evidence supplied']" "xpath_element"
    And I press "Save changes"
    Then I should see "You must confirm that you have read the AI use acknowledgement." in the "#id_error_genaiuse_ack_confirmed" "css_element"
    And I should not see "No generative AI was used"

  @javascript
  Scenario: Supporting evidence Yes/No choice is required when AI use has been declared
    Given I am on the "Test assignment" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My original submission."
    And I click on "//div[@class='submission_genaiuse_radio_title'][normalize-space(.)='No AI Used']" "xpath_element"
    And I press "Save changes"
    Then I should see "Please choose whether you have supporting evidence to upload." in the "#fgroup_id_error_genaiuse_evidence_choice_group" "css_element"
    And I should not see "No generative AI was used"
