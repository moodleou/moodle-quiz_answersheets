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
use question_bank;
use question_display_options;
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
    public function render_question_attempt_content(quiz_attempt $attemptobj, bool $isreviewing = true): string {
        $output = '';

        $slots = $attemptobj->get_slots();
        $attempt = $attemptobj->get_attempt();
        $displayoptions = $attemptobj->get_display_options($isreviewing);
        $displayoptions->flags = question_display_options::HIDDEN;
        $displayoptions->manualcommentlink = null;
        $displayoptions->context = context_module::instance($attemptobj->get_cmid());
        $displayoptions->history = question_display_options::HIDDEN;
        $rightanswer = $this->page->url->get_param('rightanswer');
        $qoutput = $this->page->get_renderer('quiz_answersheets', 'qtype_override');

        foreach ($slots as $slot) {
            $originalslot = $attemptobj->get_original_slot($slot);
            $questionnumber = $attemptobj->get_question_number($originalslot);

            $qa = $attemptobj->get_question_attempt($slot);
            $qtoutput = $qa->get_question()->get_renderer($this->page);
            $behaviouroutput = $this->page->get_renderer(get_class($qa->get_behaviour()));
            $displayoptions = clone($displayoptions);
            $qa->get_behaviour()->adjust_display_options($displayoptions);

            if ($rightanswer && $attempt->state == quiz_attempt::IN_PROGRESS) {
                $correctresponse = $qa->get_correct_response();
                if (!is_null($correctresponse)) {
                    $qa->process_action($correctresponse);
                }
            }

            $output .= $qoutput->question($qa, $behaviouroutput, $qtoutput, $displayoptions, $questionnumber);
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
    public function render_question_attempt_form(quiz_attempt $attemptobj, string $redirect = ''): string {
        $output = '';

        $attemptuser = \core_user::get_user($attemptobj->get_userid());
        $context = context_module::instance((int) $attemptobj->get_cmid());

        // Start the form.
        $output .= html_writer::start_tag('form', ['action' => new moodle_url('/mod/quiz/report/answersheets/processresponses.php',
                ['cmid' => $attemptobj->get_cmid()]), 'method' => 'post', 'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8', 'id' => 'responseform']);
        $output .= html_writer::start_tag('div');

        $output .= $this->render_question_attempt_content($attemptobj, false);

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
                utils::get_user_details($attemptuser, $context, ['username', 'idnumber'])),
                ['type' => 'button', 'class' => 'submit-responses btn btn-primary']);

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $langstring = [
                'title' => get_string('confirmation', 'admin'),
                'body' => get_string('submit_student_responses_dialog_content', 'quiz_answersheets')
        ];

        $this->page->requires->js_call_amd('quiz_answersheets/submit_student_responses', 'init', ['lang' => $langstring]);

        $output .= $this->output->single_button($redirect, get_string('cancel'), 'get');

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
     * @return string HTML string
     */
    public function render_attempt_sheet(array $sumdata, quiz_attempt $attemptobj): string {
        $quizrenderer = $this->page->get_renderer('mod_quiz');
        $templatecontext = [
                'questionattemptheader' => utils::get_attempt_sheet_print_header($attemptobj),
                'questionattemptsumtable' => $quizrenderer->review_summary_table($sumdata, 0),
                'questionattemptcontent' => $this->render_question_attempt_content($attemptobj)
        ];
        $isgecko = \core_useragent::is_gecko();
        // We need to use specific layout for Gecko because it does not fully support display flex and table.
        if ($isgecko) {
            return $this->render_from_template('quiz_answersheets/attempt_sheet_gecko', $templatecontext);
        } else {
            return $this->render_from_template('quiz_answersheets/attempt_sheet', $templatecontext);
        }
    }

}

/**
 * The override core_question_renderer for the quiz_answersheets module.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_override_renderer extends \core_question_renderer {

    protected function formulation(question_attempt $qa, qbehaviour_renderer $behaviouroutput, qtype_renderer $qtoutput,
            question_display_options $options) {
        global $attemptobj;
        // We need to use global trick here because in mod/quiz/report/answersheets/submitresponses.php:23 already loaded the attemptobj
        // so we no need to do the extra Database query.
        $output = '';

        $rightanswer = $this->page->url->get_param('rightanswer');

        if ($rightanswer && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS ||
                $this->page->pagetype == 'mod-quiz-report-answersheets-submitresponses') {
            // Do not show question instruction for right answer sheet and submit responses page.
            return parent::formulation($qa, $behaviouroutput, $qtoutput, $options);
        }

        // Append question instruction if exist.
        $output .= $this->render_question_instruction($qa);
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
                $qtypelocalname = question_bank::get_qtype_name($qtypename);
                $qinstruction = utils::get_question_instruction($qtypename, $qtypelocalname);
                if (!empty($qinstruction)) {
                    $output .= html_writer::tag('li', $qinstruction);
                }
            }
            $output .= html_writer::end_tag('ul');
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
}
