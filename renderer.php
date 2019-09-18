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

defined('MOODLE_INTERNAL') || die();

/**
 * The renderer for the quiz_answersheets module.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_answersheets_renderer extends plugin_renderer_base {

    /**
     * Render the questions content of a particular quiz attempt.
     *
     * @param quiz_attempt $attemptobj Attempt object
     * @return string HTML string
     */
    public function render_question_attempt_content(quiz_attempt $attemptobj): string {
        $output = '';

        $slots = $attemptobj->get_slots();
        $attempt = $attemptobj->get_attempt();
        $showhistoryfeedback = $attempt->state == quiz_attempt::FINISHED;

        foreach ($slots as $slot) {
            $originalslot = $attemptobj->get_original_slot($slot);
            $questionnumber = $attemptobj->get_question_number($originalslot);
            $displayoptions = $attemptobj->get_display_options_with_edit_link(true, $slot, null);
            $displayoptions->marks = question_display_options::MARK_AND_MAX;
            $displayoptions->manualcomment = question_display_options::VISIBLE;

            // Only show Feedback, History, Correctness, Right answer... for Finished attempt.
            $displayoptions->feedback = $showhistoryfeedback;
            $displayoptions->history = $showhistoryfeedback;
            $displayoptions->correctness = $showhistoryfeedback;
            $displayoptions->numpartscorrect = $showhistoryfeedback;
            $displayoptions->rightanswer = $showhistoryfeedback;

            $displayoptions->flags = question_display_options::HIDDEN;
            $displayoptions->manualcommentlink = null;

            if ($slot != $originalslot) {
                $attemptobj->get_question_attempt($slot)->set_max_mark($attemptobj->get_question_attempt($originalslot)
                    ->get_max_mark());
            }

            $quba = question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
            $output .= $quba->render_question($slot, $displayoptions, $questionnumber);
        }

        return $output;
    }

    /**
     * Render print button.
     *
     * @return string Html string
     */
    public function render_print_button() {
        return html_writer::tag('button', get_string('print', 'quiz_answersheets'),
                ['type' => 'button', 'class' => 'print-sheet btn btn-secondary', 'onclick' => 'window.print();return false;']);
    }

}
