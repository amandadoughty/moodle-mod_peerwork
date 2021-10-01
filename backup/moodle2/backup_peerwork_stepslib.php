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
 * Backup step.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup step.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_peerwork_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define structure.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        $includegroups = $this->get_setting_value('groups');
        $includeuserinfo = $userinfo && $includegroups; // We need groups to restore the data properly.

        // Define each element separated.
        $peerwork = new backup_nested_element('peerwork', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated',
            'timemodified', 'selfgrading', 'duedate', 'maxfiles',
            'fromdate', 'allowlatesubmissions', 'peergradesvisibility',
            'justification', 'justificationtype', 'justificationmaxlength',
            'paweighting', 'noncompletionpenalty', 'completiongradedpeers', 'displaypeergradestotals',
            'lockediting', 'calculator', 'pwgroupingid'));

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', ['id'], [
            'description', 'descriptionformat', 'grade', 'weight', 'sortorder']);

        $peers = new backup_nested_element('peers');
        $peer = new backup_nested_element('peer', array('id'), array(
            'criteriaid', 'grade', 'groupid', 'gradedby', 'gradefor',
            'feedback', 'locked', 'timecreated', 'timemodified', 'peergrade', 'overriddenby', 'comments', 'timeoverridden'));

        $justifications = new backup_nested_element('justifications');
        $justification = new backup_nested_element('justification', ['id'], [
            'groupid', 'gradedby', 'gradefor', 'criteriaid', 'justification']);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', array('id'), array(
            'userid', 'timecreated', 'timemodified', 'groupid',
            'grade', 'feedbacktext', 'feedbackformat', 'timegraded',
            'gradedby', 'released', 'releasedby', 'releasednotified', 'paweighting', 'locked'));

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'submissionid', 'userid', 'score', 'prelimgrade', 'grade', 'revisedgrade'
        ]);

        $pluginconfigs = new backup_nested_element('plugin_configs');

        $pluginconfig = new backup_nested_element('plugin_config', array('id'),
                                                   array('plugin',
                                                         'subtype',
                                                         'name',
                                                         'value'));

        // Build the tree.
        $peerwork->add_child($criteria);
        $criteria->add_child($criterion);

        $peerwork->add_child($peers);
        $peers->add_child($peer);

        $peerwork->add_child($justifications);
        $justifications->add_child($justification);

        $peerwork->add_child($submissions);
        $submissions->add_child($submission);

        $peerwork->add_child($grades);
        $grades->add_child($grade);

        $peerwork->add_child($pluginconfigs);
        $pluginconfigs->add_child($pluginconfig);

        // Define sources.
        $peerwork->set_source_table('peerwork', array('id' => backup::VAR_ACTIVITYID));
        $criterion->set_source_table('peerwork_criteria', ['peerworkid' => backup::VAR_PARENTID]);
        $pluginconfig->set_source_table('peerwork_plugin_config',
                                        array('peerwork' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($includeuserinfo) {
            $peer->set_source_table('peerwork_peers', ['peerwork' => backup::VAR_PARENTID]);
            $justification->set_source_table('peerwork_justification', ['peerworkid' => backup::VAR_PARENTID]);
            $submission->set_source_table('peerwork_submission', array('peerworkid' => '../../id'));
            $grade->set_source_table('peerwork_grades', ['peerworkid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.

        $peerwork->annotate_ids('grouping', 'pwgroupingid');

        $peer->annotate_ids('user', 'gradedby');
        $peer->annotate_ids('user', 'gradefor');
        $peer->annotate_ids('user', 'overriddenby');
        $peer->annotate_ids('group', 'groupid');

        $criterion->annotate_ids('scale', 'grade');

        $justification->annotate_ids('user', 'gradedby');
        $justification->annotate_ids('user', 'gradefor');
        $justification->annotate_ids('group', 'groupid');
        $justification->annotate_ids('criteria', 'criteriaid');

        $submission->annotate_ids('user', 'userid');
        $submission->annotate_ids('user', 'gradedby');
        $submission->annotate_ids('user', 'releasedby');
        $submission->annotate_ids('group', 'groupid');

        $grade->annotate_ids('user', 'userid');

        // Define file annotations.
        $peerwork->annotate_files('mod_peerwork', 'intro', null);
        $submission->annotate_files('mod_peerwork', 'submission', 'groupid');
        $submission->annotate_files('mod_peerwork', 'feedback_files', 'groupid');

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($peerwork);

    }
}
