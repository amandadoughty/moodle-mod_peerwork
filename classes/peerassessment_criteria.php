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
 * @package    mod
 * @subpackage peerassessment
 * @copyright  2018 Coventry University
 * @author     Kevin Moore <ac4581@coventry.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Handles the assessment criteria including saving/restore to database.
 * Assessment criteria are specific points to be considered when a peer marks a submission and consist of
 * a description, an expected type of grade (likert, split100,...) for the students to give to each other and some feedback.
 * 
 * Criteria are held in the $tablename=peerassessment_criteria table:-
 *  descriptionformat: {0=moodle autoformat,1=editor, 2=plain text,3=HTML, 4=markdown}
 */
class peerassessment_criteria  {

	/**
	 * DB table these criteria are stored in.
	 */
    protected static $tablename = 'peerassessment_criteria';
    protected static $tablename2 = 'peerassessment_presets';
    /**
     * The criteria are numbered 0 to $numcriteria-1
     * @var integer
     */
    protected static $numcriteria = 3; // HARDCODE for now
    
    protected $id; // the peereassessment id field from peerassessment table, used to lookup into peerassessment_criteria
    
    protected static $langkey = 'peerassessment';
    
    function __construct($peerassessmentid) {
        $this->id = $peerassessmentid;
    }
    
    /**
     * Get the criteria created for this peerassesment, making sure we have the array in field=sort order.
     * @return DB records from  peerassessment_criteria, one record per criteria on this assessment.
     */
    public function getCriteria() {
        global $DB;
        $records = $DB ->get_records(self::$tablename, array('peerassessmentid'=>$this->id ) );

        // return in field=sort order, using a php array sorting function
        uasort($records, function($a, $b) { if ($a->sort == $b->sort) {return 0;} return ($a->sort < $b->sort) ? -1 : 1; } );        
        return $records;
    }
    
    /**
     * Get the set of preset criteria, making sure we have the array in field=sort order.
     * @return DB records from  peerassessment_preset, one record per criteria on this assessment.
     */
    public function getPresetCriteria($setid=0) {
    	global $DB;
    	
    	$d = array();
    	if( $setid == 0 ) {	// we are looking for the set descriptors.
    		$d = array('sort' => -1);
    	} else {
    		$d = array('setid' => $setid);
    	}
    	$records = $DB ->get_records(self::$tablename2, $d );
    	error_log("getPresetCriteria setid=$setid found ". print_r($records,true) );
    	
    	// return in field=sort order, using a php array sorting function
    	uasort($records, function($a, $b) { if ($a->sort == $b->sort) {return 0;} return ($a->sort < $b->sort) ? -1 : 1; } );
    	return $records;
    }
    
