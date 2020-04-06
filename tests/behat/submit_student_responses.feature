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
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Export attempts" in current page administration
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    When I press "Show report"
    Then I should see "Attempts: 0"
    And I should see "Nothing to display"
    And I set the field "Attempts from" to "enrolled users who have, or have not, attempted the quiz"
    And I press "Show report"
    And I should see "Student One"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "-"

  @javascript
  Scenario: Submit responses link available for in-progress attempt
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    When I navigate to "Results > Export attempts" in current page administration
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "Submit responses..."
    When I click on "Submit responses..." "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I set the field "False" to "1"
    When I click on "Submit responses on behalf of Student One (student1) and finish attempt" "button"
    Then I should see "Are you sure you want to submit?" in the ".modal-body" "css_element"
    When I click on "Save changes" "button"
    Then "Student One" row "State" column of "answersheets" table should contain "Finished"

  @javascript
  Scenario: Submit responses link available for overdue
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_timeclose_enabled | 1           |
      | id_timeclose_year    | 2018        |
      | id_overduehandling   | graceperiod |
    And I press "Save and display"
    And I run the scheduled task "mod_quiz\task\update_overdue_attempts"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    When I navigate to "Results > Export attempts" in current page administration
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "State" column of "answersheets" table should contain "Overdue"
    And "Student One" row "Submit student responses" column of "answersheets" table should contain "Submit responses..."
    When I click on "Submit responses..." "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I set the field "False" to "1"
    When I click on "Submit responses on behalf of Student One (student1) and finish attempt" "button"
    Then I should see "Are you sure you want to submit?" in the ".modal-body" "css_element"
    When I click on "Save changes" "button"
    Then "Student One" row "State" column of "answersheets" table should contain "Never submitted"
