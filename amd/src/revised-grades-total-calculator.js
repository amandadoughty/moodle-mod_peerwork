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
 * Revised grades total calculator.
 *
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     * Revised grades total calculator.
     *
     * @param {String} tableSelector The table selector.
     */
    function revisedGradesTotalCalculator(tableSelector) {
        var tableNode = $(tableSelector);
        var fieldNodes = tableNode.find('input[name^="grade_"]');
        var totalNode = tableNode.find('.total-revised-grade');

        var getRevisedTotal = () => {
            let total = null;
            fieldNodes.each(function(i, field) {
                var node = $(field);
                var value = parseFloat(node.val().replace(',', '.')); // Minor handling of formatted values.
                if (!value || isNaN(value)) {
                    return;
                }
                total = (total || 0) + value;
            });
            return total;
        };

        var updateTotal = () => {
            var loadedTotal = getRevisedTotal();
            if (loadedTotal !== null) {
                totalNode.text(loadedTotal.toFixed(2));
            } else {
                totalNode.text('');
            }
        };

        fieldNodes.on('change', function() {
            updateTotal();
        });

        updateTotal();
    }

    return {
        init: revisedGradesTotalCalculator
    };

});
