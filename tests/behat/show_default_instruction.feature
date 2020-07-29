@mod @mod_quiz @quiz @quiz_answersheets
Feature: Test show default instruction text of question
  In order to show question instruction text on report attempt sheets
  As an administrator
  I need to be able to show instruction text if I tick on check box

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
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |

  @javascript
  Scenario: Instruction text not display when I do not tick on checkbox
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I click on "Show default instruction text?" "checkbox"
    And I click on "Show report" "button"
    And I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I should not see "Select the correct answer."

  @javascript
  Scenario: Instruction text display with default ticked checkbox
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I should see "Select the correct answer."
