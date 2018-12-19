<?php
/*
* A class implementing the  Loughborough University Web-PA algorithm for calculating student grades.
* 
* This uses a static member to remember calculations between instance of the class rather than redoing everything from scratch
* for each call to the class.
* A new WebPAAlgorithm instance will need to be created and used for each different peer group, so if the $group changes in the constructor
* it recognises that and resets the calculation. 
*/


class WebPAAlgorithm {
    
    protected $peerassessment;
    protected $group;    
    static protected $intermediate_grades = array();   // [memberid] ->grade
    static protected $grades = array();                // [memberid] -> final awarded grade
    
    static private $calculationdone = false;
    
    /**
     * Constructor
     *
     * @return  object  A new instance of this class.
     */
    public function __construct($peerassessment, $group) {
        
        $this-> peerassessment = $peerassessment;
        $this-> group = $group;
        
        // TODO if these have changed, mark the calculationdone invalid
        
    }// /->__construct()
    


    /**
     * Calculate the student's final grades. Following WebPAAlgorithm:class_webpa_algorithm.php:calculate()
     *
     * @return  boolean  The operation was successful.
     */
    public function calculate() {
        global $DB;
        
        if( self::$calculationdone ) {
            return true;
        }
        error_log("\n\n\npeerassessment WebPAAlgorithm calculating for group ..." . $this->group->name );

        // Get details of the submission.
        $submission = $DB->get_record('peerassessment_submission', array('assignment' => $this->peerassessment->id, 'groupid' => $this->group->id));
        if (empty($submission) || !isset($submission->grade) ) {
            return false;
        }
        
        $group_member_frac_scores_awarded = array();    // array of [member->id] => factional
        $group_member_total_received =  array();        // array of [member->id] => total marks received.
        
        
        // Now collect the marks awarded by peers and normalise them.
        // Take each member in turn and work out the fractional awarded (=awarded to peer/total awarded), becomes a structure like:-
        //         Array (
        //             [28] => Array
        //             (
        //                 [25] => 0.2
        //                 [23] => 0.2
        //                 [13] => 0.6
        //                 [28] => 0
        //                 )
        //        
        //             [25] => Array
        //             (
        //                 [28] => 0.14285714285714
        //                 [23] => 0.28571428571429
        //                 [13] => 0.57142857142857
        //                 [25] => 0
        //                 )
        $members = groups_get_members($this->group->id); // Groups API
        foreach ($members as $member) {
            $awarded = peerassessment_grade_by_user($this->peerassessment, $member, $members);
            $total = 0;
            foreach( $awarded ->grade as $a ) {
                $total += is_numeric($a) ? $a : 0; 
            }
            $group_member_frac_scores_awarded[$member->id] = $awarded ->grade;
            array_walk( $group_member_frac_scores_awarded[$member->id], function (&$item1, $key, $prefix) { $item1 /= $prefix; }, $total);
        }
 
        error_log("peerassessment WebPAAlgorithm  group_member_frac_scores_awarded=" . print_r($group_member_frac_scores_awarded,true) );
       
        /* (5)
         * Get the Web-PA score = total fractional score awarded to a member * multiplication-factor
         */
        $multi_factor = 1; // HARDCODE
        $calc_total_marks_awarded = array();
        foreach( array_values($group_member_frac_scores_awarded) as $received ) { // $received is an array
            
            foreach($received as $memberid => $fraction ) {
                $calc_total_marks_awarded[$memberid] +=  $fraction;
            }
        }
        array_walk($calc_total_marks_awarded, function(&$item1, $key, $prefix) { $item1 *= $prefix; }, $multi_factor );
        error_log("peerassessment WebPAAlgorithm  calc_total_marks_awarded=" . print_r($calc_total_marks_awarded,true) );
        
        /* (6)
         * Get the member's intermediate grade = Web-PA score * weighted-group-mark   (does not include penalties)
         */
        $pa_group_mark = 100;
        $nonpa_group_mark = 0;
        foreach( $calc_total_marks_awarded as $memberid => $total_frac_score ) {
            
            $intermediate_grade = (($total_frac_score * $pa_group_mark) + $nonpa_group_mark);
            if ($intermediate_grade<0) { $intermediate_grade = 0; }
            elseif ($intermediate_grade>100) { $intermediate_grade = 100; }
            self::$intermediate_grades[$memberid] = $intermediate_grade;
            self::$grades[$memberid] = $intermediate_grade;
        }
        
        error_log("final grades are " . print_r($this->grades,true) );
        self::$calculationdone = true;        
        return true;        
    }

    //$total = peerassessment_get_indpeergradestotal($this->peerassessment, $this->group, $member); // total received
    
    
    
    public function getGrade(stdClass $member) {
        error_log("peerassessment getGrade final grade for member= " . print_r($member->username,true) . " = " . self::$grades[$member->id]);
        return self::$grades[$member->id];
    }
}