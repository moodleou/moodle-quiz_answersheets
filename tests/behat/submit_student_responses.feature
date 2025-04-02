@mod @mod_quiz @quiz @quiz_answersheets
Feature: Submit student responses feature of the Answer sheets report
  In order to submit student responses for an in-progress attempt
  As a teacher
  I need to see Submit responses... link

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | The       | Teacher  |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | idnumber |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |

  @javascript
  Scenario: Submit responses link do not exist for Student do not have any attempt yet
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I set the field "Attempts from" to "enrolled_with"
    And I press "Show report"
    Then I should see "Attempts: 0"
    And I should see "Nothing to display"
    And I set the field "Attempts from" to "enrolled_any"
    And I press "Show report"
    And I should see "Student One"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "-"

  @javascript
  Scenario: Submit responses link available for in-progress attempt
    Given user "student1" has started an attempt at quiz "Quiz 1"
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "Submit responses..."
    And I click on "Submit responses..." "link" in the "Student One" "table_row"
    And I should see "First question"
    And I set the field "False" to "1"
    And I click on "Submit responses on behalf of Student One (student1) and finish attempt" "button"
    And I should see "Are you sure you want to submit?" in the ".modal-body" "css_element"
    And I click on "Save changes" "button"
    And "Student One" row "Status" column of "answersheets" table should contain "Finished"

  @javascript
  Scenario: Submit responses link available for overdue
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher"
    And I set the following fields to these values:
      | id_timeclose_enabled | 1           |
      | id_timeclose_year    | 2018        |
      | id_overduehandling   | graceperiod |
    And I press "Save and display"
    And I run the scheduled task "mod_quiz\task\update_overdue_attempts"
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "Status" column of "answersheets" table should contain "Overdue"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "Submit responses..."
    And I click on "Submit responses..." "link" in the "Student One" "table_row"
    And I should not see "You can preview this quiz, but if this were a real attempt, you would be blocked because:"
    And I should see "First question"
    And I set the field "False" to "1"
    And I click on "Submit responses on behalf of Student One (student1) and finish attempt" "button"
    And I should see "Are you sure you want to submit?" in the ".modal-body" "css_element"
    And I click on "Save changes" "button"
    And "Student One" row "Status" column of "answersheets" table should contain "Finished"

  @javascript
  Scenario: Submit responses for a closed quiz
    Given I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher"
    And I set the following fields to these values:
      | id_timeclose_enabled | 1    |
      | timeclose[year]      | 2018 |
    And I press "Save and display"
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I should see "Student One"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I should see "Are you sure you want to create a quiz attempt for Student One (student1@example.com)?" in the ".modal .modal-body" "css_element"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "Submit responses..."
    And I click on "Submit responses..." "link" in the "Student One" "table_row"
    And I should see "First question"
    And I set the field "True" to "1"
    And I click on "Submit responses on behalf of Student One (student1) and finish attempt" "button"
    And I should see "Are you sure you want to submit?" in the ".modal-body" "css_element"
    And I click on "Save changes" "button"
    And "Student One" row "Status" column of "answersheets" table should contain "Finished"
    And I click on "Review sheet" "link"
    And I should see "1.00/1.00" in the "Marks" "table_row"
