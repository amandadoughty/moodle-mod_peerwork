<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class restore_peerwork_activity_structure_step extends restore_activity_structure_step
{

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('peerwork', '/activity/peerwork');
        if ($userinfo) {
            $paths[] = new restore_path_element('peerwork_peer', '/activity/peerwork/peers/peer');
            $paths[] = new restore_path_element('peerwork_submission', '/activity/peerwork/submissions/submission');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_peerwork($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // $data->timecreated = $this->apply_date_offset($data->timecreated);
        // $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->duedate = $this->apply_date_offset($data->duedate);
        $data->fromdate = $this->apply_date_offset($data->fromdate);

        if (!empty($data->submissiongroupingid)) {
            $data->submissiongroupingid = $this->get_mappingid('grouping',
            $data->submissiongroupingid);
        } else {
            $data->submissiongroupingid = 0;
        }

        // insert the peerwork record
        $newitemid = $DB->insert_record('peerwork', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_peerwork_peer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerwork = $this->get_new_parentid('peerwork');
        // $data->timecreated = $this->apply_date_offset($data->timecreated);

        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);
        $data->gradefor = $this->get_mappingid('user', $data->gradefor);

        $newitemid = $DB->insert_record('peerwork_peers', $data);
        // $this->set_mapping('peerwork_peers', $oldid, $newitemid);
    }

    protected function process_peerwork_submission($data) {
        global $DB;

        $data = (object)$data;

        $this->set_mapping('group_map', $data->groupid,  $this->get_mappingid('group', $data->groupid), true);

        $data->assignment = $this->get_new_parentid('peerwork');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);

        // $data->optionid = $this->get_mappingid('peerwork_option', $data->optionid);
        // $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('peerwork_submission', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function after_execute() {
        // Add peerwork related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_peerwork', 'intro', null);

        // Add post related files, matching by itemname = 'forum_post'.
        $this->add_related_files('mod_peerwork', 'submission', 'group_map');
        $this->add_related_files('mod_peerwork', 'feedback_files', 'group_map');
    }
}