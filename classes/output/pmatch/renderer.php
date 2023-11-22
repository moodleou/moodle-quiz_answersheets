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
 * The override qtype_pmatch_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\pmatch;

defined('MOODLE_INTERNAL') || die();

use qtype_pmatch_question;
use question_display_options;

// Work-around when the class does not exist.
if (class_exists('\qtype_pmatch_renderer')) {
    class_alias('\qtype_pmatch_renderer', '\qtype_pmatch_renderer_alias');
    require_once($CFG->dirroot . '/question/type/pmatch/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_pmatch_renderer_alias');
}

/**
 * The override qtype_pmatch_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pmatch_override_renderer extends \qtype_pmatch_renderer_alias {

    public function question_tests_link(qtype_pmatch_question $question, question_display_options $options) {
        // Do not show the question test link.
        return;
    }

}
