@mod @mod_quiz @quiz @quiz_answersheets
Feature: Attempt sheet and Review sheet feature of the Answer sheets report
  In order to view an in-progress/finished attempt
  As a teacher
  I need to show Attempt sheet and Review sheet link

  Background:
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
  Scenario: Attempt sheet link do not exist for Student do not have any attempt yet
    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    When I navigate to "Results > Answer sheets" in current page administration
    Then I should see "Attempts: 0"
    And I should see "Nothing to display"
    And I set the field "Attempts from" to "enrolled users who have, or have not, attempted the quiz"
    And I press "Show report"
    And I should see "Student One"
    And "Student One" row "Attempt sheets" column of "answersheets" table should contain "-"
    And I should see "Student Two"
    And "Student Two" row "Attempt sheets" column of "answersheets" table should contain "-"

  @javascript
  Scenario: Attempt sheet link available for in-progress attempt, Review sheet link available for finished attempt
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And user "student1" has checked answers in their attempt at quiz "Quiz 1":
      | slot | response |
      | 1    | True     |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    When I navigate to "Results > Answer sheets" in current page administration
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "Attempt sheets" column of "answersheets" table should contain "Attempt sheet"
    When I click on "Attempt sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And "table.quizreviewsummary" "css_element" should not exist
    And user "student1" has finished an attempt at quiz "Quiz 1"
    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Answer sheets" in current page administration
    Then "Student One" row "Attempt sheets" column of "answersheets" table should contain "Review sheet"
    When I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And "table.quizreviewsummary" "css_element" should exist
