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
 * This page prints submit student responses page of a particular quiz attempt.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\report_display_options;
use quiz_answersheets\utils;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);
$redirect = optional_param('redirect', '', PARAM_LOCALURL);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$reportoptions = new report_display_options('answersheets', $attemptobj->get_quiz(),
    $attemptobj->get_cm(), $attemptobj->get_course());
$reportoptions->setup_from_params();

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_capability('quiz/answersheets:submitresponses', context_module::instance($attemptobj->get_cmid()));

$isattemptfinished = $attemptobj->get_attempt()->state == quiz_attempt::FINISHED;

// If the attempt is already closed, send them to the review sheet page.
if ($attemptobj->is_finished()) {
    redirect(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php', ['attempt' => $attemptid]));
}

// Check the access rules.
$accessmanager = $attemptobj->get_access_manager(time());
$accessmanager->setup_attempt_page($PAGE);
$messages = $accessmanager->prevent_access();

$url = new moodle_url('/mod/quiz/report/answersheets/submitresponses.php', ['attempt' => $attemptid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');

// Get the list of questions needed.
$slots = $attemptobj->get_slots();

// Check.
if (empty($slots)) {
    throw new moodle_quiz_exception($attemptobj->get_quizobj(), 'noquestionsfound');
}

// Initialise the JavaScript.
$headtags = $attemptobj->get_html_head_contributions('all', true);
$PAGE->requires->js_init_call('M.mod_quiz.init_attempt_form', null, false, quiz_get_js_module());
$PAGE->set_title(get_string('submit_student_responses_title', 'quiz_answersheets', $attemptobj->get_quiz_name()));

echo $OUTPUT->header();

$quizrenderer = $PAGE->get_renderer('mod_quiz');
$renderer = $PAGE->get_renderer('quiz_answersheets');

// Add summary table.
$sumdata = utils::prepare_summary_attempt_information($attemptobj, !$isattemptfinished, $reportoptions);
echo $quizrenderer->review_summary_table($sumdata, 0);

echo $renderer->render_question_attempt_form($attemptobj, $reportoptions, $redirect);

echo $OUTPUT->footer();
