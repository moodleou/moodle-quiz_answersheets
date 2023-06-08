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
 * Defines the renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output;

use context_module;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use qbehaviour_renderer;
use qtype_renderer;
use question_attempt;
use question_display_options;
use quiz_answersheets\report_display_options;
use quiz_answersheets\utils;
use quiz_attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * The renderer for the quiz_answersheets module.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the questions content of a particular quiz attempt.
     *
     * @param quiz_attempt $attemptobj Attempt object
     * @param bool $isreviewing True for review page, else attempt page
     * @return string HTML string
     */
    public function render_question_attempt_content(quiz_attempt $attemptobj, report_display_options $reportoptions,
            bool $isreviewing = true): string {
        $output = '';

        $slots = $attemptobj->get_slots();
        $attempt = $attemptobj->get_attempt();
        $displayoptions = $attemptobj->get_display_options($isreviewing);
        $displayoptions->flags = question_display_options::HIDDEN;
        $displayoptions->manualcommentlink = null;
        $displayoptions->context = context_module::instance($attemptobj->get_cmid());
        $displayoptions->history = question_display_options::HIDDEN;
        $displayoptions->marks = $reportoptions->marks;
        $rightanswer = $reportoptions->rightanswer;
        $qoutput = $this->page->get_renderer('quiz_answersheets', 'core_question_override');

        foreach ($slots as $slot) {
            // Clone the display option for each question.
            $cloneddisplayoptions = clone($displayoptions);
            $originalslot = $attemptobj->get_original_slot($slot);
            $questionnumber = $attemptobj->get_question_number($originalslot);

            $qa = $attemptobj->get_question_attempt($slot);
            $qtoutput = utils::get_question_renderer($this->page, $qa);
            $behaviouroutput = $this->page->get_renderer(get_class($qa->get_behaviour()));

            if (utils::should_show_combined_feedback($qa->get_question()->get_type_name()) && $rightanswer) {
                $cloneddisplayoptions->generalfeedback = question_display_options::HIDDEN;
                $cloneddisplayoptions->numpartscorrect = question_display_options::HIDDEN;
                $cloneddisplayoptions->rightanswer = question_display_options::HIDDEN;
            }

            if ($rightanswer && $attempt->state == quiz_attempt::IN_PROGRESS) {
                $correctresponse = $qa->get_correct_response();
                if (!is_null($correctresponse)) {
                    $qa->process_action($correctresponse);
                }
            } else {
                // Only adjust the display option for Attempt sheet and Submit responses.
                $qa->get_behaviour()->adjust_display_options($cloneddisplayoptions);
            }

            $output .= $qoutput->question($qa, $behaviouroutput, $qtoutput, $cloneddisplayoptions, $questionnumber);
        }

        return $output;
    }

    /**
     * Render the questions attempt form of a particular quiz attempt.
     * Part of code was copied from mod/quiz/renderer.php:attempt_form().
     *
     * @param quiz_attempt $attemptobj
     * @param string $redirect
     * @return string HTML string
     */
    public function render_question_attempt_form(quiz_attempt $attemptobj, report_display_options $reportoptions,
            string $redirect = ''): string {
        $output = '';

        $attemptuser = \core_user::get_user($attemptobj->get_userid());
        $context = context_module::instance((int) $attemptobj->get_cmid());

        // Start the form.
        $output .= html_writer::start_tag('form', ['action' => new moodle_url('/mod/quiz/report/answersheets/processresponses.php',
                ['cmid' => $attemptobj->get_cmid()]), 'method' => 'post', 'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8', 'id' => 'responseform']);
        $output .= html_writer::start_tag('div');

        $output .= $this->render_question_attempt_content($attemptobj, $reportoptions, false);

        // Some hidden fields to trach what is going on.
        $output .= html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'attempt', 'value' => $attemptobj->get_attemptid()]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'finishattempt', 'value' => 1]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'redirect', 'value' => $redirect]);

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'slots', 'value' => implode(',', $attemptobj->get_active_slots())]);

        $output .= html_writer::tag('button', get_string('submit_student_responses_on_behalf', 'quiz_answersheets',
                utils::get_user_details($attemptuser, $context, ['fullname', 'username', 'idnumber'])),
                ['type' => 'button', 'class' => 'submit-responses btn btn-primary']);

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $langstring = [
                'title' => get_string('confirmation', 'admin'),
                'body' => get_string('submit_student_responses_dialog_content', 'quiz_answersheets')
        ];

        $this->page->requires->js_call_amd('quiz_answersheets/submit_student_responses', 'init', ['lang' => $langstring]);

        $cancelurl = new moodle_url($redirect);
        // No need to highlight for Cancel action.
        $cancelurl->remove_params('lastchanged');
        $output .= $this->output->single_button($cancelurl, get_string('cancel'), 'get', ['class' => 'cancel-submit-responses']);

        return $output;
    }

    /**
     * Render print button.
     *
     * @param quiz_attempt $attemptobj
     * @param bool $isrightanswer
     * @return string Html string
     */
    public function render_print_button(quiz_attempt $attemptobj, bool $isrightanswer): string {
        $data = [
                'attemptid' => $attemptobj->get_attemptid(),
                'userid' => $attemptobj->get_userid(),
                'courseid' => $attemptobj->get_courseid(),
                'cmid' => $attemptobj->get_cmid(),
                'quizid' => $attemptobj->get_quizid(),
                'pagetype' => $isrightanswer ? utils::RIGHT_ANSWER_SHEET_PRINTED : utils::ATTEMPT_SHEET_PRINTED
        ];

        $this->page->requires->js_call_amd('quiz_answersheets/print', 'init', [$data]);

        return html_writer::tag('button', get_string('print', 'quiz_answersheets'),
                ['type' => 'button', 'class' => 'print-sheet btn btn-secondary', 'onclick' => 'window.print();return false;']);
    }

    /**
     * Render attempt sheet page
     *
     * @param array $sumdata Contains row data for table
     * @param quiz_attempt $attemptobj Attempt object
     * @param string $sheettype Sheet type
     * @return string HTML string
     */
    public function render_attempt_sheet(array $sumdata, quiz_attempt $attemptobj,
            string $sheettype, report_display_options $reportoptions): string {
        $quizrenderer = $this->page->get_renderer('mod_quiz');
        $templatecontext = [
                'questionattemptheader' => utils::get_attempt_sheet_print_header($attemptobj, $sheettype, $reportoptions),
                'questionattemptsumtable' => $quizrenderer->review_summary_table($sumdata, 0),
                'questionattemptcontent' => $this->render_question_attempt_content($attemptobj, $reportoptions)
        ];
        $isgecko = \core_useragent::is_gecko();
        // We need to use specific layout for Gecko because it does not fully support display flex and table.
        if ($isgecko) {
            return $this->render_from_template('quiz_answersheets/attempt_sheet_gecko', $templatecontext);
        } else {
            return $this->render_from_template('quiz_answersheets/attempt_sheet', $templatecontext);
        }
    }

    /**
     * Render page navigation
     *
     * @return string HTML string
     */
    public function render_attempt_navigation(): string {
        $output = '';

        $output .= html_writer::start_div('clearfix', ['id' => 'page-navbar']);
        $output .= html_writer::tag('div', $this->output->navbar(), ['class' => 'breadcrumb-nav']);
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Render the link to the bulk download instructions.
     *
     * @param report_display_options $options the report options.
     * @return string HTML.
     */
    public function bulk_download_link(report_display_options $options): string {
        $bulkurl = $options->get_url();
        $bulkurl->param('bulk', 1);

        return html_writer::div(html_writer::link($bulkurl,
                get_string('bulkdownloadlink', 'quiz_answersheets')));

    }

    /**
     * Render the instructions with the link to the steps file and what to do with it.
     *
     * @param report_display_options $options the report options.
     * @param string $filename the name of the zip file to be generated without the .zip bit.
     * @param \context $context the quiz context.
     * @return string HTML.
     */
    public function bulk_download_instructions(report_display_options $options,
            string $filename, \context $context): string {

        $bulkscripturl = $options->get_url();
        $bulkscripturl->param('bulkscript', 1);

        $a = new \stdClass();
        $a->scripturl = $bulkscripturl->out();
        $a->scriptname = $filename;

        $output = '';
        $output .= $this->output->heading(get_string('bulkinstructionstitle', 'quiz_answersheets'), 3);
        $output .= $this->output->notification(get_string('bulkinstructionswarning', 'quiz_answersheets'),
                \core\output\notification::NOTIFY_WARNING);
        $output .= format_text(get_string('bulkinstructions', 'quiz_answersheets', $a),
                FORMAT_MARKDOWN, ['context' => $context]);
        return $output;
    }

    /**
     * Render the choice list.
     *
     * @param array $choices List of choices
     * @param bool $inline Render the choices inline or not
     * @return string HTML string
     */
    public function render_choices(array $choices, bool $inline): string {
        $output = '';

        if (!empty($choices)) {
            if (!$inline) {
                $output .= html_writer::start_tag('ul', ['class' => 'answer-list']);
                foreach ($choices as $value => $choice) {
                    $output .= html_writer::tag('li', $choice);
                }
                $output .= html_writer::end_tag('ul');
            } else {
                $output .= html_writer::span('[' . implode(' | ', $choices) . ']', 'answer-list-inline');
            }
        }

        return $output;
    }
}


