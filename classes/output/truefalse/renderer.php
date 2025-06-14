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
 * The override qtype_truefalse_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\truefalse;

use html_writer;
use question_attempt;
use question_display_options;
use question_state;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/truefalse/renderer.php');

/**
 * The override qtype_truefalse_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_truefalse_override_renderer extends \qtype_truefalse_renderer {

    /**
     * The code was copied from question/type/truefalse/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_var('answer', '');

        $inputname = $qa->get_qt_field_name('answer');
        $trueattributes = [
            'type' => 'radio',
            'name' => $inputname,
            'value' => 1,
            'id' => $inputname . 'true',
        ];
        $falseattributes = [
            'type' => 'radio',
            'name' => $inputname,
            'value' => 0,
            'id' => $inputname . 'false',
        ];

        if ($options->readonly) {
            $trueattributes['disabled'] = 'disabled';
            $falseattributes['disabled'] = 'disabled';
        }

        // Work out which radio button to select (if any).
        $responsearray = [];
        if ($response) {
            $trueattributes['checked'] = 'checked';
            $truechecked = true;
            $responsearray = ['answer' => 1];
        } else if ($response !== '') {
            $falseattributes['checked'] = 'checked';
            $falsechecked = true;
            $responsearray = ['answer' => 1];
        }

        // Work out visual feedback for answer correctness.
        $trueclass = '';
        $falseclass = '';
        $truefeedbackimg = '';
        $falsefeedbackimg = '';
        // Modification starts.
        /* Comment out core code.
        if ($options->correctness) {
            if ($truechecked) {
                $trueclass = ' ' . $this->feedback_class((int) $question->rightanswer);
                $truefeedbackimg = $this->feedback_image((int) $question->rightanswer);
            } else if ($falsechecked) {
                $falseclass = ' ' . $this->feedback_class((int) (!$question->rightanswer));
                $falsefeedbackimg = $this->feedback_image((int) (!$question->rightanswer));
            }
        }
        */

        $truefeedback = '';
        $falsefeedback = '';
        if ($options->correctness) {
            $trueclass = ' ' . $this->feedback_class((int) $question->rightanswer);
            $truefeedbackimg = $this->feedback_image((int) $question->rightanswer);
            $falseclass = ' ' . $this->feedback_class((int) (!$question->rightanswer));
            $falsefeedbackimg = $this->feedback_image((int) (!$question->rightanswer));
        }
        if ($options->feedback) {
            $truefeedback = html_writer::tag('div',
                $question->make_html_inline($question->format_text($question->truefeedback, $question->truefeedbackformat, $qa,
                    'question', 'answerfeedback', $question->trueanswerid)), ['class' => 'specificfeedback']);
            $falsefeedback = html_writer::tag('div',
                $question->make_html_inline($question->format_text($question->falsefeedback, $question->falsefeedbackformat,
                    $qa, 'question', 'answerfeedback', $question->falseanswerid)), ['class' => 'specificfeedback']);
        }

        // Modification ends.
        $choicetrue = html_writer::div(get_string('true', 'qtype_truefalse'), 'flex-fill ml-1');
        $choicefalse = html_writer::div(get_string('false', 'qtype_truefalse'), 'flex-fill ml-1');

        $radiotrue = html_writer::empty_tag('input', $trueattributes) .
            html_writer::tag('label', $choicetrue, [
                'for' => $trueattributes['id'],
                'class' => 'd-flex w-auto ml-1']
            );
        $radiofalse = html_writer::empty_tag('input', $falseattributes) .
            html_writer::tag('label', $choicefalse, [
                'for' => $falseattributes['id'],
                'class' => 'd-flex w-auto ml-1',
            ]);

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        $result .= html_writer::start_tag('div', ['class' => 'ablock']);
        $result .= html_writer::tag('div', get_string('selectone', 'qtype_truefalse'), ['class' => 'prompt']);

        $result .= html_writer::start_tag('div', ['class' => 'answer']);
        // Modification starts.
        /* Comment out core code.
        $result .= html_writer::tag('div', $radiotrue . ' ' . $truefeedbackimg,
                array('class' => 'r0' . $trueclass));
        $result .= html_writer::tag('div', $radiofalse . ' ' . $falsefeedbackimg,
                array('class' => 'r1' . $falseclass));
        */
        $result .= html_writer::tag('div', $radiotrue . ' ' . $truefeedbackimg . ' ' . $truefeedback,
                ['class' => 'r0' . $trueclass]);
        $result .= html_writer::tag('div', $radiofalse . ' ' . $falsefeedbackimg . ' ' . $falsefeedback,
                ['class' => 'r1' . $falseclass]);
        // Modification ends.
        $result .= html_writer::end_tag('div'); // Answer.

        $result .= html_writer::end_tag('div'); // Ablock.

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                $question->get_validation_error($responsearray), ['class' => 'validationerror']);
        }

        return $result;
    }

}
