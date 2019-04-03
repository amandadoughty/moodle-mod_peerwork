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
    static protected $intermediate_grades = array();   // [memberid] -> grade
    static protected $grades = array();                // [memberid] -> final awarded grade of modifier and weighting
    static protected $calc_total_marks_awarded = array();	// [memberid] ->webPA score
    
    
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
     * Called on this group ($this->group->id)
     *
     * @return  boolean  The operation was successful.
     */
    public function calculate() {
        global $DB;
        
        if( self::$calculationdone ) { // Try and avoid recalculating grades 
            return true;
        }
        //error_log("\n\n\npeerassessment WebPAAlgorithm calculating for group ..." . $this->group->name );

        // Get details of the submission, we can only give grades if the tutor has provided a grade. 
        $submission = $DB->get_record('peerassessment_submission', array('assignment' => $this->peerassessment->id, 'groupid' => $this->group->id));
        if (empty($submission) || !isset($submission->grade) ) {
            return false;
        }
        
        $group_member_frac_scores_awarded = array();    // array of [member->id] => fractional
         
        /* (2)
         * Get the normalised fraction awarded by each member to each member
         * If member-A gave member-B 4 marks, then the fraction awarded = 4 / total-marks-member-A-awarded
         */
        /* (4)
         * Get the total fractional score awarded to each member for each question
         */
        
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
        $_calc_group_submitters = array();  // Count which members if this group submitted grades to their peers.
        foreach ($members as $member) {
            $awarded = peerassessment_grade_by_user($this->peerassessment, $member, $members); // array of grades this member awarded to others
            //error_log("member " . $member->id . " awarded " .  print_r($awarded,true));
            
            $total = 0;
            foreach( $awarded ->grade as $a ) { // The grade may be '-' to signify not given.
                if( is_numeric($a) ) {
                    $total += $a;   
                    $_calc_group_submitters[$member->id] = 1; // this member awarded a grade to someone else. Well done!
                } else {
                    $total += 0;
                }
            }
            // Convert awarded grades to fractional.
            $group_member_frac_scores_awarded[$member->id] = $awarded ->grade;
            array_walk( $group_member_frac_scores_awarded[$member->id], function (&$g, $key, $t) {
                // $total is passed in as '$t'
                if( !is_numeric($g)  ) { $g = 0; }
                if( $t != 0 ) { $g /= $t; }       
            }, $total);
        }  
        // All the scores awarded are now normalised. Time to calculate the actual Web-PA scores
        
        /* (3)
         * Get the multiplication factor we need to calculate the Web-PA scores
         * factor = num-members-total / num-members-submitted
         */
        $num_members = count($members);  
        $num_submitted = count($_calc_group_submitters);
        $multi_factor = ($num_submitted>0) ? ($num_members / $num_submitted) : 1 ;
        $pa_group_mark = $submission->grade;//($this->_params['weighting']/100) * $group_mark;
        $nonpa_group_mark = 0;//( (100-$this->_params['weighting']) /100 ) * $group_mark;
        
        error_log( $this->group->name  . " multifactor=$multi_factor  num_members=$num_members  num_submitted=$num_submitted" );
        error_log("peerassessment WebPAAlgorithm  group_member_frac_scores_awarded=" . print_r($group_member_frac_scores_awarded,true) );
       
        /* (5)
         * Get the Web-PA score = total fractional score awarded to a member * multiplication-factor
         */
        self::$calc_total_marks_awarded = array();
        foreach( array_values($group_member_frac_scores_awarded) as $received ) { // $received is an array
            
            foreach($received as $memberid => $fraction ) {
            	if( !array_key_exists($memberid, self::$calc_total_marks_awarded) ) { self::$calc_total_marks_awarded[$memberid] = 0; }
            	self::$calc_total_marks_awarded[$memberid] +=  $fraction;
            }
        }
        array_walk(self::$calc_total_marks_awarded, function(&$item1, $key, $prefix) { $item1 *= $prefix; }, $multi_factor );
        error_log("peerassessment WebPAAlgorithm  calc_total_marks_awarded=" . print_r(self::$calc_total_marks_awarded,true) );
        
        /* (6)
         * Get the member's intermediate grade = Web-PA score * weighted-group-mark   (does not include penalties)
         */
        foreach( self::$calc_total_marks_awarded as $memberid => $total_frac_score ) {
            
            $intermediate_grade = (($total_frac_score * $pa_group_mark) + $nonpa_group_mark);
            if ($intermediate_grade<0) { $intermediate_grade = 0; }
            elseif ($intermediate_grade>100) { $intermediate_grade = 100; }
            self::$intermediate_grades[$memberid] = $intermediate_grade;
            self::$grades[$memberid] = $intermediate_grade;
        }
        
        //error_log("final grades are " . print_r(self::$grades,true) );
        self::$calculationdone = true;        
        return true;        
    }
  
    /**
     * Return the final calculated grade for a member by accessing the static datastructure that has been populated by calculate()
     * 
     * @return float 
     */
    public function getGrade(stdClass $member) {
        //error_log("peerassessment getGrade final grade for member= " . print_r($member->username,true) . " = " . self::$grades[$member->id]);
        return self::$grades[$member->id];
    }
    
    /**
     * Return the webPA score eg 1.2 which is the relative contribution value.
     * @return float
     */
    public function getScore(stdClass $member) {
    	return self::$calc_total_marks_awarded[$member->id];
    	
    }
}