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
 * This page prints a answer sheet of a particular quiz attempt.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/answersheets/locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);

$url = new moodle_url('/mod/quiz/report/answersheets/attempt_sheet.php', ['attempt' => $attemptid]);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->check_review_capability();
require_capability('quiz/answersheets:view', context_module::instance($attemptobj->get_cmid()));

$isattemptfinished = $attemptobj->get_attempt()->state == quiz_attempt::FINISHED;
$pagetitle = $isattemptfinished ? get_string('review_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name()) :
        get_string('attempt_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name());

$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title($pagetitle);

$output = '';

// Add summary table for Finished attempt.
if ($isattemptfinished) {
    $quizrenderer = $PAGE->get_renderer('mod_quiz');
    $sumdata = prepare_summary_attempt_information($attemptobj, $url);
    $output .= $quizrenderer->review_summary_table($sumdata, 0);
}

$renderer = $PAGE->get_renderer('quiz_answersheets');
$output .= $renderer->render_question_attempt_content($attemptobj);
$output .= $renderer->render_print_button();

echo $OUTPUT->header();

echo $output;

echo $OUTPUT->footer();
