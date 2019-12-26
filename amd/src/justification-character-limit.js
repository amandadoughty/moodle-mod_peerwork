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
 * Justification character limit.
 *
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification'], function($, Str, Notification) {

    const WRAPPER_CLASS = 'justification-character-limit-wrapper';
    const WRAPPER_SELECTOR = '.justification-character-limit-wrapper';
    const TEXT_CLASS = 'justification-character-limit';
    const TEXT_SELECTOR = '.justification-character-limit';

    /**
     * Update character limit.
     *
     * @param {Node} textareaNode The text area.
     * @param {Number} limit The limit.
     */
    function updateCharacterLimit(textareaNode, limit) {
        const node = $(textareaNode);
        const length = [...node.val()].length; // Character-friendly length.
        const remaining = limit - length;
        const sibling = node.siblings(WRAPPER_SELECTOR);
        const textNode = sibling.find(TEXT_SELECTOR);

        textNode.text(M.util.get_string('charactersremaining', 'mod_peerwork', remaining));
        if (remaining < 0) {
            textNode.addClass('text-danger');
        } else {
            textNode.removeClass('text-danger');
        }
    }

    /**
     * Justification character limit.
     *
     * @param {String} selector The textarea selector.
     * @param {Number} limit The limit.
     */
    function init(selector, limit) {
        if (!limit) {
            return;
        }

        Str.get_string('charactersremaining', 'mod_peerwork').then(() => {

            const textareaNodes = $(selector);

            // Append the place where we'll compute the character limit.
            textareaNodes.each(function(i, n) {
                var node = $(n);
                node.after(`<div class="${WRAPPER_CLASS}"><small class="${TEXT_CLASS}"></small></div>`);
                updateCharacterLimit(n, limit);
            });

            // Add the change listener.
            textareaNodes.on('keyup', function(e) {
                updateCharacterLimit(e.target, limit);
            });

            return null;

        }).catch(Notification.exception);
    }

    return {
        init: init
    };

});