    /**
     * part of setting up the assessment form - called from mod_form.php:definition()
     * Define the field elements, modified from workshop/form/accumulative/edit_form.php that allow for the assessments criteria settings to be specified.
     * The major section is "Assessment criteria settings"
     * TODO like to group the criteria better (box or expandable) maybe use AMD 
     */
    public function definition( &$mform ) {
               
        $mform->addElement('header', 'assessmentcriteriasettings', get_string('assessmentcriteria:header', 'peerassessment'));
        $mform->addElement('hidden', 'norepeats', self::$numcriteria);
        $mform->setType('norepeats', PARAM_INT);
        $mform->setConstants(array('norepeats' => self::$numcriteria));     // value not to be overridden by submitted value
        
        $descriptionopts    = 'descriptionopts';        // wysiwyg fields options
        
        // A select dropdown of available presets
        $field = 'preset';
        $presets = array();
        $records = $this ->getPresetCriteria(); // No parameter triggers getting the descriptors for the sets
        foreach( $records as $record ) {
        	$presets[$record->setid] = $record->description;
        }
        error_log("preassessment_criteria loading preset " . print_r($presets,true) );
        $mform->addElement('select', $field, get_string('assessmentcriteria:usepreset',self::$langkey), $presets );
        $mform->setType($field, PARAM_INT);
        $mform->addHelpButton($field,'assessmentcriteria:usepreset', self::$langkey);
        
        
        for ($i = 0; $i < self::$numcriteria; $i++) {
        
            //$mform->addElement('header', 'dimension'.$i, get_string('dimensionnumber', 'peerassessment', $i+1)); // KM doesnt nest into a subheading
//             $field = 'dimension'.$i;
//             $mform->addElement('static', $field, '', get_string('assessmentcriteria:static', self::$langkey, $i+1) );

//             $mform->addElement('hidden', 'criteria_sort'.$i);
//             $mform->setType('criteria_sort'.$i, PARAM_INT);
        
            $field = peerassessment_criteria::makefield('E',$i);
            $mform->addElement('editor', $field,
                get_string('assessmentcriteria:description', self::$langkey, $i+1), '', $descriptionopts);
            $mform->setType($field, PARAM_RAW);
            $mform->addHelpButton($field,'assessmentcriteria:description', self::$langkey);
        
            // Add "Scoring Type" which allows choice of how to score this criteria.
            // This becomes the 'grade' field which decides if its a scale or point in  peerassessment_criteria table.
            // Currently only consider using scales. @see set_data() 
            $field = peerassessment_criteria::makefield('T',$i);
            $mform->addElement('hidden', $field );
            $mform->setType($field, PARAM_ALPHANUMEXT );
            $mform->setDefault($field, 'scale');
            
            $scales = array();
            if ($scales = grade_scale::fetch_all_global()) {
            	foreach($scales as $scale) {
            		$scales[ $scale->id ] = $scale->name;
            	}
            }
           
            $field = peerassessment_criteria::makefield('S',$i);
            $mform->addElement('select', $field, get_string('assessmentcriteria:scoretype',self::$langkey), $scales );
            $mform->setType($field, PARAM_INT);
            $mform->addHelpButton($field,'assessmentcriteria:scoretype', self::$langkey);
            

//             $e = $mform->createElement('modgrade', $field,
//                 get_string('assessmentcriteria:scoretype',self::$langkey, $i+1), true );
//             $mform ->addElement( $e );
//             $mform->setDefault($field, 10);

             
           // $this->maxgradeformelement =

            // For now all the criteria are weighted equally, future may allow weightings. currently hidden.
            // This becomes 'weight' in peerassesment_criteria table.
            $field =  peerassessment_criteria::makefield('W',$i);
            $mform->addElement('hidden', $field);
            $mform->setDefault($field, 1);
            $mform->setType($field, PARAM_INT);
            $mform->addHelpButton($field,'assessmentcriteria:weight', self::$langkey);
        }
    }

    /**
     * Populate form settings from DB.
     * Collect criteria (if any) from the database, interpret and populate the datastructure used to initialise the form
     * when tutor defines the criteria in the assessment.
     * Called by the mod_form.php::set_data() as part of its populating the form.
     * @param unknown $data
     * @param boolean $prepopulate if >0 refers to a predefined set of criteria, use those in preference.
     */
    public function set_data($data) {
                     
        $records = $this ->getCriteria();  
        // so now we have some criteria records
        foreach ($records as $id => $record) {
            
            //error_log( "found criteria record for peerassessment#$id"  . print_r($record, true) );
        	$i = $record ->sort;

        	$data ->{ peerassessment_criteria::makefield('E',$i) } = array('text'=>$record->description, 'format'=> $record->descriptionformat);

            if( $record->grade == 0 ) {
                // If grade equals 0, 'None' then no grading is possible for this dimension, just comments
            	$data ->{ peerassessment_criteria::makefield('S',$i) } = 0;
            } else if ( $record->grade < 0 ) { 
                // -ve values in table signify using a scale for the criteria; @see moodle db table 'scale'
            	$data ->{ peerassessment_criteria::makefield('S',$i) } = 0 - $record ->grade;	// Needs to be back to a positive index for chosing from select dropdown.
                $data ->{ peerassessment_criteria::makefield('T',$i) } = 'scale';
            } else {
                // So we are using a points from 0 ->grade
            	$data ->{ peerassessment_criteria::makefield('S',$i) } = $record ->grade;
            }
            
            $data ->{ peerassessment_criteria::makefield('W',$i) } = $record ->weight;
        }
    }

    /**
     * Utility function to make sure form field names are constructed consistently.
     * @param string $f a unique field key, internal to this code.
     * @param integer $i sort field from database; used to identify which criteria we are refeing to.
     * @return string
     */
	public static function makefield($f,$i) {
		$r = '';
		switch( $f ) {
			case 'E': $r = 'criteria_sort'.$i. '_editor'; break;
			case 'T': $r = 'gradetype_sort'.$i; break;
			case 'S': $r = 'grade_sort'.$i; break;
			case 'W': $r = 'weight_sort'.$i; break;
			default: die( "trying to create an unknown field in the form; this shouldnt happen; the code needs fixing.");
		}
		return $r;
	}
    
