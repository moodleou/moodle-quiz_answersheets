@mod @mod_quiz @quiz @quiz_answersheets @quiz_answersheets_create_attempt
Feature: Creating attempts using the Answer sheets report
  In order to create new user's attempt for Quiz
  As a teacher
  I need to access Answer sheets report and see Create Attempt column

  Background: Using the Answer sheets report
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | The       | Teacher  |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
      | student3 | Student   | Three    |
      | student4 | Student   | Four     |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | idnumber | attempts |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | 2        |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |

  @javascript
  Scenario: "Create attempt" button should be visible when student does not have any attempts
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I set the field "Attempts from" to "enrolled_any"
    And I click on "Show report" "button"
    And "Create Attempt" "button" should exist in the "Student One" "table_row"

  @javascript
  Scenario: "Create attempt" button should be visible when student does have finished attempts but less than max attempts .
    Given user "student2" has started an attempt at quiz "Quiz 1"
    And user "student2" has finished an attempt at quiz "Quiz 1"
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I set the field "Attempts from" to "enrolled_any"
    And I click on "Show report" "button"
    And "Create Attempt" "button" should exist in the "Student Two" "table_row"

  @javascript
  Scenario: "Create attempt" button should not be visible when student have "in progress" attempt.
    Given user "student3" has started an attempt at quiz "Quiz 1"
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I set the field "Attempts from" to "enrolled_any"
    And I click on "Show report" "button"
    And "Create Attempt" "button" should not exist in the "Student Three" "table_row"

  @javascript
  Scenario: "Create attempt" button should show dialog and create new attempt with highlight
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I set the field "Attempts from" to "enrolled_any"
    And I click on "Show report" "button"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I should see "Are you sure you want to create a quiz attempt for Student One (student1@example.com)?" in the ".modal .modal-body" "css_element"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And I should see "In progress" in the "Student One" "table_row"
    And ".lastchanged" "css_element" should exist in the "Student One" "table_row"

  @javascript
  Scenario: Cancel button in the dialogue really cancels
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I set the field "Attempts from" to "enrolled_any"
    And I click on "Show report" "button"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I should see "Are you sure you want to create a quiz attempt for Student One (student1@example.com)?" in the ".modal .modal-body" "css_element"
    And I click on "Cancel" "button" in the ".modal.show" "css_element"
    And I should see "Create Attempt" in the "Student One" "table_row"
