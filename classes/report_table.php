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
 * This file defines the quiz answersheets table for showing last try at question
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets;

use html_writer;
use moodle_url;
use quiz_attempt;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_table.php');

/**
 * This file defines the quiz answersheets table for showing last try at question
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends \quiz_attempts_report_table {

    /** @var report_display_options Option */
    protected $options;

    /** @var array User details */
    protected $userdetails = [];

    /** @var string Dash value for table cell */
    const DASH_VALUE = '-';

    public function __construct($quiz, $context, $qmsubselect, report_display_options $options,
            \core\dml\sql_join $groupstudentsjoins, \core\dml\sql_join $studentsjoins, $questions, $reporturl) {
        parent::__construct('mod-quiz-report-answersheets-report', $quiz, $context,
                $qmsubselect, $options, $groupstudentsjoins, $studentsjoins, $questions, $reporturl);
        $this->options = $options;
    }

    public function build_table() {
        if (!$this->rawdata) {
            return;
        }
        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetime'));
        parent::build_table();
    }

    /**
     * Generate the display of the user's full name column.
     *
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($row) {
        global $COURSE;

        $name = fullname($row);
        if ($this->download) {
            return $name;
        }

        $userid = $row->{$this->useridfield};
        if ($COURSE->id == SITEID) {
            $profileurl = new moodle_url('/user/profile.php', ['id' => $userid]);
        } else {
            $profileurl = new moodle_url('/user/view.php', ['id' => $userid, 'course' => $COURSE->id]);
        }

        return html_writer::link($profileurl, $name);
    }

    /**
     * Display the exam code (OU-specific).
     *
     * @param \stdClass $row the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_examcode(\stdClass $row) {
        $fakeuser = clone($row);
        $fakeuser->id = $row->userid;
        return \quiz_gradingstudents_ou_confirmation_code::get_confirmation_code(
                $this->options->cm, $fakeuser);
    }

    /**
     * Generate the display of the attempt sheet column.
     *
     * @param object $row The raw data for this row.
     * @return string The value for this cell of the table.
     */
    public function col_attempt_sheet($row) {
        if ($row->state == quiz_attempt::IN_PROGRESS) {
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    [
                        'attempt' => $row->attempt,
                        'userinfo' => $this->options->combine_user_info_visibility(),
                        'instruction' => $this->options->questioninstruction,
                        'marks' => $this->options->marks,
                    ]),
                    get_string('attempt_sheet_label', 'quiz_answersheets'),
                    ['class' => 'reviewlink']);
        } else if ($row->state == quiz_attempt::FINISHED) {
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    [
                        'attempt' => $row->attempt,
                        'userinfo' => $this->options->combine_user_info_visibility(),
                        'instruction' => $this->options->questioninstruction,
                        'marks' => $this->options->marks,
                    ]),
                    get_string('review_sheet_label', 'quiz_answersheets'),
                    ['class' => 'reviewlink']);
        } else {
            return self::DASH_VALUE;
        }
    }

    /**
     * Generate the display of the answer sheet column.
     *
     * @param object $row The raw data for this row.
     * @return string The value for this cell of the table.
     */
    public function col_answer_sheet($row) {
        if ($row->state == quiz_attempt::IN_PROGRESS) {
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    [
                        'attempt' => $row->attempt,
                        'rightanswer' => 1,
                        'userinfo' => $this->options->combine_user_info_visibility(),
                        'instruction' => $this->options->questioninstruction,
                        'marks' => $this->options->marks,
                    ]),
                    get_string('answer_sheet_label', 'quiz_answersheets'),
                    ['class' => 'reviewlink']);
        }

        return self::DASH_VALUE;
    }

    /**
     * Generate the display of the submit student responses column.
     *
     * @param object $row The raw data for this row.
     * @return string The value for this cell of the table.
     */
    public function col_submit_student_responses($row) {
        if ($row->state == quiz_attempt::IN_PROGRESS || $row->state == quiz_attempt::OVERDUE) {
            $redirect = $this->options->get_url();
            $redirect->param('lastchanged', $row->attempt);
            // Add userinfo params so that we only display fields that is used in the filter.
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/submitresponses.php',
                ['attempt' => $row->attempt, 'redirect' => $redirect, 'marks' => $this->options->marks,
                    'userinfo' => $this->options->combine_user_info_visibility()]),
                    get_string('submit_student_responses_label', 'quiz_answersheets'), ['class' => 'reviewlink']);
        } else {
            return self::DASH_VALUE;
        }
    }

    /**
     * Generate the display of the create attempt column.
     *
     * @param object $row The raw data for this row.
     * @return string The value for this cell of the table.
     */
    public function col_create_attempt($row): string {
        if ($row->used_all_attempts) {
            return self::DASH_VALUE;
        }
        if (!$row->last_attempt_for_this_user) {
            return self::DASH_VALUE;
        }
        if ($row->state == quiz_attempt::IN_PROGRESS || $row->state == quiz_attempt::OVERDUE) {
            return self::DASH_VALUE;
        }
        if (!isset($this->userdetails[$row->userid])) {
            $fakeuser = clone($row);
            $fakeuser->id = $row->userid;
            $userdetails = utils::get_user_details($fakeuser, $this->options->cm, $this->options);
            $this->userdetails[$row->userid] = get_string('create_attempt_modal_description', 'quiz_answersheets', $userdetails);
        }
        $buttontext = get_string('create_attempt', 'quiz_answersheets');
        $attributes = [
                'class' => 'btn btn-secondary mr-1 create-attempt-btn',
                'name' => 'create_attempt',
                'data-message' => $this->userdetails[$row->userid],
                'data-user-id' => $row->userid,
                'data-quiz-id' => $this->quiz->id,
                'data-url' => $this->options->get_url()->out(false)
        ];
        return html_writer::tag('button', $buttontext, $attributes);
    }

    /**
     * Add highlight class to last changed row
     *
     * @param \stdClass $attempt
     * @return string
     */
    public function get_row_class($attempt): string {
        $options = $this->options;
        $class = parent::get_row_class($attempt);
        if (!is_null($options->lastchanged)) {
            if ($options->lastchanged > 0 && $options->lastchanged == $attempt->attempt) {
                $class .= ' lastchanged';
            }
        }
        return $class;
    }

    /**
     * A chance for subclasses to modify the SQL after the count query has been generated,
     * and before the full query is constructed.
     *
     * @param string $fields SELECT list.
     * @param string $from JOINs part of the SQL.
     * @param string $where WHERE clauses.
     * @param array $params Query params.
     * @return array with 4 elements ($fields, $from, $where, $params) as from base_sql.
     */
    protected function update_sql_after_count($fields, $from, $where, $params) {
        [$fields, $from, $where, $params] = parent::update_sql_after_count($fields, $from, $where, $params);
        $fields .= ", quiza.attempt AS attemptno
                    , CASE
                        -- If, for this user, attempts allowed (including overrids) is unlimited,
                        -- then they have not used all attempts.
                        WHEN COALESCE(
                                (SELECT attempts FROM {quiz_overrides} WHERE quiz = quiza.quiz AND userid = u.id),
                                (SELECT MIN(overrides1.attempts)
                                   FROM {quiz_overrides} overrides1
                                   JOIN {groups_members} overrides1_gm ON overrides1_gm.groupid = overrides1.groupid
                                  WHERE overrides1.quiz = quiza.quiz
                                        AND overrides1_gm.userid = u.id),
                                :quizmaxattempts1) = 0 THEN 0
                        -- Or, if there is a finite limit, compare with the number of attempts they have.
                        WHEN (SELECT COUNT(1) FROM {quiz_attempts} WHERE quiz = quiza.quiz AND userid = quiza.userid) < COALESCE(
                                (SELECT attempts FROM {quiz_overrides} WHERE quiz = quiza.quiz AND userid = u.id),
                                (SELECT MAX(overrides2.attempts)
                                   FROM {quiz_overrides} overrides2
                                   JOIN {groups_members} overrides2_gm ON overrides2_gm.groupid = overrides2.groupid
                                  WHERE overrides2.quiz = quiza.quiz
                                        AND overrides2_gm.userid = u.id),
                                :quizmaxattempts2) THEN 0
                        ELSE 1
                    END AS used_all_attempts
                    , CASE
                        -- User does not have an attempt yet, so only one row.
                        WHEN quiza.id IS NULL THEN 1
                        -- User woth one or more attempts.
                        WHEN quiza.attempt = (
                                SELECT MAX(attempt)
                                  FROM {quiz_attempts}
                                 WHERE quiz = quiza.quiz AND userid = quiza.userid
                        ) THEN 1
                        ELSE 0
                    END AS last_attempt_for_this_user
                    ";
        $params['quizmaxattempts1'] = $this->quiz->attempts;
        $params['quizmaxattempts2'] = $this->quiz->attempts;
        return [$fields, $from, $where, $params];
    }

}
