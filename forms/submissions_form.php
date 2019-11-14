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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_peerwork
 * @copyright 2013 LEARNING TECHNOLOGY SERVICES
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../classes/peerwork_criteria.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/grade/grade_scale.php');

/**
 * This form is the layout for a student grading their peers. Contains a file submission area where files can be submitted on behalf of the group
 * and space to enter marks and feedback to peers in your group.
 *
 * Each criteria is presented and for each one a space for grading peers is provided.
 *
 * Data is provided into $this->_customdata sent in the CTOR new mod_peerwork_submissions_form(...) calls in submissions.php and view.php
 *
 */
class mod_peerwork_submissions_form extends moodleform {

    // Public static $fileoptions = array('mainfile' => '', 'subdirs' => 0, 'maxbytes' => -1,
    // 'maxfiles' => 1, 'accepted_types' => '*', 'return_types' => null);

    // Define the form.
    protected function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $userid = $USER->id;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('hidden', 'files');
        $mform->setType('files', PARAM_INT);
        if (isset($this->_customdata['files'])) {
            $mform->setDefault('files', $this->_customdata['files']);
        }

        if ($this->_customdata['fileupload']) {
            $mform->addElement('header', 'peerssubmission', get_string('assignment', 'peerwork'));
            $mform->addElement('filemanager', 'submission', get_string('assignment', 'peerwork'),
                null, $this->_customdata['fileoptions']);
            $mform->addHelpButton('submission', 'submission', 'peerwork');
        }

        // Create a section with all the criteria. Creates a <fieldset class="clearfix collapsible" id="id_peerstobegraded">
        $mform->addElement('header', 'peerstobegraded', get_string('peers', 'peerwork')); // "Grade your peers"
        $peers = $this->_customdata['peers'];

        $peerassess = get_coursemodule_from_id('peerwork', $this->_customdata['id']);
        $pac = new peerwork_criteria($peerassess ->instance);
        $scales = grade_scale::fetch_all_global(); // HARDCODE this locks us to using scales only.

        foreach ($pac->getCriteria() as $criteria) {

            // Get the scale.
            $scaleid = abs($criteria->grade);
            $scale = isset($scales[$scaleid]) ? $scales[$scaleid] : null;
            if (!$scale) {
                throw new moodle_exception('Unknown scale ' . $scaleid);
            }
            $scaleitems = $scale->load_items();

            // Criteria description.
            $field = 'criteriadescription_' . $criteria->id;
            $mform->addElement('html', '<div class="mod_peerwork_criteriaheader">'. $criteria->description . '</div>');

            // Header using the items in the scale, use the same label and span as the radio buttons to match radio button layout.
            $scalenumbers = array();
            foreach ($scaleitems as $k => $v) {
                //$scalenumbers[] = $mform->createElement('html', "<div class=\"mod_peerwork_scaleheader\">$v</div>");
                $scalenumbers[] = $mform->createElement('html', '<label class="form-check-inline form-check-label fitem">'.$v.'</label><span style="display: none;"></span>');
            }
            $mform->addGroup($scalenumbers, "mod_peerwork_scaleheader", '', array(''), false);

            // Create array of radio buttons for this criteria and for each peer too allow grading of peers.
            foreach ($peers as $peer) {
                $unique = $criteria->id . '[' . $peer->id . ']';
                $radioarray = [];
                $field = 'grade_idx_' . $unique;
                foreach($scaleitems as $k => $v) {
                    $radioarray[] = $mform->createElement('radio', $field, '', '', $k, '');
                }
                $mform->addGroup($radioarray, 'grade_idx_' . $unique, fullname($peer), '', false);
            }
        }
        $this->add_action_buttons(false);
    }


    /**
     *
     * Called automatically.
     * Doesnt include the submissions file $this->_customdata['fileupload'])?
     * Collect the criteria from the database and populate the form fields with existing data from database.
     *
     * @param unknown $data
     * @return unknown
     */
    public function set_data($data) {
        global $DB, $USER;

        // Convert the stored module id into the peerwork activity.
        $peerassess = get_coursemodule_from_id('peerwork', $this->_customdata['id']);
        $peerworkid = $peerassess->instance;

        // Get information about each criteria and grades awarded to peers and add to the form data
        $pac = new peerwork_criteria($peerworkid);

        foreach ($pac->getCriteria() as $id => $record) {

            // Now get all the grades and feedback for this criteria that this user has already awarded to their peers.
            // Transfer into the $data so it populates the UI
            $mygrades = $DB->get_records('peerwork_peers', [
                'peerwork' => $record->peerworkid,
                'criteriaid' => $record->id,
                'gradedby' => $USER->id,
            ], '', 'id,gradefor,feedback,grade');

            foreach ($mygrades as $grade) {
                $data->{'grade_idx_' . $record->id}[$grade->gradefor] = $grade->grade;
            }
        }
        return parent::set_data($data);
    }

}
