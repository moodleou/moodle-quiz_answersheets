@mod @mod_quiz @quiz @quiz_answersheets
Feature: Attempt sheet the Export attempt report
  In order to use the question type that have dropdown list
  As a teacher
  I need to see the horizontal/vertical list of choice instead of the dropdown list

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

  @javascript
  Scenario: Dropdown list in Attempt sheet will be converted to list
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I add a "Select missing words" question filling the form with:
      | Question name            | Select missing words          |
      | Question text            | The [[1]] [[2]] on the [[3]]. |
      | General feedback         | The cat sat on the mat.       |
      | id_choices_0_answer      | cat                           |
      | id_choices_1_answer      | sat                           |
      | id_choices_2_answer      | mat                           |
    And I add a "Matching" question filling the form with:
      | Question name                      | Matching                                       |
      | Question text                      | Match the country with the capital city.       |
      | General feedback                   | England=London, France=Paris and Spain=Madrid. |
      | id_subquestions_0                  | England                                        |
      | id_subanswers_0                    | London                                         |
      | id_subquestions_1                  | France                                         |
      | id_subanswers_1                    | Paris                                          |
      | id_subquestions_2                  | Spain                                          |
      | id_subanswers_2                    | Madrid                                         |
      | For any correct response           | Your answer is correct                         |
      | For any partially correct response | Your answer is partially correct               |
      | For any incorrect response         | Your answer is incorrect                       |
      | Hint 1                             | This is your first hint                        |
      | Hint 2                             | This is your second hint                       |
    And quiz "Quiz 1" contains the following questions:
      | question             | page |
      | Select missing words | 1    |
      | Matching             | 2    |
    And user "student1" has started an attempt at quiz "Quiz 1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I navigate to "Results > Export attempts" in current page administration
    When I click on "Attempt sheet" "link" in the "Student One" "table_row"
    And I should see "[cat | sat | mat]"
    And I should see "London"
    And I should see "Paris"
    And I should see "Madrid"
