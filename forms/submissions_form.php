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
 * @package    mod
 * @subpackage peerassessment
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once( __DIR__ . '/../classes/peerassessment_criteria.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/grade/grade_scale.php');

/**
 * This form is the layout for a student grading their peers. Contains a file submission area where files can be submitted on behalf of the group
 * and space to enter marks and feedback to peers in your group.
 * 
 * Each criteria is presented and for each one a space for grading peers is provided. 
 * 
 * Data is provided into $this->_customdata sent in the CTOR  new mod_peerassessment_submissions_form(...) calls in submissions.php and view.php
 *
 */
class mod_peerassessment_submissions_form extends moodleform
{

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
            $mform->addElement('header', 'peerssubmission', get_string('assignment', 'peerassessment'));
            $mform->addElement('filemanager', 'submission', get_string('assignment', 'peerassessment'),
                null, $this->_customdata['fileoptions']);
            $mform->addHelpButton('submission', 'submission', 'peerassessment');
            // $mform->disabledIf('submission', 'value1', 'eq|noteq', 'value2');
            // $mform->addRule('submission', $strrequired, 'required', null, 'client');
            // $mform->setAdvanced('submission');
        }

        // Create a section with all the criteria. Creates a <fieldset class="clearfix collapsible" id="id_peerstobegraded">
        $mform->addElement('header', 'peerstobegraded', get_string('peers', 'peerassessment')); // "Grade your peers"
        $peers = $this->_customdata['peers'];
        
        $peerassess = get_coursemodule_from_id('peerassessment', $this->_customdata['id']);
        $pac = new peerassessment_criteria( $peerassess ->instance );
 
        $scales = grade_scale::fetch_all_global(); // HARDCODE this locks us to using scales only.
        
        foreach( $pac ->getCriteria() as $criteria ) {

            // Criteria description
        	$field = 'criteriadescription_'.$criteria->sort;
        	$mform->addElement('html', '<div class="mod_peerassessment_criteriaheader">'. $criteria->description . '</div>' );
                    	
        	error_log("scale used for criteria ". $criteria->sort . " = " .  $criteria ->grade );
        	$scale = $scales[ abs($criteria ->grade) ];
            $scaleitems = $scale->load_items();
            error_log("scale items are " . print_r($scaleitems,true) ); 
            
            // Header using the items in the scale, use the same label and span as the radio buttons to match radio button layout.
            $scalenumbers = array();
            foreach( $scaleitems as $k => $v ) {
            	//$scalenumbers[] = $mform->createElement('html', "<div class=\"mod_peerassessment_scaleheader\">$v</div>");
            	$scalenumbers[] = $mform->createElement('html', '<label class="form-check-inline form-check-label fitem">'.$v.'</label><span style="display: none;"></span>' );    
            }
            $mform->addGroup($scalenumbers, "mod_peerassessment_scaleheader", '', array(''), false );

			// Create array of radio buttons for this criteria and for each peer too allow grading of peers.                      
            foreach ($peers as $peer) {
            	$unique = $criteria->sort .'[' . $peer->id . ']';	// create a unique string for this group eg 0[23]

                $radioarray=array();
                $attributes = '';
                $field = 'grade_idx_'. $unique;  		// grade_idx_0[28] all radios in group need same name that say which criteria and which peer it refers to.
                foreach( $scaleitems as $k => $v ) {   
                	$radioarray[] = $mform->createElement('radio', $field, '', '', $k, $attributes);             	
                }
                // $radioarray[] = $mform->createElement('radio', 'grade_idx_99', '', '', 99, ['checked']);      // hidden default, means nothing selected initially.
                /** returned from form as:-
                 *    	[grade_idx_0] => Array( [28] => 0, [23] => 4, [13] => 3 )
                 *		[grade_idx_1] => Array( [28] => 0, [23] => 0, [13] => 1 )
                 */
                $mform->addGroup($radioarray, 'grade_idx_'.$unique, fullname($peer), array(''), false); 
                //$mform->setDefault('grade_idx_'.$unique, 99); 	// not working
            }

        	$field = 'criteriadescription_'.$criteria->sort;
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
		
		// error_log("set_data _customdata = " . print_r( $this->_customdata, true ) );
		// error_log("set_data data = " . print_r( $data, true ) );
		
		// Convert the stored module id into the peerassessment activity
		// Collect the criteria data for this peerassessment and add into $data.
		$peerassess = get_coursemodule_from_id ( 'peerassessment', $this->_customdata ['id'] );
		
		// Get information about each criteria and grades awarded to peers and add to the form data
		$pac = new peerassessment_criteria ( $peerassess->instance );
		
		foreach ( $pac->getCriteria () as $id => $record ) {
			
			// Now get all the grades and feedback for this criteria that this user has already awarded to their peers.
			// Transfer into the $data so it populates the UI
			$mygrades = $DB->get_records ( 'peerassessment_peers', array (
					'peerassessment' => $record->peerassessmentid,
					'gradedby' => $USER->id,
					'sort' => $record->sort 
			), '', 'id,sort,gradefor,feedback,grade' );
			
// 			if (count ( $mygrades ) == 0) {
// 				error_log ( "set_data setting default on  = " . $record->sort );
// 				$data->{'grade_idx_' . $grade->sort } [13] = 4;
// 				$data->{'grade_idx_' . $grade->sort } [28] = 4;
// 			}
			foreach ( $mygrades as $grade ) {
				error_log ( "set_data grade = " . print_r ( $grade, true ) );
				$data->{'grade_idx_' . $grade->sort } [$grade->gradefor] = $grade->grade;
			}
		}
		return parent::set_data ( $data );
    }

    
    
// TODO commented out temporarily    
//     public function validation($data, $files) {
//         $errors = parent::validation($data, $files);

//         if (isset($data['grade'])) {
//             foreach ($data['grade'] as $k => $v) {
//                 if ($v < 0 || $v > 5) {
//                     $errors["grade"][$v] = 'Peer grade should be between 0 and 5';
//                 }
//             }
//         }

//         return $errors;
//     }
  
}