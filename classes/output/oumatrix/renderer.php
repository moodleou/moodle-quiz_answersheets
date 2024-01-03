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
 * The override qtype_oumatrix_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\oumatrix;

use html_writer;
use question_attempt;
use question_display_options;
use question_state;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/oumatrix/renderer.php');

/**
 * The override qtype_oumatrix_renderer for the quiz_answersheets module.
 *
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_oumatrix_override_renderer extends \qtype_oumatrix_single_renderer {

    /**
     * The code was copied from question/type/oumatrix/renderer.php, with modifications.
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        $question = $qa->get_question();
        $result = '';

        $result .= html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        // Display the matrix.
        $result .= $this->matrix_table($qa, $options);

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_qt_data()), ['class' => 'validationerror']);
        }

        return $result;
    }
}
