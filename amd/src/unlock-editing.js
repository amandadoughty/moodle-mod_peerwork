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
 * Support for unlocking editing of a student or submission.
 *
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {

    const strings = [
        {key: 'areyousure', component: 'core'},
        {key: 'yes', component: 'core'},
        {key: 'no', component: 'core'},
        {key: 'confirmunlockeditingsubmission', component: 'mod_peerwork'},
        {key: 'confirmunlockeditinggrader', component: 'mod_peerwork'},
    ];

    /**
     * Init function.
     *
     * @param {String} submissionSelector The submission selector.
     * @param {String} graderSelector The grader selector.
     */
    function init(submissionSelector, graderSelector) {

        // Preload strings.
        const stringsPromise = Str.get_strings(strings).catch(Notification.exception);

        // Find the submission selector.
        $(submissionSelector).on('click', (e) => {
            e.preventDefault();
            const node = $(e.currentTarget);
            const submissionId = node.data('submissionid');

            stringsPromise.then(([title, yesLabel, noLabel, qSubmission]) => {
                return Notification.confirm(title, qSubmission, yesLabel, noLabel, () => {
                    node.hide();
                    Ajax.call([{
                        methodname: 'mod_peerwork_unlock_submission',
                        args: {submissionid: submissionId}
                    }])[0].then(() => {
                        node.remove();
                        return;
                    }).catch((e) => {
                        node.show();
                        return Notification.exception(e);
                    });
                });
            }).catch(Notification.exception);
        });

        // Find the grader selectors.
        $(graderSelector).on('click', (e) => {
            e.preventDefault();
            const node = $(e.currentTarget);
            const peerworkId = node.data('peerworkid');
            const graderId = node.data('graderid');
            const graderName = node.data('graderfullname');

            stringsPromise.then(([title, yesLabel, noLabel]) => {
                return Notification.confirm(
                    title,
                    M.util.get_string('confirmunlockeditinggrader', 'mod_peerwork', graderName),
                    yesLabel,
                    noLabel,
                    () => {
                        const nodes = $(graderSelector).filter(`[data-graderid=${graderId}]`);
                        nodes.hide();
                        Ajax.call([{
                            methodname: 'mod_peerwork_unlock_grader',
                            args: {peerworkid: peerworkId, graderid: graderId}
                        }])[0].then(() => {
                            nodes.remove();
                            return;
                        }).catch((e) => {
                            nodes.show();
                            return Notification.exception(e);
                        });
                    }
                );
            }).catch(Notification.exception);
        });

    }

    return {
        init: init
    };

});
