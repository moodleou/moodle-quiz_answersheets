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
 * This file defines the quiz answer sheets report class.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\report_display_options;
use quiz_answersheets\report_table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');

/**
 * This file defines the quiz answer sheets report class.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_answersheets_report extends quiz_attempts_report {

    public function display($quiz, $cm, $course) {
        global $DB, $CFG;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) =
                $this->init('answersheets', '\quiz_answersheets\report_settings_form', $quiz, $cm, $course);

        $options = new report_display_options('answersheets', $quiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);
        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true, ['context' => context_course::instance($course->id)]);
        $table = new report_table($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
        $filename = quiz_report_download_filename(get_string('answersheetsfilename', 'quiz_answersheets'),
                $courseshortname, $quiz->name);
        $table->is_downloading($options->download, $filename, $courseshortname . ' ' . format_string($quiz->name, true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                           $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                           $studentsjoins->joins
                     WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }
        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security problem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = quiz_has_questions($quiz->id);

        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $quiz,
                    $options, $currentgroup, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {

            $table->setup_sql_queries($allowedjoins);

            // Define table columns.
            $columns = [];
            $headers = [];

            if (!$table->is_downloading() && $options->checkboxcolumn) {
                $columns[] = 'checkbox';
                if ($CFG->branch >= 38) {
                    // Checkbox header only available since Moodle 3.8.
                    $headers[] = $table->checkbox_col_header();
                } else {
                    $headers[] = null;
                }
            }

            $this->add_user_columns($table, $columns, $headers);
            $this->add_state_column($columns, $headers);
            $this->add_time_columns($columns, $headers);
            $this->add_attempt_sheet_column($table, $columns, $headers);
            $this->add_answer_sheet_column($table, $columns, $headers);
            $this->add_submit_responses_column($table, $columns, $headers);
            $this->add_create_attempt_column($table, $columns, $headers);

            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->sortable(true, 'uniqueid');
            $table->no_sorting('checkbox');

            // Set up the table.
            $table->define_baseurl($options->get_url());
            $this->configure_user_columns($table);
            $table->set_attribute('id', 'answersheets');
            $table->collapsible(true);
            $table->out($options->pagesize, true);
        }

        return true;
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $course the course object.
     * @param object $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
        parent::print_header_and_tabs($cm, $course, $quiz, $reportmode);
        $instruction = get_config('quiz_answersheets', 'instruction_message');
        if (trim(html_to_text($instruction)) !== '') {
            echo html_writer::div($instruction, 'instruction');
        }
    }

    /**
     * Add attempt sheet column to the $columns and $headers arrays.
     *
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_attempt_sheet_column(table_sql $table, array &$columns, array &$headers) {
        if (!$table->is_downloading() && has_capability('quiz/answersheets:view', $this->context)) {
            $columns[] = 'attempt_sheet';
            $headers[] = get_string('column_attempt_sheet', 'quiz_answersheets');
        }
    }

    /**
     * Add answer sheet column to the $columns and $headers arrays.
     *
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_answer_sheet_column(table_sql $table, array &$columns, array &$headers) {
        if (!$table->is_downloading() && has_capability('quiz/answersheets:viewrightanswers', $this->context)) {
            $columns[] = 'answer_sheet';
            $headers[] = get_string('column_answer_sheet', 'quiz_answersheets');
        }
    }

    /**
     * Add submit student responses column to the $columns and $headers arrays.
     *
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    protected function add_submit_responses_column(table_sql $table, array &$columns, array &$headers) {
        if (!$table->is_downloading() && has_capability('quiz/answersheets:submitresponses', $this->context)) {
            $columns[] = 'submit_student_responses';
            $headers[] = get_string('column_submit_student_responses', 'quiz_answersheets');
        }
    }

    /**
     * Add create attempt column to the $column and $headers arrays
     *
     * @param table_sql $table the table being constructed.
     * @param array $columns the list of columns. Added to.
     * @param array $headers the columns headings. Added to.
     */
    public function add_create_attempt_column(table_sql $table, &$columns, &$headers) {
        if (!$table->is_downloading() && has_capability('quiz/answersheets:createattempt', $this->context)) {
            global $PAGE;
            $PAGE->requires->js_call_amd('quiz_answersheets/create_attempt_dialog', 'init');
            $PAGE->requires->strings_for_js([
                    'create_attempt_modal_title',
                    'create_attempt_modal_button',
                    'create_attempt_modal_description'
            ], 'quiz_answersheets');
            $columns[] = 'create_attempt';
            $headers[] = get_string('create_attempt', 'quiz_answersheets');
        }
    }

}
