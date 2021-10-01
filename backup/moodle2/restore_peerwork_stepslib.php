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
 * Restore step.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore step.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_peerwork_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure.
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('peerwork', '/activity/peerwork');
        $paths[] = new restore_path_element('peerwork_criterion', '/activity/peerwork/criteria/criterion');
        $paths[] = new restore_path_element('peerwork_plugin_config',
                                            '/activity/peerwork/plugin_configs/plugin_config');

        if ($userinfo) {
            $paths[] = new restore_path_element('peerwork_peer', '/activity/peerwork/peers/peer');
            $paths[] = new restore_path_element('peerwork_justification', '/activity/peerwork/justifications/justification');
            $paths[] = new restore_path_element('peerwork_submission', '/activity/peerwork/submissions/submission');
            $paths[] = new restore_path_element('peerwork_grade', '/activity/peerwork/grades/grade');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->duedate = $this->apply_date_offset($data->duedate);
        $data->fromdate = $this->apply_date_offset($data->fromdate);

        if (!empty($data->pwgroupingid)) {
            $data->pwgroupingid = $this->get_mappingid('grouping', $data->pwgroupingid);
        } else {
            $data->pwgroupingid = 0;
        }

        $newitemid = $DB->insert_record('peerwork', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork_criterion($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerworkid = $this->get_new_parentid('peerwork');
        $data->grade = -$this->get_mappingid('scale', abs($data->grade));

        $newitemid = $DB->insert_record('peerwork_criteria', $data);
        $this->set_mapping('peerwork_criteria', $oldid, $newitemid);
    }

    /**
     * Process a plugin-config restore
     * @param stdClass $data The data in object form
     * @return void
     */
    protected function process_peerwork_plugin_config($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerwork = $this->get_new_parentid('peerwork');

        $newitemid = $DB->insert_record('peerwork_plugin_config', $data);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork_grade($data) {
        global $DB;

        $data = (object) $data;

        $data->peerworkid = $this->get_new_parentid('peerwork');
        $data->submissionid = $this->get_mappingid('peerwork_submission', $data->submissionid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerwork_grades', $data);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork_justification($data) {
        global $DB;

        $data = (object) $data;

        $data->peerworkid = $this->get_new_parentid('peerwork');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->criteriaid = $this->get_mappingid('peerwork_criteria', $data->criteriaid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);
        $data->gradefor = $this->get_mappingid('user', $data->gradefor);

        $newitemid = $DB->insert_record('peerwork_justification', $data);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork_peer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        /*
        If restoring an activity created prior to version 2020090701
        we need to copy the grade to peergrade. This is because we
        need to detect when a grade  has been overriddden.
        */
        if (!property_exists($data, 'peergrade')) {
            $data->peergrade = $data->grade;
        }

        $data->peerwork = $this->get_new_parentid('peerwork');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->criteriaid = $this->get_mappingid('peerwork_criteria', $data->criteriaid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);
        $data->gradefor = $this->get_mappingid('user', $data->gradefor);
        $data->overriddenby = $this->get_mappingid('user', $data->overriddenby);

        $newitemid = $DB->insert_record('peerwork_peers', $data);
    }

    /**
     * Process restoring element.
     *
     * @param stdClass $data The backup data.
     */
    protected function process_peerwork_submission($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $this->set_mapping('group_map', $data->groupid,  $this->get_mappingid('group', $data->groupid), true);

        $data->peerworkid = $this->get_new_parentid('peerwork');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->gradedby = $this->get_mappingid('user', $data->gradedby);
        $data->releasedby = $this->get_mappingid('user', $data->releasedby);

        $newitemid = $DB->insert_record('peerwork_submission', $data);
        $this->set_mapping('peerwork_submission', $oldid, $newitemid);
    }

    /**
     * After execute.
     */
    protected function after_execute() {
        $this->add_related_files('mod_peerwork', 'intro', null);
        $this->add_related_files('mod_peerwork', 'submission', 'group_map');
        $this->add_related_files('mod_peerwork', 'feedback_files', 'group_map');
    }
}
