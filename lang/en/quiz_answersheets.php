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

$string['pluginname'] = 'Answer sheets';
$string['answersheets'] = 'Answer sheets';
$string['answersheetsfilename'] = 'Answer_sheets_report';
$string['answersheetsreport'] = 'Answer sheets report';
$string['admin_instruction_message'] = 'Instruction message';
$string['admin_instruction_message_des'] = 'If set, this text will be shown at the top of the report. You can use this, for example, to link to any institutional policies about printing summative quizzes.';
$string['answersheets:componentname'] = 'Answer sheets';
$string['answersheets:createattempt'] = 'Create an attempt for another user';
$string['answersheets:submitresponses'] = 'Submit student responses';
$string['answersheets:view'] = 'View attempt sheet';
$string['answersheets:viewrightanswers'] = 'View right answer sheet';
$string['answer_sheet_label'] = 'Right answer sheet';
$string['attempt_sheet_label'] = 'Attempt sheet';
$string['answer_sheet_title'] = '{$a}: Answer sheet';
$string['attempt_sheet_title'] = '{$a}: Attempt sheet';
$string['column_answer_sheet'] = 'Answer sheets';
$string['column_attempt_sheet'] = 'Attempt sheets';
$string['column_submit_student_responses'] = 'Submit student responses';
$string['event_attempt_created'] = 'Quiz Answer sheets attempt created';
$string['event_attempt_viewed'] = 'Quiz Answer sheets attempt viewed';
$string['event_attempt_printed'] = 'Quiz Answer sheets attempt printed';
$string['event_right_answer_viewed'] = 'Quiz Answer sheets right answer viewed';
$string['event_right_answer_printed'] = 'Quiz Answer sheets right answer printed';
$string['event_responses_submitted'] = 'Quiz Answer sheets responses submitted';
$string['review_sheet_label'] = 'Review sheet';
$string['review_sheet_title'] = '{$a}: Review sheet';
$string['page_type_attempt'] = 'Attempt sheet';
$string['page_type_review'] = 'Review sheet';
$string['page_type_answer'] = 'Answer sheet';
$string['print'] = 'Print';
$string['privacy:metadata'] = 'The Quiz Answer sheets plugin does not store any personal data itself. It provides an additional interface for viewing and managing the data owned by the quiz activity.';
$string['print_header'] = '{$a->courseshortname} {$a->quizname} for {$a->studentname} generated {$a->generatedtime} - {$a->sheettype}';
$string['submit_student_responses_label'] = 'Submit responses...';
$string['submit_student_responses_on_behalf'] = 'Submit responses on behalf of {$a} and finish attempt';
$string['submit_student_responses_dialog_content'] = 'Are you sure you want to submit?';
$string['submit_student_responses_title'] = '{$a}: Submit student responses';
$string['strftime_header'] = '%d %b %Y, %H:%M';
$string['user_identity_fields'] = ' ({$a})';
$string['create_attempt'] = 'Create Attempt';
$string['create_attempt_modal_title'] = 'Confirmation';
$string['create_attempt_modal_description'] = 'Are you sure you want to create a quiz attempt for {$a}?';
$string['create_attempt_modal_button'] = 'Create';
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
