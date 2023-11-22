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
 * The override qtype_stack_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\stack;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use qtype_stack_question;
use question_attempt;
use question_display_options;
use question_state;
use quiz_answersheets\utils;
use stack_maths;
use stack_utils;

// Work-around when the class does not exist.
if (class_exists('\qtype_stack_renderer')) {
    class_alias('\qtype_stack_renderer', '\qtype_stack_renderer_alias');
    require_once($CFG->dirroot . '/question/type/stack/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_stack_renderer_alias');
}

/**
 * The override qtype_stack_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_stack_override_renderer extends \qtype_stack_renderer_alias {

    protected function question_tests_link(qtype_stack_question $question, question_display_options $options) {
        // Do not show the question test link.
        return;
    }

    /**
     * The code was copied from question/type/stack/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        if (utils::should_hide_inline_choice($this->page)) {
            return parent::formulation_and_controls($qa, $options);
        }

        /* Return type should be @var qtype_stack_question $question. */
        $question = $qa->get_question();

        $response = $qa->get_last_qt_data();

        $questiontext = $question->questiontextinstantiated;
        // Replace inputs.
        $inputstovaldiate = array();

        // Get the list of placeholders before format_text.
        $originalinputplaceholders = array_unique(stack_utils::extract_placeholders($questiontext, 'input'));
        sort($originalinputplaceholders);
        $originalfeedbackplaceholders = array_unique(stack_utils::extract_placeholders($questiontext, 'feedback'));
        sort($originalfeedbackplaceholders);

        // Now format the questiontext.
        $questiontext = $question->format_text(
                stack_maths::process_display_castext($questiontext, $this),
                $question->questiontextformat,
                $qa, 'question', 'questiontext', $question->id);

        // Get the list of placeholders after format_text.
        $formatedinputplaceholders = stack_utils::extract_placeholders($questiontext, 'input');
        sort($formatedinputplaceholders);
        $formatedfeedbackplaceholders = stack_utils::extract_placeholders($questiontext, 'feedback');
        sort($formatedfeedbackplaceholders);

        // We need to check that if the list has changed.
        // Have we lost some of the placeholders entirely?
        // Duplicates may have been removed by multi-lang,
        // No duplicates should remain.
        if ($formatedinputplaceholders !== $originalinputplaceholders ||
                $formatedfeedbackplaceholders !== $originalfeedbackplaceholders) {
            throw new coding_exception('Inconsistent placeholders. Possibly due to multi-lang filtter not being active.');
        }

        foreach ($question->inputs as $name => $input) {
            // Get the actual value of the teacher's answer at this point.
            $tavalue = $question->get_ta_for_input($name);

            $fieldname = $qa->get_qt_field_name($name);
            $state = $question->get_input_state($name, $response);

            // Modification starts.
            /* Comment out core code.
            $questiontext = str_replace("[[input:{$name}]]",
                    $input->render($state, $fieldname, $options->readonly, $tavalue),
                    $questiontext);
            */
            if (get_class($input) == 'stack_dropdown_input') {
                $questiontext = str_replace("[[input:{$name}]]", $this->render_choices($input), $questiontext);
            } else {
                $questiontext = str_replace("[[input:{$name}]]", $input->render($state, $fieldname, $options->readonly, $tavalue),
                        $questiontext);
            }
            // Modification ends.

            $questiontext = $input->replace_validation_tags($state, $fieldname, $questiontext);

            if ($input->requires_validation()) {
                $inputstovaldiate[] = $name;
            }
        }

        // Replace PRTs.
        foreach ($question->prts as $index => $prt) {
            $feedback = '';
            if ($options->feedback) {
                $feedback = $this->prt_feedback($index, $response, $qa, $options, $prt->get_feedbackstyle());

            } else if (in_array($qa->get_behaviour_name(), array('interactivecountback', 'adaptivemulipart'))) {
                // The behaviour name test here is a hack. The trouble is that interactive
                // behaviour or adaptivemulipart does not show feedback if the input
                // is invalid, but we want to show the CAS errors from the PRT.
                $result = $question->get_prt_result($index, $response, $qa->get_state()->is_finished());
                $feedback = html_writer::nonempty_tag('span', $result->errors,
                        array('class' => 'stackprtfeedback stackprtfeedback-' . $name));
            }
            $questiontext = str_replace("[[feedback:{$index}]]", $feedback, $questiontext);
        }

        // Initialise automatic validation, if enabled.
        if (stack_utils::get_config()->ajaxvalidation) {
            // Once we cen rely on everyone being on a Moodle version that includes the fix for
            // MDL-65029 (3.5.6+, 3.6.4+, 3.7+) we can remove this if and just call the method.
            if (method_exists($qa, 'get_outer_question_div_unique_id')) {
                $questiondivid = $qa->get_outer_question_div_unique_id();
            } else {
                $questiondivid = 'q' . $qa->get_slot();
            }
            $this->page->requires->js_call_amd('qtype_stack/input', 'initInputs',
                    [$questiondivid, $qa->get_field_prefix(),
                            $qa->get_database_id(), $inputstovaldiate]);
        }

        $result = '';
        $result .= $this->question_tests_link($question, $options) . $questiontext;

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('span',
                    $question->get_validation_error($response),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    /**
     * Render the choice inline list.
     *
     * @param \stack_dropdown_input $input Input element
     * @return string HTML string
     */
    private function render_choices(\stack_dropdown_input $input): string {
        $choices = [];
        $quizprintingrenderer = $this->page->get_renderer('quiz_answersheets');
        $answer = utils::get_reflection_property($input, 'ddlvalues');

        foreach ($answer as $key => $value) {
            if ($answer[$key]['display']) {
                $choices[] = $answer[$key]['display'];
            }
        }

        return $quizprintingrenderer->render_choices($choices, true);
    }

}
