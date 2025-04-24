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
 * This file defines the setting form for the quiz answersheets report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets;

use mod_quiz\local\reports\attempts_report_options_form;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\reports\attempts_report_options_form')) {
    class_alias('\mod_quiz\local\reports\attempts_report_options_form', '\mod_quiz_attempts_report_form_parent_class_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_form.php');
    class_alias('\mod_quiz_attempts_report_form', '\mod_quiz_attempts_report_form_parent_class_alias');
}

/**
 * This file defines the setting form for the quiz answersheets report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_settings_form extends \mod_quiz_attempts_report_form_parent_class_alias {

    /**
     * Add the custom fields to the form.
     *
     * @param MoodleQuickForm $mform The form.
     */
    protected function other_preference_fields(MoodleQuickForm $mform): void {
        $field = report_display_options::possible_user_info_visibility_settings($this->_customdata['quiz']->cmobject);

        $userinfogroup = [];
        foreach (array_keys($field) as $name) {
            $userinfogroup[] = $mform->createElement('advcheckbox', 'show' . $name, '',
                report_display_options::user_info_visibility_settings_name($name));
            $mform->setDefault('show' . $name, 1);
        }
        $mform->addGroup($userinfogroup, 'userinfo', get_string('showuserinfo', 'quiz_answersheets'), [' '], false);

        $instandmarkedcbs = [];
        $instandmarkedcbs[] = $mform->createElement('advcheckbox', 'questioninstruction',
            get_string('showquestioninstruction', 'quiz_answersheets'));
        $mform->setDefault('questioninstruction', 1);

        $instandmarkedcbs[] = $mform->createElement('advcheckbox', 'marks',
            get_string('showmarkedoutoftext', 'quiz_answersheets'));
        $mform->setDefault('marks', 1);
        $mform->addGroup($instandmarkedcbs, 'instructionandmarkedcheckboxes', '', '',
            false);

        $feedbackoptions = [];
        $feedbackoptions[] = $mform->createElement('html',
            \html_writer::div(get_string('rightanswersheet', 'quiz_answersheets'), 'mr-2'));
        $feedbackoptions[] = $mform->createElement('advcheckbox', 'showcombinefeedback',
            get_string('showcombinefeedback', 'quiz_answersheets'));
        $mform->setDefault('showcombinefeedback', 1);
        $feedbackoptions[] = $mform->createElement('advcheckbox', 'showinlinefeedback',
            get_string('showinlinefeedback', 'quiz_answersheets'));
        $mform->setDefault('showinlinefeedback', 1);
        $feedbackoptions[] = $mform->createElement('advcheckbox', 'showgeneralfeedback',
            get_string('showgeneralfeedback', 'quiz_answersheets'));
        $mform->setDefault('showgeneralfeedback', 1);
        $mform->addGroup($feedbackoptions, 'combineandinlinefeedbackcheckboxes', '', '', false);
    }
}
