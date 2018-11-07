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
 * Assessment criteria are specific points to be considered when a peer marks a submission.
 * 
 * Table: descriptionformat: {0=moode autoformat,1=editor, 2=plain text,3=HTML, 4=markdown}
 */
class peerassessment_criteria  {

    protected static $tablename = 'peerassessment_criteria';
    /**
     * The criteria are numbered 0 to $numcriteria-1
     * @var integer
     */
    protected static $numcriteria = 3;
    
    protected $id; // the peereassessment id field from peerassessment table, used to lookup into peerassessment_criteria
    
    protected static $langkey = 'peerassessment';
    
    function __construct($peerassessmentid) {
        $this->id = $peerassessmentid;
    }
    
    /**
     * Define the field elements, modified from workshop/form/accumulative/edit_form.php
     * TODO like to group the criteria better (box or expandable) maybe use AMD to 
     */
    public function definition( &$mform ) {
               
        $mform->addElement('header', 'assessmentcriteriasettings', get_string('assessmentcriteria:header', 'peerassessment'));
        $mform->addElement('hidden', 'norepeats', self::$numcriteria);
        $mform->setType('norepeats', PARAM_INT);
        $mform->setConstants(array('norepeats' => self::$numcriteria));     // value not to be overridden by submitted value
        
        $descriptionopts    = 'descriptionopts';        // wysiwyg fields options
        
        for ($i = 0; $i < self::$numcriteria; $i++) {
        
            //$mform->addElement('header', 'dimension'.$i, get_string('dimensionnumber', 'peerassessment', $i+1)); // KM doesnt nest into a subheading
            $mform->addElement('static', 'dimension'.$i, '', get_string('assessmentcriteria:static', self::$langkey, $i+1) );

            $mform->addElement('hidden', 'dimensionid__idx_'.$i);
            $mform->setType('dimensionid__idx_'.$i, PARAM_INT);
        
            $field = 'description__idx_'.$i.'_editor';
            $mform->addElement('editor', $field,
                get_string('assessmentcriteria:description', self::$langkey, $i+1), '', $descriptionopts);
            $mform->setType($field, PARAM_RAW);
            $mform->addHelpButton($field,'assessmentcriteria:description', self::$langkey);
        
            // Type modgrade does the dropdown and options automatically.
            $field = 'grade__idx_'.$i;
            $mform->addElement('modgrade', $field,
                get_string('assessmentcriteria:maxgrade',self::$langkey, $i+1), null, true);
            $mform->setDefault($field, 10);
            $mform->addHelpButton($field,'assessmentcriteria:maxgrade', self::$langkey);
        
            $field = 'weight__idx_'.$i;
            $mform->addElement('select', $field,
                get_string('assessmentcriteria:weight', self::$langkey, $i+1), range(0, 5));
            $mform->setDefault($field, 1);
            $mform->addHelpButton($field,'assessmentcriteria:weight', self::$langkey);
        }
    }

    /**
     * Collect criteria (if any) from the database and populate the datastructure used to initialise the form.
     * Called by the mod_form.php::set_data() as part of its populating the form.
     * @param unknown $data
     */
    public function set_data($data) {
        global $DB;

        $records = $DB ->get_records(self::$tablename, array('peerassessmentid'=>$data->id) );
        
        foreach ($records as $id => $record) {
            
            //error_log( "found criteria record for peerassessment#$id"  . print_r($record, true) );

            $data ->{'description__idx_'. $record ->sort . "_editor" } = array('text'=>$record->description, 'format'=> $record->descriptionformat);

            if( $record->grade == 0 ) {
                // If grade equals 0, 'None' then no grading is possible for this dimension, just comments
                $data ->{'grade__idx_'. $record ->sort } = 0;
            } else if ( $record->grade < 0 ) { 
                // The criteria uses a scale. -2 =  "Default competence", -1 = "Connected ways" held in table 'scale'
                //                 $diminfo[$dimid]->min = 1;
                //                 $diminfo[$dimid]->max = count(explode(',', $dimrecord->scale));
                $data ->{'grade__idx_'. $record ->sort } = $record ->grade;
            } else {
                // So we are using a points from 0 ->grade
                $data ->{'grade__idx_'. $record ->sort } = $record ->grade;
            }
            
            $data ->{'weight__idx_'. $record ->sort } = $record ->weight;
        }
    }
    
    /**
     * [description__idx_0_editor] => Array
        (
            [text] => This is a description of the criteria that has been changed
set a new line
            [format] => 0
        )
     * @param stdClass $peerassessment
     * @return boolean
     */
    public function update_instance(stdClass $peerassessment) {
        global $DB;
        // error_log("update_instance criteria with data " . print_r($peerassessment,true));
        
        // Get the existing (if any) criteria that are associated with this peerassessment indexed by 'id' field
        // and update.
        $records = $DB->get_records( self::$tablename, array('peerassessmentid'=>''.$peerassessment->id ) );
        // error_log("update_instance existing records= " . print_r($records,true) );

        $track = range(0, self::$numcriteria-1);        
        
        foreach( $records as $record ) {
            
            $i = $record ->sort;
            // error_log("to be updated " . print_r( $peerassessment ->{'description__idx_'.$i.'_editor'}['text'] ,true) );
            
            $record ->description = $peerassessment ->{'description__idx_'.$i.'_editor'}['text'];
            $record ->descriptionformat = $peerassessment ->{'description__idx_'.$i.'_editor'}['format'];
            $record ->grade = $peerassessment ->{'grade__idx_'. $i };
            $record ->weight = $peerassessment ->{'weight__idx_'.$i};
            if( ! $DB->update_record(self::$tablename, $record) ) {
                return false;
            }            
            unset( $track[$i] ); // We've seen this and updated. Take off list.
        }
        // So the fields left must be new data. Try and only add records with meaningful data.
        try {
            $transaction = $DB->start_delegated_transaction();
            
            foreach( array_keys($track) as $i) {
                if( !empty( $peerassessment ->{'description__idx_'.$i.'_editor'}['text'] ) ) {
                    // error_log( "updating $i " . $peerassessment ->{'description__idx_'.$i.'_editor'}['text'] );
                    
                    $criteria = new stdClass();
                    $criteria ->peerassessmentid = $peerassessment->id;
                    $criteria ->sort = $i;
                    $criteria ->description = $peerassessment ->{'description__idx_'.$i.'_editor'}['text'];
                    $criteria ->descriptionformat = $peerassessment ->{'description__idx_'.$i.'_editor'}['format'];
                    $criteria ->grade = $peerassessment ->{'grade__idx_'. $i };
                    $criteria ->weight = $peerassessment ->{'weight__idx_'.$i};
                    
                    $newid = $DB ->insert_record(self::$tablename,$criteria, true );
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

}