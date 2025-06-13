# Change log for the Export quiz attempts report


## Changes in 1.6

* This version works with Moodle 4.5 and 5.0.
* There are new settings added to control the feedbacks displayed (combined feedback, inline feedback and
  general feedback) on the Right answer sheet.


## Changes in 1.5

* Fix coding style.


## Changes in 1.4

* This version works with Moodle 4.5
* Support custom profile fields.
* Users can now export all embedded questions with student responses from the Embedded Question Report page.
* PrintQuiz: some display issues, mostly with equations, have been fixed.
* Users can now create attempt and update/upload responses even after quiz is closed.
* Export quiz attempts/STACK: preg_match_all() argument type exception when viewing Attempt sheet fixed.
* Fixed an exception in Quiz Answer sheets that was caused by an incorrect argument type being passed to
  the possible_user_info_visibility_settings function.
* Added a new feature for export attempt that can show/hide mark.
* Added oumatrix question to the answer sheet.
* Fixed a few other minor bugs.


## Changes in 1.3

* This version works with Moodle 4.0.
* Updated to use the new user field API (but note, this plugin does not yet
  support the display of custom profile fields.)
* Improved instructions for doing bulk downloads.


## Changes in 1.2

* Made it optional whether to include the message about how to answer each question.
* Response sheet now shows all the options for questions with drop-down menus.
* Review sheet now includes all the feedback, not just that relevant to the student's
  response (for some question types including multiple-choice and true/false).
* The option for whether to include user ID now only affects the sheets, not the report table.
* Fix error if you tried to sort the table using some of the headings.
* Fixed the bug where some print CSS in this plugin would affect everything.
* Fixed a few other minor bugs.
* The bulk export option is now more efficient.


## Changes in 1.1

* There is now a (slightly primitive) option to download all the attempts at a quiz
  using the helper script https://github.com/moodleou/save-answersheets.
* There are now setting for how much of the student's user identity is displayed.
* The name of the report, as it appears in Moodle's interface, was changed to
  'Export attempts' to better describe what it does.
* Fix some page layout issues with the generated PDFs.
* Fix details of the display of some question types.
* Tweak the page title when viewing an answer sheet. The main reason for doing this is
  that this determines the filename that web browsers use when saving or printing to PDF.
* A few other minor bug fixes.


## Changes in 1.0

* Initial version of the plugin.
