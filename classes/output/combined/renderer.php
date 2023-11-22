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
 * The override qtype_combined_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\combined;

use coding_exception;
use html_writer;
use qtype_combined_combinable_base;
use qtype_combined_combinable_type_base;
use qtype_combined_response_array_param;
use question_attempt;
use question_display_options;
use question_state;
use quiz_answersheets\utils;

defined('MOODLE_INTERNAL') || die();

// Work-around when the class does not exist.
if (class_exists('\qtype_combined_renderer')) {
    class_alias('\qtype_combined_renderer', '\qtype_combined_renderer_alias');
    require_once($CFG->dirroot . '/question/type/combined/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_combined_renderer_alias');
}

if (class_exists('\qtype_oumultiresponse_embedded_renderer')) {
    class_alias('\qtype_oumultiresponse_embedded_renderer', '\qtype_oumultiresponse_embedded_renderer_alias');
    require_once($CFG->dirroot . '/question/type/oumultiresponse/combinable/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_oumultiresponse_embedded_renderer_alias');
}

if (class_exists('\qtype_combined_gapselect_embedded_renderer')) {
    class_alias('\qtype_combined_gapselect_embedded_renderer', '\qtype_combined_gapselect_embedded_renderer_alias');
    require_once($CFG->dirroot . '/question/type/combined/combinable/gapselect/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_combined_gapselect_embedded_renderer_alias');
}

/**
 * The override qtype_combined_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_override_renderer extends \qtype_combined_renderer_alias {

    /**
     * The code was copied from question/type/combined/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     * @throws coding_exception
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        // Modification starts.
        /* Comment out core code.
        $questiontext = $question->combiner->render_subqs($questiontext, $qa, $options);
        */
        $questiontext = $this->render_subqs($questiontext, $qa, $options);
        // Modification ends.

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_step()->get_all_data()),
                    array('class' => 'validationerror'));
        }
        return $result;
    }

    /**
     * The code was copied from question/type/combined/combiner/runtime.php, with modifications.
     *
     * @param string $questiontext question text with embed codes to replace
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string question text with embed codes replaced
     */
    private function render_subqs($questiontext, question_attempt $qa, question_display_options $options) {
        // This will be an array $startpos => array('length' => $embedcodelen, 'replacement' => $html).
        $replacements = array();
        $subqs = utils::get_reflection_property($qa->get_question()->combiner, 'subqs');

        // Modification starts.
        /* Comment out core code.
        foreach ($this->subqs as $subq) {
        */
        foreach ($subqs as $subq) {
            // Modification ends.
            $embedcodes = $subq->question_text_embed_codes();
            $currentpos = 0;
            foreach ($embedcodes as $placeno => $embedcode) {
                // Modification starts.
                /* Comment out core code.
                $renderedembeddedquestion = $subq->type->embedded_renderer()->subquestion($qa, $options, $subq, $placeno);
                */
                $embeddedrenderer = $this->get_embedded_renderer($subq->type);
                $renderedembeddedquestion = $embeddedrenderer->subquestion($qa, $options, $subq, $placeno);
                // Modification ends.

                // Now replace the first occurrence of the placeholder.
                $pos = strpos($questiontext, $embedcode, $currentpos);
                if ($pos === false) {
                    throw new coding_exception('Expected subquestion ' . $embedcode .
                            ' code not found in question text ' . $questiontext);
                }
                $embedcodelen = strlen($embedcode);
                $replacements[$pos] = array('length' => $embedcodelen, 'replacement' => $renderedembeddedquestion);
                $questiontext = substr_replace($questiontext,
                        str_repeat('X', $embedcodelen), $pos, $embedcodelen);
                $currentpos = $pos + $embedcodelen;
            }
        }

        // Now we actually do the replacements working from the end of the string,
        // so each replacement does not change the position of things still to be
        // replaced.
        krsort($replacements);
        foreach ($replacements as $startpos => $details) {
            $questiontext = substr_replace($questiontext,
                    $details['replacement'], $startpos, $details['length']);
        }

        return $questiontext;
    }

    /**
     * Get the embedded renderer.
     *
     * @param qtype_combined_combinable_type_base $questiontype
     * @return \qtype_renderer|qtype_oumultiresponse_embedded_override_renderer
     */
    private function get_embedded_renderer(qtype_combined_combinable_type_base $questiontype) {
        $qtypename = utils::get_reflection_property($questiontype, 'qtypename');
        if ($qtypename == 'oumultiresponse') {
            return new qtype_oumultiresponse_embedded_override_renderer($this->page, null);
        } else if ($qtypename == 'gapselect') {
            return new qtype_combined_gapselect_embedded_override_renderer($this->page, null);
        } else {
            return $questiontype->embedded_renderer();
        }
    }

}

