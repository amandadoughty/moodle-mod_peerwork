<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/.
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
 * Peerwork criteria.
 *
 * @package    mod_peerwork
 * @copyright  2018 Coventry University
 * @author     Kevin Moore <ac4581@coventry.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Peerwork criteria.
 *
 * @package    mod_peerwork
 * @copyright  2018 Coventry University
 * @author     Kevin Moore <ac4581@coventry.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerwork_criteria  {

    /** @var string The table name. */
    protected static $tablename = 'peerwork_criteria';

    /** @var int The peerwork ID, or 0. */
    protected $id;

    /**
     * Constructor.
     *
     * @param int|null $peerworkid The peerwork ID, when we have one.
     */
    public function __construct($peerworkid) {
        $this->id = (int) $peerworkid;
    }

    /**
     * Get the criteria created for this peerassesment, making sure we have the array in field=sort order.
     * @return DB records from  peerwork_criteria, one record per criteria on this assessment.
     */
    public function get_criteria() {
        global $DB;
        $records = $DB->get_records(self::$tablename, ['peerworkid' => $this->id], 'sortorder, id');
        return $records;
    }

    /**
     * Settings
     * Called automatically from lib.php::peerwork_update_instance() and peerwork_add_instance() when the settings form is saved.
     * The main settings will already be saved, this intercepts and saves the criteria into self::$tablename
     *
     * @param stdClass $peerwork
     * @return boolean
     */
    public function update_instance(stdClass $peerwork) {
        global $DB;

        // The form is passing values through the key assessmentcriteria.
        $criteria = !empty($peerwork->assessmentcriteria) ? $peerwork->assessmentcriteria : [];
        $existing = array_values($this->get_criteria()); // Drop the keys.

        // Update, or delete the criteria that exist.
        foreach ($existing as $i => $crit) {
            $newdata = isset($criteria[$i]) ? $criteria[$i] : null;
            if (!$newdata) {
                $DB->delete_records(self::$tablename, ['id' => $crit->id]);
                $DB->delete_records('peerwork_peers', ['criteriaid' => $crit->id]);
                continue;
            }

            $crit->description = $newdata->description;
            $crit->descriptionformat = $newdata->descriptionformat;
            $crit->grade = $newdata->grade;
            $crit->weight = $newdata->weight;
            $crit->sortorder = $newdata->sortorder;
            $DB->update_record(self::$tablename, $crit);
        }

        // Create new criteria.
        $remaining = array_slice($criteria, count($existing));
        foreach ($remaining as $crit) {
            $crit->peerworkid = $this->id;
            $DB->insert_record(self::$tablename, $crit);
        }

        return true;
    }

}
