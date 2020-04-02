@mod @mod_quiz @quiz @quiz_answersheets
Feature: Test parts of the bulk download feature
  In order to get all attempts at a quiz out of Moodle
  As an administrator
  I need to be able to download all review sheets in bulk

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

  Scenario: Answer sheets report works when there are attempts
    Given user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Export attempts" in current page administration
    And I follow "Download review sheets in bulk"
    Then I should see "To be able to download review sheets in bulk"
    And following "bulk download steps file" should download between "400" and "600" bytes
