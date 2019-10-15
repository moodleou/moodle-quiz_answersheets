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
 * This file defines the options for the quiz answersheets report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets;

use context_module;
use quiz_attempts_report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_options.php');

/**
 * This file defines the options for the quiz answersheets report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_display_options extends \mod_quiz_attempts_report_options {

    /**@var int Last changed row id */
    public $lastchanged;

    public function __construct($mode, $quiz, $cm, $course) {
        parent::__construct($mode, $quiz, $cm, $course);
        $this->attempts = quiz_attempts_report::ENROLLED_ALL;
    }

    public function resolve_dependencies() {
        parent::resolve_dependencies();
        // We only want to show the checkbox to delete attempts
        // if the user has permissions and if the report mode is showing attempts.
        $this->checkboxcolumn = has_capability('mod/quiz:deleteattempts', context_module::instance($this->cm->id))
                && ($this->attempts != quiz_attempts_report::ENROLLED_WITHOUT);
    }

    public function setup_from_params() {
        parent::setup_from_params();
        $this->lastchanged = optional_param('lastchanged', 0, PARAM_INT);
    }

}
