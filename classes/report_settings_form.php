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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport_form.php');

/**
 * This file defines the setting form for the quiz answersheets report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_settings_form extends \mod_quiz_attempts_report_form {

    protected function other_preference_fields(\MoodleQuickForm $mform) {
        $field = report_display_options::possible_user_info_visibility_settings($this->_customdata['quiz']->cmobject);

        $userinfogroup = [];
        foreach ($field as $name => $notused) {
            $userinfogroup[] = $mform->createElement('advcheckbox', 'show' . $name, '',
                    report_display_options::user_info_visibility_settings_name($name));
            $mform->setDefault('show' . $name, 1);
        }
        $mform->addGroup($userinfogroup, 'userinfo',
                get_string('showuserinfo', 'quiz_answersheets'), array(' '), false);

        $mform->addElement('advcheckbox', 'questioninstruction',
                get_string('showquestioninstruction', 'quiz_answersheets'));
        $mform->setDefault('questioninstruction', 1);

    }
}
