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
 * The override qtype_match_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\match;

use html_writer;
use question_attempt;
use question_display_options;
use question_state;
use quiz_answersheets\utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/match/renderer.php');

/**
 * The override qtype_match_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_match_override_renderer extends \qtype_match_renderer {

    /**
     * The code was copied from question/type/match/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        if (utils::should_hide_inline_choice($this->page)) {
            return parent::formulation_and_controls($qa, $options);
        }
        $quizprintingrenderer = $this->page->get_renderer('quiz_answersheets');
        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();
        $response = $qa->get_last_qt_data();

        $choices = $this->format_choices($question);
        // Modification starts.
        $choiceslist = $quizprintingrenderer->render_choices($choices, false);
        // Modification ends.

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        $result .= html_writer::start_tag('div', ['class' => 'ablock']);
        $result .= html_writer::start_tag('table', ['class' => 'answer']);
        $result .= html_writer::start_tag('tbody');

        $parity = 0;
        $i = 1;
        foreach ($stemorder as $key => $stemid) {

            $result .= html_writer::start_tag('tr', ['class' => 'r' . $parity]);
            $fieldname = 'sub' . $key;

            $result .= html_writer::tag('td', $this->format_stem_text($qa, $stemid), ['class' => 'text']);

            $classes = 'control';

            if (array_key_exists($fieldname, $response)) {
                $selected = $response[$fieldname];
            } else {
                $selected = 0;
            }

            $fraction = (int) ($selected && $selected == $question->get_right_choice_for($stemid));

            if ($options->correctness && $selected) {
                $classes .= ' ' . $this->feedback_class($fraction);
                $feedbackimage = $this->feedback_image($fraction);
            }

            // Modification starts here.
            // The following original core code has been commented out for customization purposes.
            /* Comment out core code.
            $result .= html_writer::tag('td',
                   html_writer::label(get_string('answer', 'qtype_match', $i),
                           'menu' . $qa->get_qt_field_name('sub' . $key), false,
                           array('class' => 'accesshide')) .
                   html_writer::select($choices, $qa->get_qt_field_name('sub' . $key), $selected,
                           array('0' => 'choose'), array('disabled' => $options->readonly, 'class' => 'custom-select ml-1')) .
                   ' ' . $feedbackimage, array('class' => $classes));
            */

            $result .= html_writer::tag('td', $choiceslist, ['class' => $classes]);
            // Modification ends.

            $result .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
            $i++;
        }
        $result .= html_writer::end_tag('tbody');
        $result .= html_writer::end_tag('table');

        $result .= html_writer::end_tag('div'); // Closes <div class="ablock">.

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($response), ['class' => 'validationerror']);
        }

        return $result;
    }

}