    /**
     * Settings
     * Called automatically from lib.php::peerassessment_update_instance() and peerassessment_add_instance() when the settings form is saved.
     * The main settings will already be saved, this intercepts and saves the criteria into self::$tablename
     * 
     * @param stdClass $peerassessment
     * @return boolean
     */
    public function update_instance(stdClass $peerassessment) {
        global $DB;
        
        //error_log("starting to update_instance criteria with data " . print_r($peerassessment,true));
        
        // Get the existing (if any) criteria that are associated with this peerassessment indexed by 'id' field
        // and update.
        $records = $DB->get_records( self::$tablename, array('peerassessmentid'=>''.$peerassessment->id ) );
        // error_log("update_instance existing records= " . print_r($records,true) );

        $track = range(0, self::$numcriteria-1);        
        
        foreach( $records as $record ) { // Update records that already exist in the DB.
            
        	$i = $record ->sort; 
        	$f = peerassessment_criteria::makefield('E',$i);
            $record ->description = $peerassessment ->{$f}['text'];
            $record ->descriptionformat = $peerassessment ->{$f}['format'];
            $record ->grade = $this ->set_grade_for_db( $peerassessment ->{ peerassessment_criteria::makefield('T',$i) },
            											$peerassessment ->{ peerassessment_criteria::makefield('S',$i) } );
            $record ->weight = $peerassessment ->{ peerassessment_criteria::makefield('W',$i) };
            if( ! $DB->update_record(self::$tablename, $record) ) {
                return false;
            }            
            unset( $track[$i] ); // We've seen this and updated. Take off list.
        }
        // So the fields left must be new data. Try and only add records with meaningful data and avoid empty criteria.
        try {
            $transaction = $DB->start_delegated_transaction();
            
            foreach( array_keys($track) as $i) {
            	if( !empty( $peerassessment ->{ peerassessment_criteria::makefield('E',$i) }['text'] ) ) {
                    //error_log( "adding settings $i " . $peerassessment ->{'criteria_sort'.$i.'_editor'}['text'] );
                    
                    $record= new stdClass();
                    $record->peerassessmentid = $peerassessment->id;
                    $record->sort = $i;
                    $f = peerassessment_criteria::makefield('E',$i);
                    $record->description = $peerassessment ->{$f}['text'];
                    $record->descriptionformat = $peerassessment ->{$f}['format'];
                    $record->grade = $this ->set_grade_for_db( $peerassessment ->{ peerassessment_criteria::makefield('T',$i) },
                                                               $peerassessment ->{ peerassessment_criteria::makefield('S',$i) } );
                    $record->weight = $peerassessment ->{ peerassessment_criteria::makefield('W',$i) };
                    
                    $newid = $DB ->insert_record(self::$tablename,$record, true );
                    // error_log("just inserted $newid");
                }          
            }
            $transaction->allow_commit();
        } catch( Exception $ex ) {
            $transaction->rollback($ex);
            return false;
        }

        return true;
    }
    
