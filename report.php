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
 * This file defines the export quiz attempts class.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_answersheets\report_display_options;
use quiz_answersheets\report_table;
use quiz_answersheets\utils;

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');

/**
 * This file defines the export quiz attempts report class.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_answersheets_report extends quiz_attempts_report {

    public function display($quiz, $cm, $course) {
        global $DB, $PAGE;

        $bulkinstructions = optional_param('bulk', false, PARAM_BOOL);
        $bulkscript = optional_param('bulkscript', false, PARAM_BOOL);

        // Hack so we can get this in the form initialisation code.
        $quiz->cmobject = $cm;
        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) =
                $this->init('answersheets', '\quiz_answersheets\report_settings_form', $quiz, $cm, $course);

        if ($bulkinstructions || $bulkscript) {
            require_capability('quiz/answersheets:bulkdownload', $this->context);
        }

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
        if ($bulkscript) {
            $table->download = 'html';
        }
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
        }

        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {

            $table->setup_sql_queries($allowedjoins);

            // Define table columns.
            $columns = [];
            $headers = [];

            if (!$table->is_downloading() && $options->checkboxcolumn) {
                $columns[] = 'checkbox';
                if (method_exists($table, 'checkbox_col_header')) {
                    // Checkbox header only available since Moodle 3.8.
                    $headers[] = $table->checkbox_col_header('checkbox');
                } else {
                    $headers[] = null;
                }
            }

            $this->add_user_columns_from_options($table, $columns, $headers, $options);
            $this->add_state_column($columns, $headers);
            $this->add_time_columns($columns, $headers);
            $this->add_attempt_sheet_column($table, $columns, $headers);
            $this->add_answer_sheet_column($table, $columns, $headers);
            $this->add_submit_responses_column($table, $columns, $headers);
            $this->add_create_attempt_column($table, $columns, $headers);

            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->sortable(true, 'uniqueid');
            // Do not allow sorting on virtual columns.
            $table->no_sorting('checkbox');
            $table->no_sorting('attempt_sheet');
            $table->no_sorting('answer_sheet');
            $table->no_sorting('submit_student_responses');
            $table->no_sorting('create_attempt');

            // Set up the table.
            $table->define_baseurl($options->get_url());
            $this->configure_user_columns($table);
            $table->set_attribute('id', 'answersheets');
            $table->collapsible(true);

            $renderer = $PAGE->get_renderer('quiz_answersheets');
            if ($bulkinstructions) {
                echo $renderer->bulk_download_instructions($options,
                        $this->generate_zip_filename($quiz, $cm), $this->context);

            } else if ($bulkscript) {
                $this->generate_bulk_download_script($table,
                        $this->generate_zip_filename($quiz, $cm),
                        $options);

            } else {
                if (!$table->is_downloading()) {
                    $this->form->display();
                }
                $table->out($options->pagesize, true);

                if (!$table->is_downloading() && has_capability('quiz/answersheets:bulkdownload', $this->context)) {
                    echo $renderer->bulk_download_link($options);
                }
            }
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
     * Add user columns, taking note of our option for which ones to show.
     *
     * @param report_table $table the table we are building.
     * @param array $columns the columns array to update.
     * @param array $headers the column headers array to update.
     * @param report_display_options $options report display options.
     */
    protected function add_user_columns_from_options(report_table $table,
            array &$columns, array &$headers, report_display_options $options): void {
        global $CFG;

        if (!$table->is_downloading() && $CFG->grade_report_showuserimage) {
            $columns[] = 'picture';
            $headers[] = '';
        }

        foreach ($options->userinfovisibility as $field => $show) {
            if ($field === 'fullname') {
                if (!$table->is_downloading()) {
                    $columns[] = 'fullname';
                    $headers[] = get_string('name');
                } else {
                    $columns[] = 'lastname';
                    $headers[] = get_string('lastname');
                    $columns[] = 'firstname';
                    $headers[] = get_string('firstname');
                }
            } else if ($field === 'examcode') {
                $table->no_sorting('examcode');
            } else {
                $columns[] = $field;
                $headers[] = report_display_options::user_info_visibility_settings_name($field);
            }
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

    /**
     * Generate a script with the steps to download all the review sheets to a zip file.
     *
     * This script is processed by a separate command-line utility
     * called save-answersheets.
     *
     * @param report_table $table used to get the list of attempts.
     * @param string filename for the zip to generate. Also used in the suggested name for the script.
     */
    protected function generate_bulk_download_script(report_table $table, string $zipfilename,
            report_display_options $options): void {
        global $CFG;

        $qtyperesponsefiles = $this->get_qtype_response_files();

        $table->setup();
        $table->query_db(10); // Page size does not matter since we are downloading.

        // Start writing the script to a downloadable .txt file.
        header('Content-Disposition: attachment; filename="' . $zipfilename . '-steps.txt"');
        header('Content-Type: text/plain; charset=UTF-8');
        echo "# This set of steps is designed to be processed by the save-answersheets tool.\n";
        echo "# Save in the same folder as save-answersheets then run the command-line\n";
        echo "#\n";
        echo "#     .\save-answersheets '$zipfilename-steps.txt'\n";
        echo "#\n";
        echo "# Remember to delete this file after use!\n\n";

        echo 'zip-name ' . $zipfilename . "\n";
        echo 'cookies ' . $this->obfuscate_cookies_for_script() . "\n";
        foreach ($table->rawdata as $attempt) {
            // Try to avoid time-outs.
            core_php_time_limit::raise(60);
            flush();

            if (empty($attempt->attempt)) {
                // Not actually an attempt.
                continue;
            }
            if ($attempt->state != quiz_attempt::FINISHED) {
                // Attempt not relevant.
                continue;
            }

            // Save the Review sheet as a PDF.
            $folder = $this->generate_attempt_folder_name($attempt);
            echo "\nsave-pdf " . $CFG->wwwroot .
                    '/mod/quiz/report/answersheets/attemptsheet.php?attempt=' . $attempt->attempt .
                    '&userinfo=' . $options->combine_user_info_visibility() .
                    ' as ' . $folder . '/responses.pdf' . "\n";

            if (!$this->attempt_has_any_questions_with_files($attempt->attempt, $qtyperesponsefiles)) {
                continue;
            }

            // Load the attempt.
            $attemptobj = quiz_create_attempt_handling_errors($attempt->attempt,
                    $this->context->instanceid);

            // Save any response files.
            foreach ($attemptobj->get_slots() as $slot) {
                $qa = $attemptobj->get_question_attempt($slot);
                $fileareas = $qa->get_question()->qtype->response_file_areas();
                if (!$fileareas) {
                    // Question will not have any response files.
                    continue;
                }

                $anyfilessaved = false;
                foreach ($fileareas as $filearea) {
                    $files = $qa->get_last_qt_files($filearea, $this->context->id);
                    if (!$files) {
                        // This attempt has no files in this area.
                        continue;
                    }

                    // We have files to save.
                    $filefolder = 'q' . $attemptobj->get_question_number($slot) . '-files';
                    foreach ($files as $file) {
                        $anyfilessaved = true;
                        echo 'save-file ' . $qa->get_response_file_url($file) .
                                ' as ' . $folder . '/' . $filefolder . '/' .
                                $this->clean_filename($filearea) . '-' .
                                $this->clean_filename($file->get_filename()) . "\n";
                    }
                }

                if (!$anyfilessaved) {
                    // This question did not have any files at all.
                    echo 'save-text No_files_were_uploaded_in_response_to_this_question. as ' .
                            $folder . '/' . 'q' . $attemptobj->get_question_number($slot) .
                            '-has-no-files.txt' . "\n";
                }
            }
        }

        echo "\n# The end -- presence of this line confirms all the steps were generated.\n";
        die;
    }

    /**
     * Return an array listing all the question types which might have response files.
     *
     * @return array qtype name (e.g. essay) => array of response file area names.
     */
    protected function get_qtype_response_files(): array {
        $qtypefileareas = [];

        foreach (question_bank::get_all_qtypes() as $qtype) {
            $areas = $qtype->response_file_areas();
            if ($areas) {
                $qtypefileareas[$qtype->name()] = $areas;
            }
        }

        return $qtypefileareas;
    }

    /**
     * Check a quiz attempt to see if it contains any questions which might have response files.
     *
     * @param int $attemptid quiz attempt id.
     * @param array $qtyperesponsefiles the array returned by get_qtype_response_files();
     * @return bool true if there are any question types where the response might contain files.
     */
    protected function attempt_has_any_questions_with_files(int $attemptid, array $qtyperesponsefiles): bool {
        global $DB;

        if (empty($qtyperesponsefiles)) {
            return false;
        }

        [$qtypetest, $params] = $DB->get_in_or_equal(array_keys($qtyperesponsefiles));
        $params[] = $attemptid;

        return $DB->record_exists_sql("
                SELECT 1
                  FROM {quiz_attempts} quiza
                  JOIN {question_attempts} qa ON qa.questionusageid = quiza.uniqueid
                  JOIN {question} q ON q.id = qa.questionid
                 WHERE q.qtype $qtypetest
                   AND quiza.id = ?
            ", $params);
    }

    /**
     * Generate a sensible zip filename for this quiz.
     *
     * If possible, use the quiz idnumber, otherwise the quiz name, or
     * if all else fails, just call it 'attempts'.
     *
     * @param stdClass $quiz the quiz settings.
     * @param stdClass|cm_info $cm the course-module settings for the quiz.
     * @return string suggested filename.
     */
    protected function generate_zip_filename(stdClass $quiz, stdClass|cm_info $cm): string {
        $filename = '';
        if ($cm->idnumber) {
            $filename = $this->clean_filename($cm->idnumber);
        }
        if (!$filename) {
            $filename = $this->clean_filename($quiz->name);
        }
        if (!$filename) {
            $filename = 'attempts';
        }
        return $filename;
    }

    /**
     * Generate a sensible folder name to store one attempt.
     *
     * The general form is <user-info> for the first attempt by a user,
     * or <user-info>-attempt2 for later attempts.
     *
     * The <user-info> is the first of these that can be used
     * as the bases of a folder name: user idnumber, user login name,
     * or moodle-user-<userid>.
     *
     * @param $attempt
     * @return string suggested folder name.
     */
    protected function generate_attempt_folder_name($attempt): string {
        $name = '';
        if (!empty($attempt->idnumber)) {
            $name = $this->clean_filename($attempt->idnumber);
        }
        if (!$name && !empty($attempt->username)) {
            $name = $this->clean_filename($attempt->username);
        }
        if (!$name) {
            $name = 'moodle-user-' . $attempt->userid;
        }
        if ($attempt->attemptno > 1) {
            $name .= '-attempt' . $attempt->attemptno;
        }
        return $name;
    }

    /**
     * Take a string and turn it into something save as a filename.
     *
     * @param string $rawname some strings.
     * @return string something that should be usable as a filename.
     */
    protected function clean_filename(string $rawname): string {
        return clean_filename(preg_replace('~\s+~', '', $rawname));
    }

    /**
     * Get the user's session cookies in a form suitable to include in the script.
     * @return string
     */
    protected function obfuscate_cookies_for_script() {
        return base64_encode($_SERVER['HTTP_COOKIE']);
    }
}
