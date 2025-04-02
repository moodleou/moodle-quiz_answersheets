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
 * External Quiz Answersheets API
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\utils;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use mod_quiz\quiz_settings;

/**
 * External API class.
 */
class quiz_answersheets_external extends external_api {

    /**
     * Describes the parameters for create_attempt.
     *
     * @return external_function_parameters
     */
    public static function create_attempt_parameters() {
        return new external_function_parameters([
            'quizid' => new external_value(PARAM_INT, 'Quiz Id'),
            'userid' => new external_value(PARAM_INT, 'User Id'),
        ]);
    }

    /**
     * Describes the create_attempt return value.
     *
     * @return external_single_structure
     */
    public static function create_attempt_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'success: true/false'),
            'message' => new external_value(PARAM_TEXT, 'response message'),
            'id' => new external_value(PARAM_INT, 'ID of new attempt if success.'),
        ]);
    }

    /**
     * Create Attempt API
     *
     * @param int $quizid
     * @param int $userid
     * @return array
     */
    public static function create_attempt($quizid, $userid) {
        $message = '';

        $params = self::validate_parameters(self::create_attempt_parameters(), [
            'quizid' => $quizid,
            'userid' => $userid,
        ]);

        list($course, $cm) = get_course_and_cm_from_instance($params['quizid'], 'quiz');
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $quizobj = quiz_settings::create($params['quizid'], $params['userid']);

        // Check questions.
        if (!$quizobj->has_questions()) {
            throw new moodle_exception('noquestionsfound', 'quiz');
        }

        $attempts = quiz_get_user_attempts($params['quizid'], $params['userid'], 'all');

        if (!utils::can_create_attempt($quizobj, $attempts)) {
            throw new moodle_exception('webservicecannotcreateattempts', 'quiz_answersheets');
        }

        $attemptnumber = count($attempts);
        $lastattempt = array_pop($attempts);
        // TODO: MDL-66633 When we move to Moodle 3.8, use quiz_prepare_and_start_new_attempt in mod/quiz/locallib.php.
        $attempt = static::quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber + 1, $lastattempt, false, [], [],
                $params['userid']);
        $response = ['success' => true, 'message' => $message, 'id' => $attempt->id];

        utils::create_events(utils::ATTEMPT_SHEET_CREATED, $attempt->id, $params['userid'], $course->id, $context,
                $params['quizid']);

        return $response;
    }

    /**
     * Prepare and start a new attempt deleting the previous preview attempts.
     * @todo MDL-66633 When we move to Moodle 3.8, use quiz_prepare_and_start_new_attempt in mod/quiz/locallib.php.
     *
     * @param quiz_settings $quizobj quiz object
     * @param int $attemptnumber the attempt number
     * @param object $lastattempt last attempt object
     * @param bool $isoffline whether is an offline attempt or not
     * @param array $forcedrandqs slot number => question id. Used for random questions,
     *      to force the choice of a particular actual question. Intended for testing purposes only.
     * @param array $forcedvariants slot number => variant. Used for questions with variants,
     *      to force the choice of a particular variant. Intended for testing purposes only.
     * @param int $userid Specific user id to create an attempt for that user, null for current logged in user
     * @return object the new attempt
     * @since  Moodle 3.1
     */
    public static function quiz_prepare_and_start_new_attempt(quiz_settings $quizobj, $attemptnumber, $lastattempt,
            $isoffline = false, $forcedrandqs = [], $forcedvariants = [], $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
            $ispreviewuser = $quizobj->is_preview_user();
        } else {
            $ispreviewuser = has_capability('mod/quiz:preview', $quizobj->get_context(), $userid);
        }
        // Delete any previous preview attempts belonging to this user.
        quiz_delete_previews($quizobj->get_quiz(), $userid);

        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        // Create the new attempt and initialize the question sessions.
        $timenow = time(); // Update time now, in case the server is running really slowly.
        $attempt = quiz_create_attempt($quizobj, $attemptnumber, $lastattempt, $timenow, $ispreviewuser, $userid);

        if (!($quizobj->get_quiz()->attemptonlast && $lastattempt)) {
            $attempt = quiz_start_new_attempt($quizobj, $quba, $attempt, $attemptnumber, $timenow,
                $forcedrandqs, $forcedvariants);
        } else {
            $attempt = quiz_start_attempt_built_on_last($quba, $attempt, $lastattempt);
        }

        $transaction = $DB->start_delegated_transaction();

        // Init the timemodifiedoffline for offline attempts.
        if ($isoffline) {
            $attempt->timemodifiedoffline = $attempt->timemodified;
        }
        $attempt = quiz_attempt_save_started($quizobj, $quba, $attempt);

        $transaction->allow_commit();

        return $attempt;
    }

    /**
     * Describes the parameters for create_event.
     *
     * @return external_function_parameters
     */
    public static function create_event_parameters() {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt Id'),
            'userid' => new external_value(PARAM_INT, 'User Id'),
            'courseid' => new external_value(PARAM_INT, 'Course Id'),
            'cmid' => new external_value(PARAM_INT, 'Context module Id'),
            'quizid' => new external_value(PARAM_INT, 'Quiz Id'),
            'pagetype' => new external_value(PARAM_ALPHAEXT, 'Page type'),
        ]);
    }

    /**
     * Create event API
     *
     * @param int $attemptid Attempt id
     * @param int $userid User id
     * @param int $courseid Course id
     * @param int $cmid Context module id
     * @param int $quizid Quiz id
     * @param string $pagetype Page type
     * @return bool Result
     */
    public static function create_event($attemptid, $userid, $courseid, $cmid, $quizid, $pagetype) {
        $allowpagetypes = [utils::ATTEMPT_SHEET_PRINTED, utils::RIGHT_ANSWER_SHEET_PRINTED];
        $params = self::validate_parameters(self::create_event_parameters(), [
            'attemptid' => $attemptid,
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'pagetype' => $pagetype,
        ]);
        if (!in_array($params['pagetype'], $allowpagetypes)) {
            throw new invalid_parameter_exception('Invalid pagetype event');
        }
        $context = context_module::instance($params['cmid']);
        utils::create_events($params['pagetype'], $params['attemptid'], $params['userid'], $params['courseid'], $context,
                $params['quizid']);
        return true;
    }

    /**
     * Describes the create_event return value.
     *
     * @return external_value
     */
    public static function create_event_returns() {
        return new external_value(PARAM_BOOL, 'Success');
    }

}
