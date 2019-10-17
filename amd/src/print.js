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
 * Javascript for print event.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var t = {

        /**
         * Server side config.
         */
        mconfig: null,

        /**
         * Initialise function
         * @param {object} options Options for ajax request
         */
        init: function(options) {
            t.mconfig = options;
            window.addEventListener('beforeprint', function(e) {
                e.preventDefault();
                var promises = Ajax.call([{
                    methodname: 'quiz_answersheets_create_event',
                    args: {
                        attemptid: t.mconfig.attemptid,
                        userid: t.mconfig.userid,
                        courseid: t.mconfig.courseid,
                        cmid: t.mconfig.cmid,
                        quizid: t.mconfig.quizid,
                        pagetype: t.mconfig.pagetype
                    }
                }]);
                // Handle promise.
                promises[0].then(function() {
                    return true;
                }).fail(function(err) {
                    Notification.exception(err);
                });
            });
        }

    };

    return t;
});
