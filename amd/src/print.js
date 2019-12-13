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
         * CSS Selector.
         */
        SELECTOR: {
            PAGE_HEADER: '.attempt-sheet-header-gecko',
            PAGE_CONTAINER: '#page'
        },

        /**
         * Page header height.
         */
        pageHeaderHeight: 0,

        /**
         * Page container.
         */
        pageContainer: null,

        /**
         * Page Header element
         */
        pageHeader: null,


        /**
         * Initialise function
         * @param {object} options Options for ajax request
         */
        init: function(options) {
            t.mconfig = options;
            t.pageHeader = $(t.SELECTOR.PAGE_HEADER);
            t.pageContainer = $(t.SELECTOR.PAGE_CONTAINER);
            t.pageHeaderHeight = t.pageHeader.height();

            // Set the correct position for page header.
            t.pageHeader.css('top', -Math.abs(t.pageHeaderHeight));
            // Set the space fof page header.
            t.pageContainer.css('margin-top', t.pageHeaderHeight);

            window.addEventListener('beforeprint', t.handlePrintStart);
            window.addEventListener('afterprint', t.handlePrintStop);
        },

        /**
         * Handle print start event.
         *
         * @param {Event} e
         */
        handlePrintStart: function(e) {
            e.preventDefault();
            t.pageContainer.css('margin-top', 0);
            if (Y.UA.gecko) {
                t.pageContainer.css('padding-top', t.pageHeaderHeight + 30);
                t.pageHeader.css('top', 0);
            }

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
        },

        /**
         * Handle print stop event.
         *
         * @param {Event} e
         * @returns {boolean}
         */
        handlePrintStop: function(e) {
            e.preventDefault();
            t.pageContainer.css('margin-top', t.pageHeaderHeight);
            if (Y.UA.gecko) {
                t.pageContainer.css('padding-top', 0);
                t.pageHeader.css('top', -Math.abs(t.pageHeaderHeight));
            }
            return true;
        }

    };

    return t;
});
