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

use quiz_answersheets\utils;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$attemptid = required_param('attempt', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);
$rightanswer = optional_param('rightanswer', 0, PARAM_BOOL);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_capability('quiz/answersheets:view', context_module::instance($attemptobj->get_cmid()));

if ($rightanswer) {
    require_capability('quiz/answersheets:viewrightanswers', context_module::instance($attemptobj->get_cmid()));
}

$isattemptfinished = $attemptobj->get_attempt()->state == quiz_attempt::FINISHED;
$pagetitle = $isattemptfinished ? get_string('review_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name()) :
        get_string('attempt_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name());

$url = new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php', ['attempt' => $attemptid, 'rightanswer' => $rightanswer]);

if ($rightanswer) {
    $pagetitle = get_string('answer_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name());
} else if ($isattemptfinished) {
    $pagetitle = get_string('review_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name());
} else {
    $pagetitle = get_string('attempt_sheet_title', 'quiz_answersheets', $attemptobj->get_quiz_name());
}

$isrightanswer = $rightanswer && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS;

// Fire event.
$context = context_module::instance((int) $attemptobj->get_cmid());
$event = $rightanswer && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS ? utils::RIGHT_ANSWER_SHEET_VIEWED :
        utils::ATTEMPT_SHEET_VIEWED;
utils::create_events($event, $attemptobj->get_attemptid(), $attemptobj->get_userid(), $attemptobj->get_courseid(), $context,
        $attemptobj->get_quizid());

$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title($pagetitle);

echo $OUTPUT->header();

$quizrenderer = $PAGE->get_renderer('mod_quiz');
$renderer = $PAGE->get_renderer('quiz_answersheets');

// Summary table.
$sumdata = utils::prepare_summary_attempt_information($attemptobj, $url, !$isattemptfinished);

// Page content.
echo $renderer->render_attempt_sheet($sumdata, $attemptobj);
// Print button.
echo $renderer->render_print_button($attemptobj, $isrightanswer);

echo $OUTPUT->footer();
