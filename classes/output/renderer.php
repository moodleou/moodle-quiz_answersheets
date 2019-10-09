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
                utils::get_user_details($attemptuser, $context)),
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
     * @return string Html string
     */
    public function render_print_button(): string {
        return html_writer::tag('button', get_string('print', 'quiz_answersheets'),
                ['type' => 'button', 'class' => 'print-sheet btn btn-secondary', 'onclick' => 'window.print();return false;']);
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
        $qinstruction = utils::get_question_instruction($qa->get_question()->get_type_name());
        if (!empty($qtoutput)) {
            $output .= html_writer::div($qinstruction, 'question-instruction');
            $output .= parent::formulation($qa, $behaviouroutput, $qtoutput, $options);
        }

        return $output;
    }
}
