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
use context_module;
use html_writer;
use moodle_page;
use moodle_url;
use qtype_renderer;
use question_attempt;
use question_display_options;
use quiz_attempt;
use ReflectionClass;
use stdClass;
use cm_info;
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

    const ATTEMPT_SHEET_CREATED = 'attempt_created';
    const ATTEMPT_SHEET_PRINTED = 'attempt_printed';
    const ATTEMPT_SHEET_VIEWED = 'attempt_viewed';
    const RIGHT_ANSWER_SHEET_PRINTED = 'right_answer_printed';
    const RIGHT_ANSWER_SHEET_VIEWED = 'right_answer_viewed';
    const RESPONSES_SUBMITTED = 'responses_submitted';
    const EXAMPLE_TUTOR = 'example tutor';
    const EXAMPLE_STUDENT = 'example student';
    /** @var string[] Supported question types for new combine feedback. */
    const COMBINED_FEEDBACK_QTYPES = [
            'oumultiresponse',
            'match',
            'multichoice',
            'gapselect',
            'truefalse',
            'wordselect',
            'combined'
    ];

    /**
     * Calculate summary information of a particular quiz attempt.
     * The code was copied from mod/quiz/review.php, with modifications.
     *
     * @param quiz_attempt $attemptobj Attempt object
     * @param bool $minimal True to only show the student fullname
     * @param report_display_options $reportoptions controls which user info is shown.
     * @return array List of summary information
     */
    public static function prepare_summary_attempt_information(quiz_attempt $attemptobj,
            bool $minimal, report_display_options $reportoptions): array {

        global $CFG, $DB;

        $sumdata = [];
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $options = $attemptobj->get_display_options(true);
        $context = context_module::instance($attemptobj->get_cm()->id);
        // Get student data with custom fields.
        $userfieldsapi = \core_user\fields::for_identity($context);
        [
            'selects' => $userfieldsselects,
            'joins' => $userfieldsjoin,
            'params' => $userfieldsparams
        ] = (array) $userfieldsapi->get_sql('u', false, '', '', false);

        $param = array_merge($userfieldsparams, [$attemptobj->get_userid()]);
        $sql = "SELECT u.*, $userfieldsselects
                  FROM {user} u $userfieldsjoin
                 WHERE u.id = ?";
        $student = $DB->get_record_sql($sql, $param);

        if ($reportoptions->userinfovisibility['fullname']) {
            if (!self::is_example_user($student)) {
                $userpicture = new user_picture($student);
                $userpicture->courseid = $attemptobj->get_courseid();
                $sumdata['user'] = [
                        'title' => $userpicture,
                        'content' => new action_link(new moodle_url('/user/view.php',
                                ['id' => $student->id, 'course' => $attemptobj->get_courseid()]), fullname($student, true))
                ];
            }
        }

        foreach ($reportoptions->userinfovisibility as $field => $show) {
            if ($field === 'fullname') {
                continue;
            }
            if (!$show) {
                continue;
            }

            if ($field === 'examcode') {
                $value = \quiz_gradingstudents_ou_confirmation_code::get_confirmation_code(
                        $reportoptions->cm, $student);

            } else {
                $value = $student->$field;
            }

            $sumdata['user' . $field] = [
                    'title' => report_display_options::user_info_visibility_settings_name($field),
                    'content' => $value,
            ];
        }

        if ($minimal) {
            return $sumdata;
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
            if ($attempt->state != quiz_attempt::FINISHED) { // @codingStandardsIgnoreLine
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
     * @param stdClass|cm_info $cm quiz course_module.
     * @param report_display_options|array $fieldoptions which use fields to show, either an options object,
     *      or just a array of field names.
     * @return string User detail string
     */
    public static function get_user_details(stdClass $attemptuser, stdClass|cm_info $cm, $fieldoptions): string {
        $fields = [];
        if ($fieldoptions instanceof report_display_options) {
            foreach ($fieldoptions->userinfovisibility as $field => $show) {
                if ($show) {
                    $fields[] = $field;
                }
            }
        } else {
            $fields = $fieldoptions;
        }

        $userinfo = '';
        if (in_array('fullname', $fields)) {
            $userinfo .= fullname($attemptuser);
        }

        $data = [];
        foreach ($fields as $field) {
            if ($field === 'fullname') {
                continue;
            }

            if ($field === 'examcode') {
                $data[] = \quiz_gradingstudents_ou_confirmation_code::get_confirmation_code(
                        $cm, $attemptuser);

            } else if (!empty($attemptuser->$field)) {
                $data[] = $attemptuser->$field;
            }
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
        // Check if quiz is unlimited.
        if (!$quizobj->get_quiz()->attempts) {
            return true;
        }
        $numprevattempts = count($attempts);
        if ($numprevattempts == 0) {
            return true;
        }
        $lastattempt = end($attempts);
        $state = $lastattempt->state;
        if ($state && $state == quiz_attempt::FINISHED) {
            // Check max attempts.
            $rule = new \quizaccess_numattempts($quizobj, time());
            if (!$rule->prevent_new_attempt($numprevattempts, $lastattempt)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get instruction text for given question type
     *
     * @param string $questiontype Question type
     * @return string instruction text
     */
    public static function get_question_instruction(string $questiontype): string {
        $instructionexists = get_string_manager()->string_exists($questiontype . '_instruction', 'quiz_answersheets');
        if (!$instructionexists) {
            return '';
        } else {
            return get_string($questiontype . '_instruction', 'quiz_answersheets');
        }
    }

    /**
     * Prepare event data.
     *
     * @param int $attemptid Attempt id
     * @param int $userid User id
     * @param int $courseid Course id
     * @param context_module $context Module context
     * @param int $quizid Quiz id
     * @return array Event data
     */
    private static function prepare_event_data(int $attemptid, int $userid, int $courseid, context_module $context,
            int $quizid): array {
        $params = [
                'relateduserid' => $userid,
                'courseid' => $courseid,
                'context' => $context,
                'other' => [
                        'quizid' => $quizid,
                        'attemptid' => $attemptid
                ]
        ];

        return $params;
    }

    /**
     * Fire events.
     *
     * @param string $eventtype Event type name
     * @param int $attemptid Attempt id
     * @param int $userid User id
     * @param int $courseid Course id
     * @param context_module $context Module context
     * @param int $quizid Quiz id
     */
    public static function create_events(string $eventtype, int $attemptid, int $userid, int $courseid, context_module $context,
            int $quizid): void {
        $params = self::prepare_event_data($attemptid, $userid, $courseid, $context, $quizid);
        $classname = '\quiz_answersheets\event\\' . $eventtype;
        $event = $classname::create($params);
        $event->trigger();
    }

    /**
     * Get the protected property of given class.
     *
     * @param $originalclass Class that contain the protected property
     * @param string $propertyname Protected property that need to get value
     * @return mixed Protected value
     */
    public static function get_reflection_property($originalclass, string $propertyname) {
        $reflectionclass = new ReflectionClass($originalclass);
        $reflectionproperty = $reflectionclass->getProperty($propertyname);
        $reflectionproperty->setAccessible(true);
        $returnvalue = $reflectionproperty->getValue($originalclass);
        $reflectionproperty->setAccessible(false);

        return $returnvalue;
    }

    /**
     * Get the attempt sheet header string for printing
     *
     * @param quiz_attempt $attemptobj
     * @param string $sheettype Sheet type
     * @return string Header string
     */
    public static function get_attempt_sheet_print_header(quiz_attempt $attemptobj, string $sheettype,
            report_display_options $reportoptions): string {
        $generatedtime = time();
        $attemptuser = \core_user::get_user($attemptobj->get_userid());
        $context = context_module::instance((int) $attemptobj->get_cmid());

        $headerinfo = new \stdClass();
        $headerinfo->courseshortname = $attemptobj->get_course()->shortname;
        $headerinfo->quizname = $attemptobj->get_quiz_name();
        $headerinfo->studentname = self::get_user_details($attemptuser, $reportoptions->cm, $reportoptions);
        // We use custom time format because get_string('strftime...', 'langconfig'); do not have format we need.
        $headerinfo->generatedtime = userdate($generatedtime, get_string('strftime_header', 'quiz_answersheets'));
        $headerinfo->sheettype = $sheettype;

        if (self::is_example_user($attemptuser)) {
            return get_string('print_header_minimised', 'quiz_answersheets', $headerinfo);
        }

        return get_string('print_header', 'quiz_answersheets', $headerinfo);
    }

    /**
     * Set current page navigation
     *
     * @param string $pagetitle Page title
     */
    public static function set_page_navigation(string $pagetitle): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        $reportlist = quiz_report_list($PAGE->cm->context);

        $PAGE->navbar->add(get_string('results', 'quiz'),
                new moodle_url('/mod/quiz/report.php', ['id' => $PAGE->cm->id, 'mode' => reset($reportlist)]));
        $PAGE->navbar->add(get_string('answersheets', 'quiz_answersheets'),
                new moodle_url('/mod/quiz/report.php', ['id' => $PAGE->cm->id, 'mode' => 'answersheets']));

        $PAGE->navbar->add($pagetitle);
    }

    /**
     * Check if given user is an example user.
     *
     * @param stdClass $user User
     * @return bool
     */
    public static function is_example_user(stdClass $user): bool {
        return class_exists('block_viewasexample\behaviour') &&
                \block_viewasexample\behaviour::is_viewas_user($user);
    }

    /**
     * Get the question renderer.
     *
     * @param moodle_page $page the page we are outputting to.
     * @param question_attempt $qa the question attempt object
     * @return qtype_renderer the renderer to use for outputting this question.
     */
    public static function get_question_renderer(moodle_page $page, question_attempt $qa) {
        global $CFG;

        $qtypename = $qa->get_question()->get_type_name();
        $requirefile = $CFG->dirroot . '/mod/quiz/report/answersheets/classes/output/' . $qtypename . '/renderer.php';
        if (file_exists($requirefile)) {
            // Override supported question found.
            require_once($requirefile);
            $classpath = sprintf('quiz_answersheets\output\\' . $qtypename . '\%s', 'qtype_' . $qtypename . '_override_renderer');
            return new $classpath($page, null);
        }

        return $qa->get_question()->get_renderer($page);
    }

    /**
     * Check if given question type is supported for the new combine feedback format.
     *
     * @param string $questiontype Question type
     * @return bool
     */
    public static function should_show_combined_feedback(string $questiontype): bool {
        return in_array($questiontype, self::COMBINED_FEEDBACK_QTYPES);
    }

    /**
     * Check if we should hide the inline choice or not
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function should_hide_inline_choice(moodle_page $page): bool {
        $rightanswer = $page->url->get_param('rightanswer');
        $attemptid = $page->url->get_param('attempt');

        if (empty($attemptid)) {
            return $page->pagetype != 'mod-quiz-report-answersheets-attemptsheet' || $rightanswer;
        }

        $attemptobj = quiz_attempt::create($attemptid);
        $attempt = $attemptobj->get_attempt();

        return $page->pagetype != 'mod-quiz-report-answersheets-attemptsheet' || $rightanswer ||
                $attempt->state == quiz_attempt::FINISHED;
    }

}
