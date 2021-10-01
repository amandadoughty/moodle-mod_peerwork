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
 * AJAX helper for the inline editing a value.
 *
 * This script is automatically included from template core/inplace_editable
 * It registers a click-listener on [data-inplaceeditablelink] link (the "inplace edit" icon),
 * then replaces the displayed value with an input field. On "Enter" it sends a request
 * to web service core_update_inplace_editable, which invokes the specified callback.
 * Any exception thrown by the web service (or callback) is displayed as an error popup.
 *
 * @module     core/inplace_editable
 * @package    core
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery',
        'core/ajax',
        'core/templates',
        'core/notification',
        'core/str',
        'core/config',
        'core/url',
        'core/form-autocomplete',
        'core/pending',
    ],
function($, ajax, templates, notification, str, cfg, url, autocomplete, Pending) {

    var itemid;
    var warningshown = false;

    /**
     * Enables inplace editing.
     */
    var handleInplaceEdit = function() {
        warningshown = true;

        document.querySelectorAll('.inplace-grading.cell').forEach(function(td) {
            td.removeEventListener('click', beforeDueDate, true);
            td.removeEventListener('keypress', beforeDueDate, true);
        });

        document.querySelector("[data-itemid='" + itemid + "'] [data-inplaceeditablelink]").click();
    };

    /**
     * Prevents inplace editing.
     */
    var stopInplaceEdit = function() {
        return true;
    };    

    /**
     * Displays the show confirmation to edit grade.
     *
     * @param {String} message confirmation message
     * @param {function} onconfirm function to execute on confirm
     * @param {function} onreject function to execute on reject
     */
    var confirmHandleInplaceEdit = function(message, onconfirm, onreject, e) {
        str.get_strings([
            {key: 'confirmeditgrade', component: 'mod_peerwork'},
            {key: 'yes', component: 'core'},
            {key: 'no', component: 'mod_peerwork'}
        ]).done(function(s) {
                notification.confirm(s[0], message, s[1], s[2], onconfirm, onreject);                
            }
        );
    };

    /**
     * The edit link was clicked in the group list.
     *
     * @param {Event} e click/keypress event
     */
    var beforeDueDate = function(e) {
        if (e.type === 'keypress' && e.keyCode !== 13) {
            return;
        }

        e.stopImmediatePropagation();
        e.preventDefault();
        itemid = e.target.closest('.inplaceeditable').dataset.itemid;        

        str.get_strings([
            {key: 'confirmeditgradetxt', component: 'mod_peerwork'}
        ]).done(function(s) {
            confirmHandleInplaceEdit(s[0], handleInplaceEdit, stopInplaceEdit, e);    

        });
    };

    document.querySelectorAll('.inplace-grading.cell').forEach(function(td) {
        if (!warningshown) {
            td.addEventListener('click', beforeDueDate, true);
            td.addEventListener('keypress', beforeDueDate, true);
        }
        return false;
    });

    return {};
});
