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
 * Ask student to confirm that they are aware that editing will be locked.
 *
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification'], function($, Str, Notification) {

    const strings = [
        {key: 'areyousure', component: 'core'},
        {key: 'confirmlockeditingaware', component: 'mod_peerwork'},
        {key: 'yes', component: 'core'},
        {key: 'no', component: 'core'},
    ];

    /**
     * Init function.
     *
     * @param {String} selector The form selector.
     */
    function init(selector) {

        // Preload strings.
        const stringsPromise = Str.get_strings(strings).catch(Notification.exception);

        // Listen to form submissions.
        $(selector).on('submit', (e) => {
            if (this._confirmed) {
                return;
            }

            e.preventDefault();
            const form = $(e.target);

            stringsPromise.then(([title, question, yesLabel, noLabel]) => {
                return Notification.confirm(title, question, yesLabel, noLabel, () => {
                    this._confirmed = true;
                    form.submit();
                });
            }).catch(Notification.exception);

        });
    }

    return {
        init: init
    };

});
