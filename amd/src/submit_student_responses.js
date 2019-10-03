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
 * Javascript for submit student responses
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/notification'],
    function($, ModalFactory, ModalEvents, Notification) {

    var t = {

        SELECTOR: {
            SUBMIT_RESPONSES_BUTTON: '.submit-responses',
            RESPONSE_FORM: '#responseform'
        },

        /**
         * Initialise function
         *
         * @param {object} lang Lang string
         */
        init: function(lang) {
            var submitResponsesButton = $(t.SELECTOR.SUBMIT_RESPONSES_BUTTON);
            var responseForm = $(t.SELECTOR.RESPONSE_FORM);

            submitResponsesButton.click(function(e) {
                e.preventDefault();
                submitResponsesButton.attr('disabled', 'disabled');
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: lang.title,
                    body: lang.body
                }).then(function(modal) {
                    modal.show();
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        submitResponsesButton.removeAttr('disabled');
                        if (typeof M.core_formchangechecker !== 'undefined') {
                            M.core_formchangechecker.set_form_submitted();
                        }
                        responseForm.submit();
                    });
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        modal.destroy();
                        submitResponsesButton.removeAttr('disabled');
                    });
                    return modal;
                }).fail(function(err) {
                    Notification.exception(err);
                    submitResponsesButton.removeAttr('disabled');
                });
            });
        }
    };

    return t;
});
