<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'quiz_answersheets', language 'en'
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['answersheets'] = 'Export attempts';
$string['answersheetsfilename'] = 'Exportable_quiz_attempts';
$string['answersheetsreport'] = 'Export quiz attempts';
$string['admin_instruction_message'] = 'Instruction message';
$string['admin_instruction_message_des'] = 'If set, this text will be shown at the top of the report. You can use this, for example, to link to any institutional policies about printing summative quizzes.';
$string['answersheets:bulkdownload'] = 'Download review sheet in bulk';
$string['answersheets:componentname'] = 'Export quiz attempts';
$string['answersheets:createattempt'] = 'Create an attempt for another user';
$string['answersheets:submitresponses'] = 'Submit student responses';
$string['answersheets:view'] = 'View attempt sheet';
$string['answersheets:viewrightanswers'] = 'View right answer sheet';
$string['answer_sheet_label'] = 'Right answer sheet';
$string['attempt_sheet_label'] = 'Attempt sheet';
$string['answer_sheet_title'] = '{$a->courseshortname} - {$a->quizname} - Answer sheet';
$string['attempt_sheet_title'] = '{$a->courseshortname} - {$a->quizname} - Attempt sheet';
$string['bulkdownloadlink'] = 'Download review sheets in bulk';
$string['bulkinstructions'] = 'To be able to download review sheets in bulk, you need the
`save-answersheets` tool on your computer. Once you have that:

1. The attempts that will be downloaded when you follow these instructions are based on the settings of the report you just left.
   What this process will do is effectively follow every **Review sheet** link there. So, if you are in any doubt, go back and
   check the report is showing the attempts you want exported.
2. Once you are satisfied, download the [bulk download steps file]({$a->scripturl}) that will tell `save-answersheets` what to do.
   **Don\'t forget the warning above!**
3. Save that file (which should be called `{$a->scriptname}-steps.txt`) in the same folder where you have `save-answersheets`
   on your computer.
4. Open a command prompt and go to that folder.
5. Type the command `.\save-answersheets \'{$a->scriptname}-steps.txt\'` and wait for it to run. It outputs what it is doing as it goes.
6. Once the script has finished, you should have a file `{$a->scriptname}.zip` inside the `output` folder.
7. Remember to delete the `{$a->scriptname}-steps.txt` file.

If you only want the files for one student, you can run a command like
`.\save-answersheets --download-only \'X1234567\' \'{$a->scriptname}-steps.txt\'`

If you only need the attachments, without the PDF of the review page, then add `--skip-pdfs` to the command. This is much faster.
Example command: `.\save-answersheets --skip-pdfs \'{$a->scriptname}-steps.txt\'`.

These two options can be combined, e.g. `.\save-answersheets --skip-pdfs --download-only \'X1234567\' \'{$a->scriptname}-steps.txt\'`.

