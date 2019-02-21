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

/**
 * This form is the layout for a student grading their peers. Contains a file submission area where files can be submitted on behalf of the group
 * and space to enter marks and feedback to peers in your group.
 * 
 * Each criteria is presented and for each one space for grading peers is provided. Do this with a callback to make the grading elements onto this form
 * (dependency injection??) That way this class knows all about peers and submissions,  peerassessment_criteria knows about criteria
 *
 */
class mod_peerassessment_add_submission_form extends moodleform
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
            $mform->addElement('header', 'peers', get_string('assignment', 'peerassessment'));
            $mform->addElement('filemanager', 'submission', get_string('assignment', 'peerassessment'),
                null, $this->_customdata['fileoptions']);
            $mform->addHelpButton('submission', 'submission', 'peerassessment');
            // $mform->disabledIf('submission', 'value1', 'eq|noteq', 'value2');
            // $mform->addRule('submission', $strrequired, 'required', null, 'client');
            // $mform->setAdvanced('submission');
        }

        $mform->addElement('header', 'peers', get_string('peers', 'peerassessment')); // "Grade your peers"
        $default = 0;
        $peers = $this->_customdata['peers'];
        
        // This outputs each criteria and uses the callback to ourself to add the peers' assessments to each one.
        $peerassess = get_coursemodule_from_id('peerassessment', $this->_customdata['id']);
        $pac = new peerassessment_criteria( $peerassess ->instance );
        $pac ->add_submission_form_definition($mform, array($this, 'callback') );
 
        $this->add_action_buttons(false);
    }
    
    /**
     * This callback function is called by peerassessment_criteria::add_submission_form_definition() to add in multiple UI elements to allow a user
     * to assess their peers. 
     *
     * @param int $criteriasort the peerassessment_criteria::sort field in DB 
     */
    public function callback($criteriasort) {
        global $OUTPUT;
        
        error_log( "calling callback for peers on this assessment");
        
        $mform = $this->_form;
        $peers = $this->_customdata['peers'];
        $grades = range(0, 5); // TODO respect criteria definition of grade.

        // Create a table, users (peers) as rows.
        $t = new html_table();
        $t->attributes['class'] = 'userenrolment';
        $t->id = 'mod-peerassessment-summary-table';
        $t->head = array('name', 'grade', 'feedback'); // TODO lang
        
        // I would like to layout these fields using a table, but it seems the fields dont actually add to the form?
//         foreach ($peers as $peer) {
//             $row = new html_table_row();

//             $id = '[' . $peer->id . ']';

//             // Create field to collect a grade 
//             $field = 'grade__idx_'. $criteriasort . $id;
//             $gradeinput = $mform->createElement('select', $field, get_string('grade', 'peerassessment'), $grades);
//             //$gradeinput ->setDefault(3);
//             // $mform->setType("grade$id", PARAM_ALPHA);
//             // $mform->addHelpButton('grade', 'langkey_help', 'peerassessment');
//             // $mform->disabledIf('grade', 'value1', 'eq|noteq', 'value2');
// //            $mform->addRule($field, get_string('required'), 'required', null, 'client',false,true);
//             // $mform->setAdvanced('grade');
            
//             $field = 'feedback__idx_' . $criteriasort . $id;
//             $feedbackinput = $mform->createElement('textarea', $field, get_string('feedback', 'peerassessment'),
//                 array('rows' => 1, 'cols' => 40));
//             // $mform->setType('feedback', PARAM_RAW);
//             // $mform->setDefault('feedback', 'defult string value for the textarea');
//             // $mform->addHelpButton('feedback', 'langkey_help', 'peerassessment');
//             // $mform->disabledIf('feedback', 'value1', 'eq|noteq', 'value2');
//             // $mform->addRule($field, get_string('required'), 'required', null, 'client',false,true);
//             // $mform->setAdvanced('feedback');
            

//             $row->cells = array( fullname($peer),
//                                  $gradeinput->toHtml(),
//                                  $feedbackinput->toHtml()
//             );
//             $t->data[] = $row;
//         }
//         $mform->addElement('html', html_writer::table($t) );

            foreach ($peers as $peer) {
                $mform->addElement('html', '<div>'); // might give us change to layout with CSS
                
                $id = '[' . $peer->id . ']';
                
                $mform->addElement('static', 'label2', fullname($peer));
                    
                // Create field to collect a grade
                $field = 'grade__idx_'. $criteriasort . $id;
                $mform->addElement('select', $field, get_string('grade', 'peerassessment'), $grades);
                $mform ->setDefaults($field,3);
                $mform->addRule($field, get_string('required'), 'required', null, 'client');
    
                // Field to collect a bit of feeedback on a peer
                $field = 'feedback__idx_' . $criteriasort . $id;
                $mform->addElement('textarea', $field, get_string('feedback', 'peerassessment'),
                    array('rows' => 1, 'cols' => 40));
                
                $mform->addElement('html', '</div>');
            }

    }
    
    
    /**
     * 
     * Called automatically.
     * Collect the criteria from the database and populate the criteria form fields so we see the criteria the tutor setup.
     * 
     * @param unknown $data
     * @return unknown
     */
    public function set_data($data) {
       
        // Collect the criteria data for this peerassessment and add into $data.
        $peerassess = get_coursemodule_from_id('peerassessment', $this->_customdata['id']);
        $pac = new peerassessment_criteria( $peerassess ->instance );
        $pac->add_submission_form_set_data($data);
               
        return parent::set_data($data);
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