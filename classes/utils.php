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
 * Quiz answer sheet utils.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets;

use action_link;
use context;
use html_writer;
use moodle_url;
use question_display_options;
use quiz_attempt;
use stdClass;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz answer sheet utils.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Calculate summary information of a particular quiz attempt.
     * The code was copied from mod/quiz/review.php.
     *
     * @param quiz_attempt $attemptobj Attempt object
     * @param moodle_url $baseurl Base url
     * @param boolean $minimal True to only show the student fullname
     * @return array List of summary information
     */
    public static function prepare_summary_attempt_information(quiz_attempt $attemptobj, moodle_url $baseurl,
            $minimal = true): array {
        global $DB, $USER;

        $sumdata = [];
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $options = $attemptobj->get_display_options(true);

        if (!$attemptobj->get_quiz()->showuserpicture && $attemptobj->get_userid() != $USER->id) {
            $student = $DB->get_record('user', ['id' => $attemptobj->get_userid()]);
            $userpicture = new user_picture($student);
            $userpicture->courseid = $attemptobj->get_courseid();
            $sumdata['user'] = [
                    'title' => $userpicture,
                    'content' => new action_link(new moodle_url('/user/view.php',
                            ['id' => $student->id, 'course' => $attemptobj->get_courseid()]), fullname($student, true))
            ];
        }

        if ($minimal) {
            return $sumdata;
        }

        if ($attemptobj->has_capability('mod/quiz:viewreports')) {
            $attemptlist = $attemptobj->links_to_other_attempts($baseurl);
            if ($attemptlist) {
                $sumdata['attemptlist'] = [
                        'title' => get_string('attempts', 'quiz'),
                        'content' => $attemptlist
                ];
            }
        }

        $sumdata['startedon'] = [
                'title' => get_string('startedon', 'quiz'),
                'content' => userdate($attempt->timestart),
        ];

        $sumdata['state'] = [
                'title' => get_string('attemptstate', 'quiz'),
                'content' => quiz_attempt::state_name($attempt->state),
        ];

        $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);

        if ($options->marks >= question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {
            if ($attempt->state != quiz_attempt::FINISHED) {
                // Cannot display grade.
            } else if (is_null($grade)) {
                $sumdata['grade'] = [
                        'title' => get_string('grade', 'quiz'),
                        'content' => quiz_format_grade($quiz, $grade),
                ];
            } else {
                // Show raw marks only if they are different from the grade (like on the view page).
                if ($quiz->grade != $quiz->sumgrades) {
                    $a = new stdClass();
                    $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
                    $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
                    $sumdata['marks'] = [
                            'title' => get_string('marks', 'quiz'),
                            'content' => get_string('outofshort', 'quiz', $a),
                    ];
                }

                // Now the scaled grade.
                $a = new stdClass();
                $a->grade = html_writer::tag('b', quiz_format_grade($quiz, $grade));
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                if ($quiz->grade != 100) {
                    $a->percent = html_writer::tag('b', format_float(
                            $attempt->sumgrades * 100 / $quiz->sumgrades, 0));
                    $formattedgrade = get_string('outofpercent', 'quiz', $a);
                } else {
                    $formattedgrade = get_string('outof', 'quiz', $a);
                }
                $sumdata['grade'] = [
                        'title' => get_string('grade', 'quiz'),
                        'content' => $formattedgrade,
                ];
            }
        }

        // Any additional summary data from the behaviour.
        $sumdata = array_merge($sumdata, $attemptobj->get_additional_summary_data($options));

        // Feedback if there is any, and the user is allowed to see it now.
        $feedback = $attemptobj->get_overall_feedback($grade);
        if ($options->overallfeedback && $feedback) {
            $sumdata['feedback'] = [
                    'title' => get_string('feedback', 'quiz'),
                    'content' => $feedback,
            ];
        }

        return $sumdata;
    }

    /**
     * Get user detail with identity fields
     *
     * @param stdClass $attemptuser User info
     * @param context $context Context module
     * @return string User detail string
     */
    public static function get_user_details(stdClass $attemptuser, context $context): string {
        $userinfo = '';

        $userinfo .= fullname($attemptuser);

        $extra = get_extra_user_fields($context);
        $data = [];
        foreach ($extra as $field) {
            $value = $attemptuser->{$field};
            if (!$value) {
                continue;
            }
            $data[] = $value;
        }

        if (count($data) > 0) {
            $userinfo .= get_string('user_identity_fields', 'quiz_answersheets', implode(', ', $data));
        }

        return $userinfo;
    }

    /**
     * Check if can create attempt
     *
     * @param \quiz $quizobj Quiz object
     * @param array $attempts Array of attempts
     * @return bool
     */
    public static function can_create_attempt($quizobj, $attempts): bool {
        $numprevattempts = count($attempts);
        if ($numprevattempts == 0) {
            return true;
        }
        $lastattempt = end($attempts);
        $state = $lastattempt->state;
        if ($state && $state == quiz_attempt::FINISHED) {
            // Check max attempts
            $rule = new \quizaccess_numattempts($quizobj, time());
            if (!$rule->prevent_new_attempt($numprevattempts, $lastattempt)) {
                return true;
            }
        }
        return false;
    }
}
