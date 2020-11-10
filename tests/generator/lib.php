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
 * Peerwork data generator.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Peerwork data generator class.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerwork_generator extends testing_module_generator {

    /**
     * Create instance.
     *
     * @param stdClass $record The raw record.
     * @param array|null $options Some options.
     * @return object The instance.
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object) (array) $record;
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Create a criteria.
     *
     * @param stdClass|array $record The record.
     * @return object
     */
    public function create_criterion($record) {
        global $DB;
        $record = (object) (array) $record;

        if (empty($record->peerworkid)) {
            throw new coding_exception('Missing peerworkid');
        }

        if (empty($record->description)) {
            $record->description = 'Please use the scale to rate your peer.';
            $record->descriptionformat = FORMAT_PLAIN;
        }

        if (!empty($record->scale)) {
            $record->grade = -$record->scale->id;
            unset($record->scale);
        } else if (!empty($record->scaleid)) {
            $record->grade = -$record->scaleid;
            unset($record->scaleid);
        }

        if (empty($record->grade) || $record->grade > 0) {
            throw new coding_exception('The grade must be the negative value of a scale ID.');
        }

        $id = $DB->insert_record('peerwork_criteria', $record);
        return $DB->get_record('peerwork_criteria', ['id' => $id]);
    }

    /**
     * Create a submission.
     *
     * @param stdClass|array $record The record.
     * @return object
     */
    public function create_submission($record) {
        global $DB;
        $record = (object) (array) $record;

        if (empty($record->peerworkid)) {
            throw new coding_exception('Missing peerworkid');
        } else if (empty($record->groupid)) {
            throw new coding_exception('Missing groupid');
        }

        $id = $DB->insert_record('peerwork_submission', $record);
        return $DB->get_record('peerwork_submission', ['id' => $id]);
    }

    /**
     * Create a grade.
     *
     * @param stdClass|array $record The record.
     * @return object
     */
    public function create_grade($record) {
        global $DB;
        $record = (object) (array) $record;

        if (empty($record->peerworkid)) {
            throw new coding_exception('Missing peerworkid');
        } else if (empty($record->submissionid)) {
            throw new coding_exception('Missing submissionid');
        } else if (empty($record->userid)) {
            throw new coding_exception('Missing userid');
        }

        if (!isset($record->grade)) {
            $record->grade = 0;
        }

        $id = $DB->insert_record('peerwork_grades', $record);
        return $DB->get_record('peerwork_grades', ['id' => $id]);
    }

    /**
     * Create a peer grade.
     *
     * @param stdClass|array $record The record.
     * @return object
     */
    public function create_peer_grade($record) {
        global $DB;
        $record = (object) (array) $record;

        if (isset($record->peerworkid)) {
            $record->peerwork = $record->peerworkid;
            unset($record->peerworkid);
        }

        if (empty($record->peerwork)) {
            throw new coding_exception('Missing peerwork or peerworkid');
        } else if (empty($record->criteriaid)) {
            throw new coding_exception('Missing criteriaid');
        } else if (empty($record->groupid)) {
            throw new coding_exception('Missing groupid');
        } else if (empty($record->gradedby)) {
            throw new coding_exception('Missing gradedby');
        } else if (empty($record->gradefor)) {
            throw new coding_exception('Missing gradefor');
        }

        if (!isset($record->prelimgrade)) {
            $record->prelimgrade = 0;
        }
        if (!isset($record->grade)) {
            $record->grade = 0;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }

        $id = $DB->insert_record('peerwork_peers', $record);
        return $DB->get_record('peerwork_peers', ['id' => $id]);
    }

    /**
     * Create a peer justification.
     *
     * @param stdClass|array $record The record.
     * @return object
     */
    public function create_justification($record) {
        global $DB;
        $record = (object) (array) $record;

        if (empty($record->peerworkid)) {
            throw new coding_exception('Missing peerworkid');
        } else if (empty($record->groupid)) {
            throw new coding_exception('Missing groupid');
        } else if (empty($record->gradedby)) {
            throw new coding_exception('Missing gradedby');
        } else if (empty($record->gradefor)) {
            throw new coding_exception('Missing gradefor');
        }

        if (!isset($record->justification)) {
            $record->justification = 'They actively participated in the group';
        }

        $id = $DB->insert_record('peerwork_justification', $record);
        return $DB->get_record('peerwork_justification', ['id' => $id]);
    }

}
