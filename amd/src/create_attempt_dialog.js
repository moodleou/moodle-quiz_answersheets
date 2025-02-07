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
 * JavaScript for the create_attempt_dialog class.
 *
 * @module    quiz_answersheets/create_attempt_dialog
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal_events', 'core/ajax', 'core/notification', 'core/modal_save_cancel'],
    function($, ModalEvents, Ajax, Notification, ModalSaveCancel) {
        var t = {
            SELECTOR: '.create-attempt-btn',
            init: function() {
                $(t.SELECTOR).click(function(e) {
                    e.preventDefault();
                    var target = $(this).closest('table').find('button');
                    t.disableButton(target, true);
                    var message = $(this).data('message');
                    var params = {
                        quizid: $(this).data('quiz-id'),
                        userid: $(this).data('user-id'),
                        url: $(this).data('url')
                    };
                    ModalSaveCancel.create({
                        title: M.util.get_string('create_attempt_modal_title', 'quiz_answersheets'),
                        body: message,
                        buttons: {
                            save: M.util.get_string('create_attempt_modal_button', 'quiz_answersheets'),
                        },
                        show: true,
                    }).then((modal) => {
                        modal.getRoot().on(ModalEvents.save, params, t.createAttempt);
                        modal.show();
                        // Handle hidden event.
                        modal.getRoot().on(ModalEvents.hidden, modal, t.closeModal);
                        return modal;
                    }).catch(function(err) {
                        t.exceptionHandler(err, target);
                    });
                });
            },
            closeModal: function(e) {
                var modal = e.data;
                var target = $(t.SELECTOR).closest('table').find('button');
                modal.destroy();
                t.disableButton(target, false);
            },
            createAttempt: function(e) {
                e.preventDefault();
                var data = e.data;
                var modalButtons = $(e.target).find('button');
                t.disableButton(modalButtons, true);
                var promises = Ajax.call([{
                    methodname: 'quiz_answersheets_create_attempt',
                    args: {
                        quizid: data.quizid,
                        userid: data.userid
                    }
                }]);
                // Handle promise.
                promises[0].then(function(res) {
                    var id = res.id || '';
                    window.location.href = t.updateQueryStringParameter(data.url, 'lastchanged', id);
                    return true;
                }).fail(function(err) {
                    t.exceptionHandler(err, modalButtons);
                });
            },
            disableButton: function(el, status) {
                el.prop("disabled", status);
            },
            exceptionHandler: function(ex, el) {
                Notification.exception(ex);
                t.disableButton(el, false);
            },
            updateQueryStringParameter: function(uri, key, value) {
                var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
                var separator = uri.indexOf('?') !== -1 ? "&" : "?";
                if (uri.match(re)) {
                    return uri.replace(re, '$1' + key + "=" + value + '$2');
                } else {
                    return uri + separator + key + "=" + value;
                }
            }
        };
        return t;
    });
