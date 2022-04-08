@mod @mod_quiz @quiz @quiz_answersheets
Feature: Basic use of the Answer sheets report
  In order to generate a paper version for Quiz
  As a teacher
  I need to access Answer sheets report

  Background: Using the Answer sheets report
    Given the following "custom profile fields" exist:
      | datatype | shortname | name  |
      | text     | alias     | Alias |
    And the following "users" exist:
      | username | firstname | lastname | email            | profile_field_alias | phone1 | phone2 |
      | teacher  | The       | Teacher  | teacher@asd.com  | T1                  | 888888 | 777777 |
      | student1 | Student   | One      | student1@asd.com | S1                  | 11111  | 22222  |
      | student2 | Student   | Two      | student2@asd.com | S2                  | 33333  | 44444  |
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
      | activity | name   | intro              | course | idnumber |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |

  @javascript
  Scenario: Answer sheets report works when there are no attempts
    Given I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
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
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    And I press "Show report"
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And I should not see "Student Two"
    And I set the field "Attempts from" to "enrolled users who have, or have not, attempted the quiz"
    And I press "Show report"
    And I should see "Student Two"

  @javascript
  Scenario: Instruction message will be displayed
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then ".instruction" "css_element" should not exist
    And the following config values are set as admin:
      | config              | value                    | plugin            |
      | instruction_message | Test instruction message | quiz_answersheets |
    And I reload the page
    And ".instruction" "css_element" should exist
    And I should see "Test instruction message"

  @javascript
  Scenario: Answer sheets report display field base on user identify.
    Given the following config values are set as admin:
      | showuseridentity | username,email,profile_field_alias,phone1,phone2 |
    And user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    And I press "Show report"
    Then "Student One" row "Alias" column of "answersheets" table should contain "S1"
    And "Student One" row "Email address" column of "answersheets" table should contain "student1@asd.com"
    And "Student One" row "Username" column of "answersheets" table should contain "student1"
    When I click on "Review sheet" "link"
    Then I should see "student1"
    And I should see "student1@asd.com"
    And I should see "11111"
    And I should see "22222"
    And I should see "S1"
    When I click on "Export attempts" "link"
    And I press "Show report"
    And I click on "Create Attempt" "button" in the "Student One" "table_row"
    And I click on "Create" "button" in the ".modal.show" "css_element"
    And I click on "Right answer sheet" "link"
    Then I should see "student1"
    And I should see "student1@asd.com"
    And I should see "11111"
    And I should see "22222"
    And I should see "S1"
    When I click on "Export attempts" "link"
    And I press "Show report"
    And I click on "Submit responses..." "link"
    Then I should see "student1"
    And I should see "student1@asd.com"
    And I should see "11111"
    And I should see "22222"
    And I should see "S1"
    When the following config values are set as admin:
      | showuseridentity | username |
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I set the field "Attempts from" to "enrolled users who have attempted the quiz"
    And I press "Show report"
    Then "Student One" row "Username" column of "answersheets" table should contain "student1"
    And "Student One" row "Alias" column of "answersheets" table should not contain "S1"
    And "Student One" row "Email address" column of "answersheets" table should not contain "student1@asd.com"
    When I click on "Review sheet" "link"
    Then I should see "student1"
    And I should not see "student1@asd.com"
    And I should not see "S1"
    And I should not see "11111"
    And I should not see "22222"
    When I click on "Export attempts" "link"
    And I press "Show report"
    And I click on "Right answer sheet" "link"
    Then I should see "student1"
    And I should not see "student1@asd.com"
    And I should not see "S1"
    And I should not see "11111"
    And I should not see "22222"
    When I click on "Export attempts" "link"
    And I press "Show report"
    And I click on "Submit responses..." "link"
    Then I should see "student1"
    And I should not see "student1@asd.com"
    And I should not see "S1"
    And I should not see "11111"
    And I should not see "22222"
