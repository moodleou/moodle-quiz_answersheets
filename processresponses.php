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
 * This page deals with processing responses was submitted by Submit student responses feature.
 * The code was copied from mod/quiz/processattempt.php because processattempt.php prevent to submit from other user.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\report_display_options;
use quiz_answersheets\utils;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

// Remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page).
$timenow = time();

$attemptid = required_param('attempt', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);
$finishattempt = optional_param('finishattempt', false, PARAM_BOOL);
$redirect = optional_param('redirect', '', PARAM_LOCALURL);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_capability('quiz/answersheets:submitresponses', context_module::instance($attemptobj->get_cmid()));
require_sesskey();

$url = new moodle_url('/mod/quiz/report/answersheets/processresponses.php', ['cmdid' => $cmid]);
$PAGE->set_url($url);

$reportoptions = new report_display_options('answersheets', $attemptobj->get_quiz(),
        $attemptobj->get_cm(), $attemptobj->get_course());
$reportoptions->setup_from_params();

// If the attempt is already closed, send them to the review sheet page.
if ($attemptobj->is_finished()) {
    throw new moodle_exception('attemptalreadyclosed', 'quiz',
            new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    ['attempt' => $attemptid, 'userinfo' => $reportoptions->combine_user_info_visibility()]));
}

// Process the attempt, getting the new status for the attempt.
// If we are trying to create and update the student response for closed quizzes.
// Then we will have to set processing time to quiz timeclose.
// Otherwise, the attempt would not get updated, considering it to be too late in the process_attempt function.
$quiz = $attemptobj->get_quiz();
if ($timenow > $quiz->timeclose) {
    $timenow = $quiz->timeclose;
}
$attemptobj->process_attempt($timenow, $finishattempt, false, 0);

if ($redirect == '') {
    $redirect = new moodle_url('/mod/quiz/report.php',
            ['id' => $attemptobj->get_cmid(), 'mode' => 'answersheets', 'lastchanged' => $attemptid]);
}

// Fire event.
$context = context_module::instance((int) $attemptobj->get_cmid());
utils::create_events('responses_submitted', $attemptobj->get_attemptid(), $attemptobj->get_userid(), $attemptobj->get_courseid(),
        $context, $attemptobj->get_quizid());
redirect($redirect);
