@mod @mod_quiz @quiz @quiz_answersheets
Feature: Review sheet of the Export attempt report
  In order to make sure the Export attempt report work with both normal question and open question
  As a teacher
  I need to see correct/incorrect feedback for both normal question and open question

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
      | questioncategory | qtype     | name | questiontext    |
      | Test questions   | truefalse | TF1  | First question  |
      | Test questions   | essay     | ES1  | Second question |
      | Test questions   | truefalse | TF2  | Third question  |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | ES1      | 2    |
      | TF2      | 3    |

  @javascript
  Scenario: Review sheet will show the correct and incorrect feedback for both normal and open question
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And user "student1" has checked answers in their attempt at quiz "Quiz 1":
      | slot | response |
      | 1    | False    |
      | 3    | True     |
    And user "student1" has finished an attempt at quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    When I click on "Review sheet" "link" in the "Student One" "table_row"
    Then ".text-success" "css_element" should exist in the ".que.truefalse.deferredfeedback.incorrect" "css_element"
    And ".text-danger" "css_element" should exist in the ".que.truefalse.deferredfeedback.incorrect" "css_element"
    And ".text-success" "css_element" should exist in the ".que.truefalse.deferredfeedback.correct" "css_element"
    And ".text-danger" "css_element" should exist in the ".que.truefalse.deferredfeedback.correct" "css_element"

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

  @javascript
  Scenario: 'Marked out of' text not display when I do not tick on checkbox on multiples sheets.
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show \"Marked out of\" text" "checkbox"
    And I click on "Show report" "button"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should not see "Marked out of 1.00"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show \"Marked out of\" text" "checkbox"
    And I click on "Show report" "button"
    And I click on "Submit responses..." "link"
    And I should not see "Marked out of 1.00"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show \"Marked out of\" text" "checkbox"
    And I click on "Show report" "button"
    # There is link with the same name "Attempt sheets" in the head, so we are using css_element to identify the correct link.
    And I click on "Attempt sheet" "link" in the "tbody" "css_element"
    And I should not see "Marked out of 1.00"

  @javascript
  Scenario: 'Marked out of' text display with default ticked checkbox on multiples sheets.
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show report" "button"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "Marked out of 1.00"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show report" "button"
    And I click on "Submit responses..." "link"
    And I should see "Marked out of 1.00"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Show report" "button"
    # There is link with the same name "Attempt sheets" in the head, so we are using css_element to identify the correct link.
    And I click on "Attempt sheet" "link" in the "tbody" "css_element"
    And I should see "Marked out of 1.00"

  @javascript
  Scenario: Right answer is display with right answer sheet
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "admin"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And I click on "Right answer sheet" "link"
    And I should see "You should have selected true."
