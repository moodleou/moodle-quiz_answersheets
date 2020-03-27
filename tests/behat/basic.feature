@mod @mod_quiz @quiz @quiz_answersheets
Feature: Basic use of the Answer sheets report
  In order to generate a paper version for Quiz
  As a teacher
  I need to access Answer sheets report

  Background: Using the Answer sheets report
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | The       | Teacher  |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
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
  Scenario: Answer sheets report works when there are no attempts
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Export attempts" in current page administration
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    Then I press "Show report"
    Then I should see "Attempts: 0"
    And I should see "Nothing to display"
    And I log out

  @javascript
  Scenario: Answer sheets report works when there are attempts
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Export attempts" in current page administration
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    When I press "Show report"
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And I should not see "Student Two"
    And I set the field "Attempts from" to "enrolled users who have, or have not, attempted the quiz"
    And I press "Show report"
    And I should see "Student Two"

  @javascript
  Scenario: Instruction message will be displayed
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    When I navigate to "Results > Export attempts" in current page administration
    Then ".instruction" "css_element" should not exist
    And the following config values are set as admin:
      | config              | value                    | plugin            |
      | instruction_message | Test instruction message | quiz_answersheets |
    And I reload the page
    And ".instruction" "css_element" should exist
    And I should see "Test instruction message"
