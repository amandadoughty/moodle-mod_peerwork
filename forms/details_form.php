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

/**
 * Creates UI elements for the tutor to enter an overall grade to a submission.
 * Called from and data provided by details.php
 */
class mod_peerassessment_details_form extends moodleform
{
    public static $fileoptions = array('mainfile' => '', 'subdirs' => 1, 'maxbytes' => -1, 'maxfiles' => -1,
        'accepted_types' => '*', 'return_types' => null);

    // Define the form.
    protected function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $userid = $USER->id;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);
        
        ////////////////////////////////////////////////////////////////////////////////////////
        $mform->addElement('header', 'mod_peerassessment_details', 'Details' );  
        $mform->addElement('static', 'groupname', 'Group' ); // Filled from $data['groupname']
        $mform->addElement('static', 'status', 'Status' );
        
        ////////////////////////////////////////////////////////////////////////////////////////
        $mform->addElement('header', 'mod_peerassessment_peers', 'Peer submission and grades' );  
        $mform->addElement('static', 'submission', get_string('submission', 'peerassessment'));
        $mform->addHelpButton('submission', 'submission', 'peerassessment');
        
        $mform->addElement('static', 'peergradesawarded', "");	// This gets replaced with a table of grades peers have awarded.
        
        
        ////////////////////////////////////////////////////////////////////////////////////////
        $mform->addElement('header', 'mod_peerassessment_grading', 'Tutor grading' );
        $mform->addElement('text', 'grade', "Group grade out of 100", array('maxlength' => 15, 'size' => 10));
        $mform->setType('grade', PARAM_INT);
        // $mform->setDefault('grade', 'defult string value for the textarea');
        // $mform->addHelpButton('grade', 'langkey_help', 'peerassessment');
        // $mform->disabledIf('grade', 'value1', 'eq|noteq', 'value2');
        // $mform->addRule('grade', $strrequired, 'required', null, 'client');
        // $mform->setAdvanced('grade');
        
        $mform->addElement('static', 'finalgrades', "Calculated grades");	// becomes a HTML table
        //$mform->addHelpButton('finalgrades', 'finalgrades', 'peerassessment');


        $editoroptions = array();
        $mform->addElement('editor', 'feedback', get_string('feedback', 'peerassessment'), '', $editoroptions);
        $mform->setType('feedback', PARAM_CLEANHTML);
        // $mform->addHelpButton('feedback', 'langkey_help', 'peerassessment');
        // $mform->disabledIf('feedback', 'value1', 'eq|noteq', 'value2');
        // $mform->addRule('feedback', $strrequired, 'required', null, 'client');
        // $mform->setAdvanced('feedback');

        $mform->addElement('filemanager', 'feedback_files', get_string('feedbackfiles', 'peerassessment'),
            null, $this->_customdata['fileoptions']);
        // $mform->addHelpButton('feedback_files', 'langkey_help', 'peerassessment');
        // $mform->disabledIf('feedback_files', 'value1', 'eq|noteq', 'value2');
        // $mform->addRule('feedback_files', $strrequired, 'required', null, 'client');
        // $mform->setAdvanced('feedback_files');

        
        $this->add_action_buttons();
    }

    /**
     * Called from details.php to populate the form from existing data.
     */
    public function set_data($data) {
    	global $OUTPUT;
 
    	error_log("set_data " . print_r($data['finalgrades'] , true ) );
    	
    	if( array_key_exists('finalgrades', $data) ) {
    		
    		$t = new html_table();
    		$t->attributes['class'] = 'userenrolment';
    		$t->id = 'mod-peerassessment-summary-table';
    		$t->head = array(	'Name', 
    							get_string('contibutionscore', 'peerassessment'). $OUTPUT->help_icon('contibutionscore', 'peerassessment'),
    							'Calculated grade', 
    							"Revised grade");
    		
    		foreach ($data['finalgrades'] as $member) {
    			$row = new html_table_row();
    			// TODO also add grade from gradebook in case it's overwritten ??
    			
    			$default = $member['calcgrade'];

    			$row->cells[] = $member['fullname']; 
    			$row->cells[] = $member['contribution'];
    			$row->cells[] = $member['calcgrade'];
    			$row->cells[] = $this->_form ->createElement('text', 'grade_'.$member['memberid'], '', array('maxlength' => 15, 'size' => 10)) ->toHtml();
    			
    			// not working $this->_form ->setDefault('grade_'.$member['memberid'], $default );
    			
    			$t->data[] = $row;
    		}
    		$data['finalgrades'] = html_writer::table($t);
    		
    	} else {    		
    		$data['finalgrades'] = ""; 
    	}
    	

    	return parent::set_data($data);
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['grade'] < 0 || $data['grade'] > 100) {
            $errors['grade'] = 'Grade should be between 0 and 100';
        }
        return $errors;
    }
}
