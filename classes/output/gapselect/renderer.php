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
 * The override qtype_gapselect_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\gapselect;

use question_attempt;
use question_display_options;
use quiz_answersheets\utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/gapselect/renderer.php');

/**
 * The override qtype_gapselect_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_gapselect_override_renderer extends \qtype_gapselect_renderer {

    /**
     * Render the embedded element
     *
     * @param question_attempt $qa
     * @param $place
     * @param question_display_options $options
     * @return string
     */
    protected function embedded_element(question_attempt $qa, $place, question_display_options $options) {
        if (utils::should_hide_inline_choice($this->page)) {
            return parent::embedded_element($qa, $place, $options);
        }
        $quizprintingrenderer = $this->page->get_renderer('quiz_answersheets');
        $question = $qa->get_question();
        $group = $question->places[$place];

        $orderedchoices = $question->get_ordered_choices($group);
        $selectoptions = [];
        foreach ($orderedchoices as $orderedchoicevalue => $orderedchoice) {
            $selectoptions[$orderedchoicevalue] = format_string($orderedchoice->text);
        }
        return $quizprintingrenderer->render_choices($selectoptions, true);
    }

}
