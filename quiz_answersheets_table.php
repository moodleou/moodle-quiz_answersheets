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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_table.php');

/**
 * This file defines the quiz answersheets table for showing last try at question
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_answersheets_table extends quiz_attempts_report_table {

    public function __construct($quiz, $context, $qmsubselect, quiz_answersheets_options $options,
            \core\dml\sql_join $groupstudentsjoins, \core\dml\sql_join $studentsjoins, $questions, $reporturl) {
        parent::__construct('mod-quiz-report-answersheets-report', $quiz, $context,
                $qmsubselect, $options, $groupstudentsjoins, $studentsjoins, $questions, $reporturl);
    }

    public function build_table() {
        if (!$this->rawdata) {
            return;
        }
        $this->strtimeformat = str_replace(',', ' ', get_string('strftimedatetime'));
        return parent::build_table();
    }

    /**
     * Generate the display of the user's full name column.
     *
     * @param object $row the table row being output.
     * @return string HTML content to go inside the td.
     * @throws moodle_exception
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
     * Generate the display of the attempt sheet column.
     *
     * @param object $row The raw data for this row.
     * @return string The value for this cell of the table.
     */
    public function col_attempt_sheet($row) {
        if ($row->state == quiz_attempt::IN_PROGRESS) {
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    ['attempt' => $row->attempt]), get_string('attempt_sheet_label', 'quiz_answersheets'),
                    ['class' => 'reviewlink']);
        } else if ($row->state == quiz_attempt::FINISHED) {
            return html_writer::link(new moodle_url('/mod/quiz/report/answersheets/attemptsheet.php',
                    ['attempt' => $row->attempt]), get_string('review_sheet_label', 'quiz_answersheets'),
                    ['class' => 'reviewlink']);
        } else {
            return '-';
        }
    }

}