/**
 * Class qtype_oumultiresponse_embedded_override_renderer
 *
 * @package quiz_answersheets\output\combined
 */
class qtype_oumultiresponse_embedded_override_renderer extends \qtype_oumultiresponse_embedded_renderer_alias {

    /**
     * The code was copied from question/type/oumultiresponse/combinable/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param qtype_combined_combinable_base $subq
     * @param $placeno
     * @return string
     */
    public function subquestion(question_attempt $qa, question_display_options $options, qtype_combined_combinable_base $subq,
            $placeno) {
        $question = $subq->question;
        $fullresponse = new qtype_combined_response_array_param($qa->get_last_qt_data());
        $response = $fullresponse->for_subq($subq);

        $commonattributes = array(
                'type' => 'checkbox'
        );

        if ($options->readonly) {
            $commonattributes['disabled'] = 'disabled';
        }

        $checkboxes = array();
        $feedbackimg = array();
        $classes = array();
        foreach ($question->get_order($qa) as $value => $ansid) {
            $inputname = $qa->get_qt_field_name($subq->step_data_name('choice'.$value));
            $ans = $question->answers[$ansid];
            $inputattributes = array();
            $inputattributes['name'] = $inputname;
            $inputattributes['value'] = 1;
            $inputattributes['id'] = $inputname;
            // Modification starts.
            /* Comment out core code.
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            }
            */
            $inputattributes['checked'] = 'checked';
            // Modification ends.
            $hidden = '';
            if (!$options->readonly) {
                $hidden = html_writer::empty_tag('input', array(
                        'type' => 'hidden',
                        'name' => $inputattributes['name'],
                        'value' => 0,
                ));
            }

            $checkboxes[] = html_writer::empty_tag('input', $inputattributes + $commonattributes) .
                    html_writer::tag('label',
                            html_writer::span(
                                    \qtype_combined\utils::number_in_style($value, $question->answernumbering),
                                    'answernumber') .
                            $question->make_html_inline($question->format_text(
                                    $ans->answer, $ans->answerformat, $qa, 'question', 'answer', $ansid)),
                            ['for' => $inputattributes['id']]);

            $class = 'r' . ($value % 2);
            // Modification starts.
            /* Comment out core code.
            if ($options->correctness && $isselected) {
            */
            if ($options->correctness) {
                // Modification ends.
                $iscbcorrect = ($ans->fraction > 0) ? 1 : 0;
                $feedbackimg[] = $this->feedback_image($iscbcorrect);
                $class .= ' ' . $this->feedback_class($iscbcorrect);
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        $cbhtml = '';

        if ('h' === $subq->get_layout()) {
            $inputwraptag = 'span';
        } else {
            $inputwraptag = 'div';
        }

        foreach ($checkboxes as $key => $checkbox) {
            $cbhtml .= html_writer::tag($inputwraptag, $checkbox . ' ' . $feedbackimg[$key],
                            array('class' => $classes[$key])) . "\n";
        }

        $result = html_writer::tag($inputwraptag, $cbhtml, array('class' => 'answer'));

        return $result;
    }

}

/**
 * Class qtype_combined_gapselect_embedded_override_renderer
 *
 * @package quiz_answersheets\output\combined
 */
class qtype_combined_gapselect_embedded_override_renderer extends \qtype_combined_gapselect_embedded_renderer_alias {

    /**
     * Render the sub question.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param qtype_combined_combinable_base $subq
     * @param $placeno
     * @return string
     */
    public function subquestion(question_attempt $qa, question_display_options $options, qtype_combined_combinable_base $subq,
            $placeno) {
        if (utils::should_hide_inline_choice($this->page)) {
            return parent::subquestion($qa, $options, $subq, $placeno);
        }
        $quizprintingrenderer = $this->page->get_renderer('quiz_answersheets');
        $question = $subq->question;
        $place = $placeno + 1;
        $group = $question->places[$place];

        $orderedchoices = $question->get_ordered_choices($group);
        $selectoptions = array();
        foreach ($orderedchoices as $orderedchoicevalue => $orderedchoice) {
            $selectoptions[$orderedchoicevalue] = $orderedchoice->text;
        }

        return $quizprintingrenderer->render_choices($selectoptions, true);
    }

}
