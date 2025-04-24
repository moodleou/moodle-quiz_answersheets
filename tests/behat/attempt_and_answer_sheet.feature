@mod @mod_quiz @quiz @quiz_answersheets
Feature: Attempt sheet, Review sheet and Answer sheet feature of the Answer sheets report
  In order to view an in-progress/finished attempt
  As a teacher
  I need to show Attempt sheet, Review sheet and Answer sheet links

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
      | activity | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | interactive        |
      | quiz     | Quiz 2 | Quiz 2 description | C1     | quiz2    | deferredfeedback   |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    | template    |
      | Test questions   | truefalse   | TF1  | First question  |             |
      | Test questions   | multichoice | MT1  | Second question | one_of_four |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | MT1      | 2    |

  @javascript
  Scenario: Attempt sheet, Answer sheet links do not exist for Student do not have any attempt yet
    Given I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    And I set the field "Attempts from" to "enrolled_with"
    When I press "Show report"
    Then I should see "Attempts: 0"
    And I should see "Nothing to display"
    And I set the field "Attempts from" to "enrolled_any"
    And I press "Show report"
    And I should see "Student One"
    And "Student One" row "Attempt sheets" column of "answersheets" table should contain "-"
    And "Student One" row "Answer sheets" column of "answersheets" table should contain "-"
    And I should see "Student Two"
    And "Student Two" row "Attempt sheets" column of "answersheets" table should contain "-"
    And "Student Two" row "Answer sheets" column of "answersheets" table should contain "-"

  @javascript
  Scenario: Attempt sheet, Answer sheet links available for in-progress attempt, Review sheet link available for finished attempt
    Given user "student1" has started an attempt at quiz "Quiz 1"
    And user "student1" has checked answers in their attempt at quiz "Quiz 1":
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then I should see "Attempts: 1"
    And I should see "Student One"
    And "Student One" row "Attempt sheets" column of "answersheets" table should contain "Attempt sheet"
    And "Student One" row "Answer sheets" column of "answersheets" table should contain "Right answer sheet"
    And I click on "Show \"General Feedback\"?" "checkbox"
    And I click on "Show report" "button"
    When I click on "Attempt sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I should not see "Check"
    And "table.quizreviewsummary" "css_element" should exist
    And I should see "Student One" in the "table.quizreviewsummary" "css_element"
    And I should not see "Started" in the "table.quizreviewsummary" "css_element"
    And I should not see "Status" in the "table.quizreviewsummary" "css_element"
    And I should see "Select the correct answer" in the ".question-instruction" "css_element"
    And I should not see "If incorrect:"
    And I should not see "If partially correct:"
    And I should not see "If correct:"
    And I should not see "General feedback and further information:"
    And I press the "back" button in the browser
    And I click on "Show \"General Feedback\"?" "checkbox"
    And I click on "Show report" "button"
    When I click on "Right answer sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And I should see "Second question"
    And I should see "If incorrect:"
    And I should see "If partially correct:"
    And I should see "If correct:"
    And I should see "General feedback and further information:"
    And I should see "One is the oddest."
    And I should see "Two is even."
    And I should see "Three is odd."
    And I should see "Four is even."
    And user "student1" has finished an attempt at quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
    Then "Student One" row "Attempt sheets" column of "answersheets" table should contain "Review sheet"
    And "Student One" row "Answer sheets" column of "answersheets" table should contain "-"
    When I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "First question"
    And "table.quizreviewsummary" "css_element" should exist
    And I should see "Student One" in the "table.quizreviewsummary" "css_element"
    And I should see "Started" in the "table.quizreviewsummary" "css_element"

  @javascript
  Scenario: Sheet's special message for un-submitted RecordRTC question type
    Given the qtype_recordrtc plugin is installed
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   | template |
      | Test questions   | recordrtc | RTC1 | Third question | audio    |
    And quiz "Quiz 2" contains the following questions:
      | question | page |
      | RTC1     | 1    |
    And user "student1" has started an attempt at quiz "Quiz 2"
    And I am on the "Quiz 2" "quiz_answersheets > Report" page logged in as "teacher"
    When I click on "Attempt sheet" "link" in the "Student One" "table_row"
    Then I should see "No recording"
    And "No response recorded." "text" in the "Third question" "question" should not be visible

  @javascript
  Scenario: Sheet's special message for submitted oumaxtrix question type
    Given the qtype_oumatrix plugin is installed
    When the following "questions" exist:
      | questioncategory | qtype    | name | questiontext    | template       |
      | Test questions   | oumatrix | OUM1 | Fourth question | animals_single |
    And quiz "Quiz 2" contains the following questions:
      | question | page |
      | OUM1     | 1    |
    And I am on the "quiz2" "Activity" page logged in as "student1"
    And I click on "Attempt quiz" "button"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I should see "That is not right at all."
    And I am on the "Quiz 2" "quiz_answersheets > Report" page logged in as "teacher"
    When I click on "Review sheet" "link" in the "Student One" "table_row"
    Then I should see "Fourth question"
    And I should see "That is not right at all."
    And I should see "We are recognising different type of animals."
    And I should see "The correct answers are:"
    And I should see "Bee → Insects"
    And I should see "Salmon → Fish"
    And I should see "Seagull → Birds"
    And I should see "Dog → Mammals"

  @javascript
  Scenario: Review sheet link available for finished attempt for oumatrix
    Given the qtype_oumatrix plugin is installed
    When the following "questions" exist:
      | questioncategory | qtype    | name | questiontext    | template       |
      | Test questions   | oumatrix | OUM1 | Fourth question | animals_single |
    And quiz "Quiz 2" contains the following questions:
      | question | page |
      | OUM1     | 1    |
    And user "student1" has started an attempt at quiz "Quiz 2"
    And I am on the "Quiz 2" "quiz_answersheets > Report" page logged in as "teacher"
    And I click on "Right answer sheet" "link" in the "Student One" "table_row"
    # OU Matrix response answer.
    Then I should see "Feedback" in the "Insects" "table_row"
    And I should see "Fourth question"
    And I should see "Flies and Bees are insects." in the "Bee" "table_row"
    And I should see "Cod, Salmon and Trout are fish." in the "Salmon" "table_row"
    And I should see "Gulls and Owls are birds." in the "Seagull" "table_row"
    And I should see "Cows, Dogs and Horses are mammals." in the "Dog" "table_row"
    # General feedback.
    And I should see "Well done!"
    And I should see "We are recognising different type of animals."
    And I should see "The correct answers are:"
    And I should see "Bee → Insects"
    And I should see "Salmon → Fish"
    And I should see "Seagull → Birds"
    And I should see "Dog → Mammals"

  @javascript
  Scenario: Sheet's special messgae for submitted oumultiresponse question type
    Given the qtype_oumultiresponse plugin is installed
    When the following "questions" exist:
      | questioncategory | qtype           | name          | questiontext   | template    |
      | Test questions   | oumultiresponse | OUM response1 | Third question | two_of_four |
    And quiz "Quiz 2" contains the following questions:
      | question      | page |
      | OUM response1 | 1    |
    And I am on the "quiz2" "Activity" page logged in as "student1"
    And I click on "Attempt quiz" "button"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    Then I should see "That is not right at all."
    And I am on the "Quiz 2" "quiz_answersheets > Report" page logged in as "teacher"
    And I click on "Review sheet" "link" in the "Student One" "table_row"
    And I should see "Third question"
    And I should see "One is odd."
    And I should see "Two is even."
    And I should see "Three is odd."
    And I should see "Four is odd."
    And the field "Three" in the ".oumultiresponse" "css_element" matches value "1"
    And the field "One" in the ".oumultiresponse" "css_element" matches value "1"