/**
 * The override core_question_renderer for the quiz_answersheets module.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_question_override_renderer extends \core_question_renderer {

    protected function formulation(question_attempt $qa, qbehaviour_renderer $behaviouroutput, qtype_renderer $qtoutput,
            question_display_options $options) {
        global $attemptobj;
        // We need to use global trick here because in mod/quiz/report/answersheets/submitresponses.php:23
        // already loaded the attemptobj so we no need to do the extra Database query.
        $output = '';

        $rightanswer = $this->page->url->get_param('rightanswer');

        if ($rightanswer && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS ||
                $this->page->pagetype == 'mod-quiz-report-answersheets-submitresponses') {
            // Do not show question instruction for right answer sheet and submit responses page.
            return parent::formulation($qa, $behaviouroutput, $qtoutput, $options);
        }

        // Show default instruction if ticked.
        if ((bool) $this->page->url->get_param('instruction')) {
            // Append question instruction if exist.
            $output .= $this->render_question_instruction($qa);
        }
        $output .= parent::formulation($qa, $behaviouroutput, $qtoutput, $options);

        return $output;
    }

    protected function status(question_attempt $qa, qbehaviour_renderer $behaviouroutput, question_display_options $options) {
        // Do not show the question status.
        return '';
    }

    /**
     * Render question instruction
     *
     * @param question_attempt $qa Question attempt
     * @return string HTML string
     */
    private function render_question_instruction(question_attempt $qa) {
        $output = '';
        $question = $qa->get_question();

        if ($question->get_type_name() == 'combined') {
            // Specific code for Combined question type.
            // Get all sub questions. We need to user reflection method because it is a protected property.
            $subqs = utils::get_reflection_property($question->combiner, 'subqs');
            $output .= html_writer::start_div('question-instruction');
            if (count($subqs) > 1) {
                $output .= html_writer::start_tag('ul', ['class' => 'list']);
                $subqslist = [];
                foreach ($subqs as $subq) {
                    // Get sub question type name.
                    $qtypename = utils::get_reflection_property($subq->type, 'qtypename');
                    if (!in_array($qtypename, $subqslist)) {
                        $subqslist[] = $qtypename;
                    } else {
                        continue;
                    }
                    $qinstruction = utils::get_question_instruction($qtypename);
                    if (!empty($qinstruction)) {
                        $output .= html_writer::tag('li', $qinstruction);
                    }
                }
                $output .= html_writer::end_tag('ul');
            } else {
                $subq = $subqs[0];
                $qtypename = utils::get_reflection_property($subq->type, 'qtypename');
                $qinstruction = utils::get_question_instruction($qtypename);
                if (!empty($qinstruction)) {
                    $output .= $qinstruction;
                }
            }
            $output .= html_writer::end_div();
        } else {
            // Normal question type.
            $qinstruction = utils::get_question_instruction($question->get_type_name());
            if (!empty($qinstruction)) {
                $output .= html_writer::div($qinstruction, 'question-instruction');
            }
        }

        return $output;
    }

    public function question(question_attempt $qa, qbehaviour_renderer $behaviouroutput, qtype_renderer $qtoutput,
            question_display_options $options, $number) {
        $rightanswer = $this->page->url->get_param('rightanswer');
        $output = '';
        $output .= parent::question($qa, $behaviouroutput, $qtoutput, $options, $number);

        if (utils::should_show_combined_feedback($qa->get_question()->get_type_name()) && $rightanswer) {
            $output .= $this->render_question_combined_feedback($qa);
        }

        return $output;
    }

    /**
     * Render question combined feedback
     *
     * @param question_attempt $qa Question attempt
     * @return string HTML string
     */
    public function render_question_combined_feedback(question_attempt $qa) {
        $feedback = '';
        $incorrectfeedback = $this->get_combine_feedback($qa, 'incorrect');
        $partiallycorrectfeedback = $this->get_combine_feedback($qa, 'partiallycorrect');
        $correctfeedback = $this->get_combine_feedback($qa, 'correct');
        $generalfeedback = $qa->get_question()->format_generalfeedback($qa);

        if (!empty($incorrectfeedback)) {
            $feedback .= \html_writer::tag('h3', get_string('combine_feedback_incorrect', 'quiz_answersheets'),
                    ['class' => 'question-feedback-title']);
            $feedback .= \html_writer::div($incorrectfeedback, 'question-feedback-content');
        }
        if (!empty($partiallycorrectfeedback)) {
            $feedback .= \html_writer::tag('h3', get_string('combine_feedback_partially_correct', 'quiz_answersheets'),
                    ['class' => 'question-feedback-title']);
            $feedback .= \html_writer::div($partiallycorrectfeedback, 'question-feedback-content');
        }
        if (!empty($correctfeedback)) {
            $feedback .= \html_writer::tag('h3', get_string('combine_feedback_correct', 'quiz_answersheets'),
                    ['class' => 'question-feedback-title']);
            $feedback .= \html_writer::div($correctfeedback, 'question-feedback-content');
        }
        if (!empty($generalfeedback)) {
            $feedback .= \html_writer::tag('h3', get_string('combine_feedback_general', 'quiz_answersheets'),
                    ['class' => 'question-feedback-title']);
            $feedback .= \html_writer::div($generalfeedback, 'question-feedback-content');
        }

        if (!empty($feedback)) {
            $feedback = \html_writer::div($feedback, 'question-feedback');
        }

        return $feedback;
    }

    /**
     * Get the combine feedback for given question.
     *
     * @param question_attempt $qa Question attempt
     * @param string $type Type of feedback
     * @return string Combine feedback
     */
    public function get_combine_feedback(question_attempt $qa, string $type) {
        $question = $qa->get_question();
        $feedback = '';
        $field = $type . 'feedback';
        $format = $type . 'feedbackformat';
        if (isset($question->$field) && $question->$field) {
            $feedback .= $question->format_text($question->$field, $question->$format, $qa, 'question', $field, $question->id);
            if ($type == 'partiallycorrect' && $question->get_type_name() == 'oumultiresponse') {
                $feedback .= \html_writer::div(get_string('toomanyselected', 'qtype_multichoice'));
            }
        }

        return $feedback;
    }
}
