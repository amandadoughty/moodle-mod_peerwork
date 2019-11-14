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

    /**
     * Definition.
     *
     * @return void
     */
    protected function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $userid = $USER->id;
        $peers = $this->_customdata['peers'];
        $peerworkid = $this->_customdata['peerworkid'];
        $strrequired = get_string('required');

        // The CM id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $this->_customdata['id']);

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

        // Create a hidden field for each possible rating, this is so that we can construct the radio
        // button ourselves while still use the form validation.
        $pac = new peerwork_criteria($peerworkid);
        foreach ($pac->getCriteria() as $criteria) {
            foreach ($peers as $peer) {
                $uniqueid = 'grade_idx_' . $criteria->id . '[' . $peer->id . ']';
                $mform->addElement('hidden', $uniqueid, 0);
                $mform->setType($uniqueid, PARAM_INT);
                $mform->setDefault($uniqueid, 0);
            }
        }
    }

    /**
     * Definition after data.
     *
     * We define the criteria here in order to be able to get the current rated values and
     * apply them ourselves to the radio buttons.
     *
     * @return void
     */
    public function definition_after_data() {
        global $USER;
        $mform = $this->_form;
        $peerworkid = $this->_customdata['peerworkid'];

        // Create a section with all the criteria.
        $mform->addElement('header', 'peerstobegraded', get_string('peers', 'peerwork'));
        $peers = $this->_customdata['peers'];

        $scales = grade_scale::fetch_all_global();
        $pac = new peerwork_criteria($peerworkid);

        foreach ($pac->getCriteria() as $criteria) {

            // Get the scale.
            $scaleid = abs($criteria->grade);
            $scale = isset($scales[$scaleid]) ? $scales[$scaleid] : null;
            if (!$scale) {
                throw new moodle_exception('Unknown scale ' . $scaleid);
            }
            $scaleitems = $scale->load_items();

            $html = '';
            $html .= html_writer::start_div('mod_peerwork_criteria');
            $html .= html_writer::div($criteria->description, 'mod_peerwork_criteriaheader');

            $html .= html_writer::start_div('mod_peerwork_criteriarating');
            $html .= html_writer::div(implode('', array_map(function($item) {
                return html_writer::div($item);
            }, $scaleitems)), 'mod_peerwork_scaleheaders');

            $html .= html_writer::div(implode('', array_map(function($peer) use ($criteria, $scaleitems, $mform, $USER) {
                $uniqueid = 'grade_idx_' . $criteria->id . '[' . $peer->id . ']';
                $currentvalue = $mform->exportValue($uniqueid);
                $fullname = fullname($peer);
                $namedisplay = $peer->id == $USER->id ? get_string('peernameisyou', 'mod_peerwork', $fullname) : $fullname;

                $o = '';
                $o .= html_writer::start_div('mod_peerwork_peer');
                $o .= html_writer::div($namedisplay, 'mod_peerwork_peername');
                $o .= html_writer::start_div('mod_peerwork_ratings');
                $o .= implode('', array_map(function($item, $key) use ($peer, $uniqueid, $currentvalue, $fullname) {
                    $label = get_string('ratingnforuser', 'mod_peerwork', [
                        'rating' => $item,
                        'user' => $fullname,
                    ]);
                    $attrs = [
                        'type' => 'radio',
                        'name' => $uniqueid,
                        'value' => $key,
                        'title' => $label
                    ];
                    if ($currentvalue == $key) {
                        $attrs['checked'] = 'checked';
                    }
                    return html_writer::div(
                        html_writer::tag('label', html_writer::empty_tag('input', $attrs) .
                        html_writer::tag('span', $label, ['class' => 'sr-only'])
                    ), 'mod_peerwork_rating');
                }, $scaleitems, array_keys($scaleitems)));
                $o .= html_writer::end_div();
                $o .= html_writer::end_div();
                return $o;
            }, $peers)), 'mod_peerwork_peers');

            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
            $mform->addElement('html', $html);
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
                $data->{'grade_idx_' . $record->id . '[' . $grade->gradefor . ']'} = $grade->grade;
            }
        }

        return parent::set_data($data);
    }

}