    /**
     * Utility function to convert the type of grade ('scale'|'number') and grade integer into an integer for storage in DB.
     * DB table peerassessment_criteria stores the type of scoring for each criteria as a number. -ve numbers means its a scale drawn from the scales table.
     * However the form will send back +ve numbers so we also look at the hidden field 'gradetype_sort' to decide how we store in the DB.
     */
    private function set_grade_for_db( $gradetype, $grade ) {
    	
    	$ret = 1;
    	
    	if( $gradetype == 'scale' ) {
    		$ret = 0 - abs( $grade );
    	} else {
    		$ret = $grade;
    	}
    	return $ret;
    }
    
    
//     /*************************************************************************************
//      * SUBMISSION FORM
//      */
    
//     /**
//      * Submission from student.
//      * Add in fields to allow peers to submit their peer assessments of their peers to the marking criteria as set by tutor.
//      * Called by mod_peerassessment_add_submission_form::definition()
//      * @param unknown $mform
//      */
//     public function add_submission_form_definition( &$mform, $gradepeersform = array() ) {
        
//         global $DB;
        
//         // Fetch the criteria for this assessment so we know how to identify them (we need the 
//         // 'sort' field).
//         $records = $DB->get_records( self::$tablename, array('peerassessmentid'=>''. $this->id) );
//         if( count($records) == 0 ) {
//             $mform ->addElement('static', 'nocriteriamessage', '', get_string('assessmentcriteria:nocriteria', self::$langkey ) ); // "No criteria"
//         }
//         // TODO based on per-criteria
//         $grades = range(0, 5);
        
//         $criterianumber = 0;    // For visual display, not the actual criteria sort in the DB 
        
//         foreach ($records as $id => $record) {
//             $criterianumber++;
//       //  for ($i = 0; $i < self::$numcriteria; $i++) {
        
//             //$mform->addElement('header', 'dimension'.$i, get_string('dimensionnumber', 'peerassessment', $i+1)); // KM doesnt nest into a subheading
//             $field = 'static__idx_'. $record ->sort;
//             $mform->addElement('static', $field, '', get_string('assessmentcriteria:static', self::$langkey, $criterianumber) ); // "Criteria number 1"        
        
//             // Field to display a single criteria description TODO make this smaller and readonly
//             $field = 'description__idx_' . $record ->sort . '_editor';
//             $mform->addElement('editor', $field,
//                     get_string('assessmentcriteria:description', self::$langkey, $criterianumber), '', array('size'=>'20'));
//             $mform->setType($field, PARAM_RAW);
//             $mform->addHelpButton($field,'assessmentcriteria:description', self::$langkey);
            
//             // 
//             // Use the callback to add UI elements. 
//             // get a list of peer ids (they are moodle DB user id) and add in grading fields to assess each peer, pass the criteria's sort as a parameter
//             call_user_func_array( $gradepeersform, array($record ->sort));
            
// // //             $mform->addElement('static', 'description', get_string('description', 'exercise'),
// // //                 get_string('descriptionofexercise', 'exercise', $COURSE->students));
            
// //             // A space to accept the grade, going to depend on the grading type.
// //             // TODO will need a grade description??
// //             $field = 'grade__idx_'. $record ->sort . '_' .$peeruserid;
// //             $mform->addElement('select', $field, get_string('assessmentcriteria:grade', self::$langkey), $grades);
// //             $mform->setDefault($field, $default);
// //             $mform->setType($field, PARAM_ALPHA);
// //             $mform->addHelpButton($field, 'assessmentcriteria:grade', self::$langkey);
// // //            $mform->disabledIf('grade', 'value1', 'eq|noteq', 'value2');
// // //             $mform->addRule($field, $strrequired, 'required', null, 'client');
// // //             $mform->setAdvanced('grade');

        
// // //             $field = 'weight__idx_'.$i;
// // //             $mform->addElement('select', $field,
// // //                 get_string('assessmentcriteria:weight', self::$langkey, $i+1), range(0, 5));
// // //             $mform->setDefault($field, 1);
// // //             $mform->addHelpButton($field,'assessmentcriteria:weight', self::$langkey);
//         }
//     }

//   /**
//    * Collect any data about the criteria, grades and feedback awarded (if any) to peers and add to $data so
//    * it populates the form.
//    */
//     public function add_submission_form_set_data($data) {
        
//         global $DB;
//         global $USER;
        
//         error_log("calling add_submission_form_set_data setdata with " . print_r($data, true)  );
        
//         // Get fixed information about the criteria. 
//         $criteria = $DB ->get_records(self::$tablename, array('peerassessmentid'=> $this->id) );
        
//         foreach ($criteria as $id => $record) {
        
//             error_log( "found criteria record for peerassessment# " . $record->peerassessmentid . " ". print_r($record, true) );
//             $data ->{'description__idx_'. $record ->sort . '_editor'} = array('text'=>$record->description, 'format'=> $record->descriptionformat);
//         }
        
//         // Now get all the grades and feedback for each criteria this user has already awarded to their peers.
//         // Transfer into the $data so it populates the UI
//         $mygrades = $DB->get_records('peerassessment_peers', array('peerassessment' => $this->id,
//             'gradedby' => $USER->id), '', 'id,sort,gradefor,feedback,grade');
        
//         foreach( $mygrades as $grade) {
//             $data ->{'grade__idx_'. $grade ->sort }[$grade->gradefor] = $grade ->grade;
//             $data ->{'feedback__idx_'. $grade ->sort }[$grade->gradefor] = $grade ->feedback;
            
//         }

//     }

}