If you run any of these commands again, they will just download files which have not already been fetched. This can be helpful,
for example if just a few additional students have attempted the quiz.';
$string['bulkinstructionstitle'] = 'Instructions for downloading review sheets in bulk';
$string['bulkinstructionswarning'] = '<b>Warning</b>! the file you download in Step 2 of the instructions below contains enough
information for the tool to access the quiz attempts to be saved using your current login session. You <b>must</b> delete
that file as soon as you have finished with it. Retaining it is a security risk.';
$string['column_answer_sheet'] = 'Answer sheets';
$string['column_attempt_sheet'] = 'Attempt sheets';
$string['column_submit_student_responses'] = 'Submit student responses';
$string['combine_feedback_correct'] = 'If correct:';
$string['combine_feedback_general'] = 'General feedback and further information:';
$string['combine_feedback_incorrect'] = 'If incorrect:';
$string['combine_feedback_partially_correct'] = 'If partially correct:';
$string['create_attempt'] = 'Create Attempt';
$string['create_attempt_modal_button'] = 'Create';
$string['create_attempt_modal_description'] = 'Are you sure you want to create a quiz attempt for {$a}?';
$string['create_attempt_modal_title'] = 'Confirmation';
$string['event_attempt_created'] = 'Quiz attempt created for user';
$string['event_attempt_viewed'] = 'Quiz attempt sheet viewed';
$string['event_attempt_printed'] = 'Quiz attempt sheet printed';
$string['event_right_answer_viewed'] = 'Quiz right answer sheet viewed';
$string['event_right_answer_printed'] = 'Quiz right answer sheet printed';
$string['event_responses_submitted'] = 'Quiz responses submitted for user';
$string['examcode'] = 'Confirmation code';
$string['interactive_content_warning'] = 'Interactive content is not available in this format.';
$string['no_response_recorded'] = 'No response recorded.';
$string['page_type_attempt'] = 'Attempt sheet';
$string['page_type_review'] = 'Review sheet';
$string['page_type_answer'] = 'Answer sheet';
$string['pluginname'] = 'Export quiz attempts';
$string['print'] = 'Print';
$string['privacy:metadata'] = 'The Export quiz attempts plugin does not store any personal data itself. It provides an additional interface for viewing and managing the data owned by the quiz activity.';
$string['print_header'] = '{$a->courseshortname} {$a->quizname} for {$a->studentname} generated {$a->generatedtime} - {$a->sheettype}';
$string['print_header_minimised'] = '{$a->courseshortname} {$a->quizname} generated {$a->generatedtime} - {$a->sheettype}';
$string['review_sheet_label'] = 'Review sheet';
$string['review_sheet_title'] = '{$a->courseshortname} - {$a->quizname} - Review sheet';
$string['response_recorded'] = 'Response recorded: {$a}.';
$string['showmarkedoutoftext'] = 'Show "Marked out of" text?';
$string['showquestioninstruction'] = 'Show default instruction text?';
$string['showuserinfo'] = 'Identifying information to show about users';
$string['submit_student_responses_label'] = 'Submit responses...';
$string['submit_student_responses_on_behalf'] = 'Submit responses on behalf of {$a} and finish attempt';
$string['submit_student_responses_dialog_content'] = 'Are you sure you want to submit?';
$string['submit_student_responses_title'] = '{$a}: Submit student responses';
$string['strftime_header'] = '%d %b %Y, %H:%M';
$string['user_identity_fields'] = ' ({$a})';
$string['webservicecannotcreateattempts'] = 'Cannot create attempt';

// Question instruction.
$string['coderunner_instruction'] = 'Write your answer in the space provided.';
$string['ddwtos_instruction'] = 'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, (2) means twice etc. An asterisk (*) means that the items’ use is unlimited.';
$string['ddmarker_instruction'] = 'Mark the points on the image and write the letter of corresponding answer (A, B, C, D, …) beside them.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, (2) means twice etc. An asterisk (*) means that the items’ use is unlimited.';
$string['ddimageortext_instruction'] = 'Mark the points on the image and write the letter of corresponding answer (A, B, C, D, …) beside them.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, (2) means twice etc. An asterisk (*) means that the items’ use is unlimited.';
$string['essay_instruction'] = 'Write your answer in the space provided.';
$string['match_instruction'] = 'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.';
$string['multichoice_instruction'] = 'Select the correct answer.';
$string['numerical_instruction'] = 'Write your answer (in numerical value) in the space provided.';
$string['ordering_instruction'] = 'Write the correct order in the space provided.';
$string['oumultiresponse_instruction'] = 'Select the correct answer(s).';
$string['pmatch_instruction'] = 'Write your answer in the space provided. Please keep it to a sentence or two.';
$string['pmatchjme_instruction'] = 'Write your answer in the space provided.';
$string['gapselect_instruction'] = 'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.';
$string['shortanswer_instruction'] = 'Write your answer in the space provided. Please keep it to a sentence or two.';
$string['stack_instruction'] = 'Write your answer in the space provided.';
$string['truefalse_instruction'] = 'Select the correct answer.';
$string['varnumeric_instruction'] = 'Write your answer in the space provided.';
$string['varnumericset_instruction'] = 'Write your answer in the space provided.';
$string['varnumunit_instruction'] = 'Write your answer in the space provided.';
$string['wordselect_instruction'] = 'Select the answer(s) by circling the key word(s).';
