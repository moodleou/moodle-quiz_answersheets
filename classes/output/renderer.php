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
use plugin_renderer_base;
use question_display_options;
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
     * @return string HTML string
     */
    public function render_question_attempt_content(quiz_attempt $attemptobj): string {
        $output = '';

        $slots = $attemptobj->get_slots();
        $displayoptions = $attemptobj->get_display_options(true);
        $displayoptions->flags = question_display_options::HIDDEN;
        $displayoptions->manualcommentlink = null;
        $displayoptions->context = context_module::instance($attemptobj->get_cmid());

        foreach ($slots as $slot) {
            $originalslot = $attemptobj->get_original_slot($slot);
            $questionnumber = $attemptobj->get_question_number($originalslot);

            $output .= $attemptobj->get_question_attempt($slot)->render($displayoptions, $questionnumber);
        }

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
