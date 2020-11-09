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
 * Updated calculator settings.
 *
 * @copyright  2019 Coventry University
 * @author     Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    /**
     * Update calculator settings.
     *
     * @param {int} formid The form id.
     */
    function calculatorChooser(formid) {
        if (formid) {
            var updatebut = $('#' + formid + ' #id_updatecalculator');
            var formatselect = $('#' + formid + ' #id_calculator');
            var ancestor = updatebut.closest('fieldset');
            var action = $('form.mform').attr('action');

            if (updatebut && formatselect) {
                updatebut.css('display', 'none');
                formatselect.on('change', function() {
                    $('form.mform').attr('action', action + '#' + ancestor.attr('id'));
                    updatebut.trigger('click');
                });
            }
        }
    }

    return {
        init: calculatorChooser
    };

});