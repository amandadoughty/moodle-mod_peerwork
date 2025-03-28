<?php
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
 * Contains the class for fetching the important dates in mod_peerwork for a given module instance and a user.
 *
 * @package   mod_peerwork
 * @copyright 2022 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_peerwork;

use core\activity_dates;

/**
 * Class for fetching the important dates in mod_peerwork for a given module instance and a user.
 *
 * @copyright 2022 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dates extends activity_dates {

    /**
     * Returns a list of important dates in mod_peerwork
     *
     * @return array
     */
    protected function get_dates(): array {
        $timeopen = $this->cm->customdata['fromdate'] ?? null;
        $timeclose = $this->cm->customdata['duedate'] ?? null;
        $now = time();
        $dates = [];

        if ($timeopen) {
            $openlabelid = $timeopen > $now ? 'activitydate:opens' : 'activitydate:opened';
            $dates[] = [
                'label' => get_string($openlabelid, 'mod_peerwork'),
                'timestamp' => (int)$timeopen,
            ];
        }

        if ($timeclose) {
            $closelabelid = $timeclose > $now ? 'activitydate:closes' : 'activitydate:closed';
            $dates[] = [
                'label' => get_string($closelabelid, 'mod_peerwork'),
                'timestamp' => (int)$timeclose,
            ];
        }

        return $dates;
    }
}
