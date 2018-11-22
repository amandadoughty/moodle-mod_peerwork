<?php
// This file is part of 3rd party created module for Moodle - http://moodle.org/.
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
define('PEERASSESSMENT_STATUS_NOT_SUBMITTED', 0);
define('PEERASSESSMENT_STATUS_SUBMITTED', 1);
define('PEERASSESSMENT_STATUS_GRADED', 2);
define('PEERASSESSMENT_STATUS_NOT_SUBMITTED_CLOSED', 3);

define('PEERASSESSMENT_DUEDATE_NOT_USED', 0);
define('PEERASSESSMENT_DUEDATE_OK', 1);
define('PEERASSESSMENT_DUEDATE_PASSED', 2);

define('PEERASSESSMENT_FROMDATE_NOT_USED', 0);
define('PEERASSESSMENT_FROMDATE_OK', 1);
define('PEERASSESSMENT_FROMDATE_BEFORE', 2);

define('PEERASSESSMENT_SIMPLE', 'simple');
define('PEERASSESSMENT_OUTLIER', 'outlier');
define('PEERASSESSMENT_WEBPA', 'webpa');

require_once($CFG->dirroot . '/lib/grouplib.php');

function peerassessment_get_peers($course, $peerassessment, $groupingid, $group = null) {
    global $USER;

    if (!$group) {
        $group = peerassessment_get_mygroup($course, $USER->id, $groupingid);
    }

    $members = groups_get_members($group);
    $membersgradeable = $members;

    if (!$peerassessment->selfgrading) {
        unset($membersgradeable[$USER->id]);
    }

    return $membersgradeable;
}

/**
 * Gets the group id for the group the user belongs to. Prints errors if
 * the user belongs to none or more than one group. Can be restricted to
 * groups within a grouping.
 *
 * @param int $courseid The id of the course.
 * @param int $userid The id of the user.
 * @param int $groupingid optional returns only groups in the specified grouping.
 * @param bool $die - @TODO check use of this parameter.
 * @return int The group id.
 */
function peerassessment_get_mygroup($courseid, $userid, $groupingid = 0, $die = true) {
    global $CFG;

    $mygroups = groups_get_all_groups($courseid, $userid, $groupingid);

    if ($die && count($mygroups) == 0) {
        print_error("You do not belong to any group.");
    } else if ($die && count($mygroups) > 1) {
        print_error("You belong to more than one group, this is currently not supported.");
    }

    $mygroup = array_shift($mygroups);

    return $mygroup->id;
}

/**
 * Gets the status.
 * @param $peerassessment
 * @param int $group returns only groups in the specified grouping.
 */
function peerassessment_get_status($peerassessment, $group) {
    global $DB;
    $submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $group->id));
    $status = new stdClass();
    $duedate = peerassessment_due_date($peerassessment);

    if ($submission && $submission->timegraded) {
        $status->code = PEERASSESSMENT_STATUS_GRADED;
        $user = $DB->get_record('user', array('id' => $submission->gradedby));
        $status->text = "Assessment graded by " . fullname($user) . ' on ' .
        userdate($submission->timegraded) . '. Grade: ' . $submission->grade;
        $status->text = "Assessment graded by " . fullname($user) . ' on ' .
        userdate($submission->timegraded) . '.';
        return $status;
    }

    if (!$submission && $duedate == PEERASSESSMENT_DUEDATE_PASSED) {
        $status->code = PEERASSESSMENT_STATUS_NOT_SUBMITTED_CLOSED;
        $status->text = "Nothing submitted yet but due date passed " . format_time(time() - $peerassessment->duedate) . ' ago.';
        return $status;
    }

    if (!$submission) {
        $status->code = PEERASSESSMENT_STATUS_NOT_SUBMITTED;
        $status->text = "Nothing submitted yet";
        return $status;
    }

    if ($duedate == PEERASSESSMENT_DUEDATE_PASSED) {
        $submiter = $DB->get_record('user', array('id' => $submission->userid));
        $status->code = PEERASSESSMENT_STATUS_SUBMITTED;
        $status->text = "First submitted by " . fullname($submiter) . ' on ' . userdate($submission->timecreated) .
        ". Due date has passed " . format_time(time() - $peerassessment->duedate) . ' ago.';
        return $status;
    } else {
        $submiter = $DB->get_record('user', array('id' => $submission->userid));
        $status->code = PEERASSESSMENT_STATUS_SUBMITTED;
        $status->text = "First submitted by " . fullname($submiter) . ' on ' . userdate($submission->timecreated);
        return $status;
    }
}

/**
 * FOLLOWING METHOD COPIED FROM ASSIGN TO CHECK IF ANY SUBMISSIONS OR GRADES YET.
 * Does an assignment have submission(s) or grade(s) already?
 *
 * @return bool
 */
function has_been_graded($peerassessment) {

    global $DB;
    $submissions = $DB->get_records('peerassessment_submission', array('assignment' => $peerassessment->id));
    $status = new stdClass();
    $status->code = '';

    foreach ($submissions as $submission) {

        if ($submission && $submission->timegraded) {
            $status->code = PEERASSESSMENT_STATUS_GRADED;
        }
    }

    if ($status->code == PEERASSESSMENT_STATUS_GRADED) {
        return true;
    } else {
        return false;
    }

}

/**
 * Was due date used and has it passed?
 * @param $peerassessment
 */
function peerassessment_due_date($peerassessment) {
    if (!$peerassessment->duedate) {
        return PEERASSESSMENT_DUEDATE_NOT_USED;
    }

    if ($peerassessment->duedate < time()) {
        return PEERASSESSMENT_DUEDATE_PASSED;
    } else {
        return PEERASSESSMENT_DUEDATE_OK;
    }
}

/**
 * Was from date used and is it after?
 * @param $peerassessment
 */
function peerassessment_from_date($peerassessment) {
    if (!$peerassessment->fromdate) {
        return PEERASSESSMENT_FROMDATE_NOT_USED;
    }

    if ($peerassessment->fromdate > time()) {
        return PEERASSESSMENT_FROMDATE_BEFORE;
    } else {
        return PEERASSESSMENT_FROMDATE_OK;
    }
}

/**
 * Can student $user submit/edit based on the current status?
 * @param $peerassessment
 */
function peerassessment_is_open($peerassessment, $groupid = 0) {
    global $DB;
    $status = new stdClass();
    $status->code = false;

    // Is it before from date?
    $fromdate = peerassessment_from_date($peerassessment);
    if ($fromdate == PEERASSESSMENT_FROMDATE_BEFORE) {
        $status->text = "Assessment not open yet.";
        return $status;
    }

    $course = $DB->get_record('course', array('id' => $peerassessment->course), '*', MUST_EXIST);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);

    // Is it already graded?
    $pstatus = peerassessment_get_status($peerassessment, $group);
    if ($pstatus->code == PEERASSESSMENT_STATUS_GRADED) {
        $status->text = "Assessment already graded.";
        return $status;
    }

    // Is it after due date?
    $duedate = peerassessment_due_date($peerassessment);
    if ($duedate == PEERASSESSMENT_DUEDATE_PASSED) {
        if ($peerassessment->allowlatesubmissions) {
            $status->code = true;
            $status->text = "After due date but late submissions allowed.";
        } else {
            $status->text = "After due date and late submissions not allowed.";
        }
        return $status;
    }

    // If we are here it means it's between from date and due date.
    $status->code = true;
    $status->text = "Assessment open.";
    return $status;
}

/**
 * Get grades for all peers in a group
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_peer_grades($peerassessment, $group, $membersgradeable = null, $full = true) {
    global $DB;

    $return = new stdClass();

    $peers = $DB->get_records('peerassessment_peers', array('peerassessment' => $peerassessment->id, 'groupid' => $group->id));
    $grades = array();
    $feedback = array();

    foreach ($peers as $peer) {
        $grades[$peer->gradedby][$peer->gradefor] = $peer->grade;
        $feedback[$peer->gradedby][$peer->gradefor] = $peer->feedback;
    }

    if ($full) {
        foreach ($membersgradeable as $member1) {
            foreach ($membersgradeable as $member2) {
                if (!isset($grades[$member1->id][$member2->id])) {
                    $grades[$member1->id][$member2->id] = '-';
                }
                if (!isset($feedback[$member1->id][$member2->id])) {
                    $feedback[$member1->id][$member2->id] = '-';
                }
            }
        }
    }

    $return->grades = $grades;
    $return->feedback = $feedback;

    return $return;
}

/**
 * How was user graded by his peers
 *
 * @param $id peer assessment id
 * @param $userid user id
 */
function peerassessment_gradedme($id, $userid, $membersgradeable) {
    global $DB;
    $gradedme = new stdClass();

    // How others graded me.
    $myresults = $DB->get_records('peerassessment_peers', array('peerassessment' => $id, 'gradefor' => $userid),
        '', 'gradedby,feedback,grade');
    foreach ($membersgradeable as $member) {
        if (isset($myresults[$member->id])) {
            $gradedme->feedback[$member->id] = $myresults[$member->id]->feedback;
            $gradedme->grade[$member->id] = $myresults[$member->id]->grade;
        } else {
            $gradedme->feedback[$member->id] = '-';
            $gradedme->grade[$member->id] = '-';
        }
    }

    return $gradedme;
}


/**
 * Get peer grades for an individual. Takes into account treat0asgrade
 * @param $peerassessment
 * @param $group
 * @param $user
 */
function peerassessment_get_indpeergrades($peerassessment, $group, $user) {
    global $DB;

    if ($peerassessment->treat0asgrade) {
        $records = $DB->get_records_sql('SELECT id, grade FROM {peerassessment_peers} WHERE peerassessment=? AND groupid=?
            AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));
    } else {
        $records = $DB->get_records_sql('SELECT id, grade FROM {peerassessment_peers} WHERE grade>0 AND peerassessment=?
            AND groupid=? AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));

    }

    $peergrades = array();
    foreach ($records as $record) {
        $peergrades[] = $record->grade;
    }

    return $peergrades;
}


/**
 * Get count of an individuals peer grades. Takes into account treat0asgrade
 * @param $peerassessment
 * @param $group
 * @param $user
 */
function peerassessment_get_indcount($peerassessment, $group, $user) {
    global $DB;

    if ($peerassessment->treat0asgrade) {
        $count = (int)$DB->count_records_sql('SELECT COUNT(grade) FROM {peerassessment_peers} WHERE peerassessment=?
            AND groupid=? AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));
    } else {
        $count = (int)$DB->count_records_sql('SELECT COUNT(grade) FROM {peerassessment_peers} WHERE grade>0 AND peerassessment=?
            AND groupid=? AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));
    }

    if (!$count) {
        return 0;
    } else {
        return $count;
    }
}


/**
 * Get sum of an individuals peer grades. Rounded to two decimal places.
 * @param $peerassessment
 * @param $group
 * @param $user
 */
function peerassessment_get_indpeergradestotal($peerassessment, $group, $user) {
    global $DB;

    if ($peerassessment->treat0asgrade) {
        $gradesum = $DB->get_record_sql('SELECT SUM(grade) AS s FROM {peerassessment_peers} WHERE peerassessment=? AND
            groupid=? AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));
    } else {
        $gradesum = $DB->get_record_sql('SELECT SUM(grade) AS s FROM {peerassessment_peers} WHERE grade>0 AND peerassessment=?
            AND groupid=? AND gradefor=?',
            array($peerassessment->id, $group->id, $user->id));

    }

    return $gradesum->s;
}


/**
 * Get count of peer grades, multiple criteria will cause more than one grade per peer. Takes into account treat0asgrade
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_groupcount($peerassessment, $group) {
    global $DB;

    if ($peerassessment->treat0asgrade) {
        $count = (int)$DB->count_records_sql('SELECT COUNT(grade) FROM {peerassessment_peers} WHERE peerassessment=? AND groupid=?',
            array($peerassessment->id, $group->id));
    } else {
        $count = (int)$DB->count_records_sql('SELECT COUNT(grade) FROM {peerassessment_peers} WHERE grade>0 AND
            peerassessment=? AND groupid=?',
            array($peerassessment->id, $group->id));
    }

    if (!$count) {
        return 0;
    } else {
        return $count;
    }
}


/**
 * Get sum of peer grades. Rounded to two decimal places.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_grouppeergradestotal($peerassessment, $group) {
    global $DB;

    if ($peerassessment->treat0asgrade) {
        $gradesum = $DB->get_record_sql('SELECT SUM(grade) AS s FROM {peerassessment_peers} WHERE peerassessment=? AND groupid=?',
            array($peerassessment->id, $group->id));
    } else {
        $gradesum = $DB->get_record_sql('SELECT SUM(grade) AS s FROM {peerassessment_peers} WHERE grade>0 AND
            peerassessment=? AND groupid=?',
            array($peerassessment->id, $group->id));
    }

    return $gradesum->s;
}


/**
 * Get group average. Either simple or adjusted for outlier.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_groupaverage($peerassessment, $group) {
    global $DB;

    // Can't calculate grade if student does not belong to any group.
    if (!$group) {
        return null;
    }

    if ($peerassessment->calculationtype == PEERASSESSMENT_SIMPLE) {
        $groupaverage = peerassessment_get_simplegravg($peerassessment, $group);
    } else if ($peerassessment->calculationtype == PEERASSESSMENT_OUTLIER) {
        $groupaverage = peerassessment_get_adjustedgravg($peerassessment, $group);
    } else {
        return null;
    }

    return $groupaverage;
}


/**
 * Get simple group average. Rounded to two decimal places.
 * May return NAN (if $count was zero) which the caller should handle. 
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_simplegravg($peerassessment, $group) {
    global $DB;

    $count = peerassessment_get_groupcount($peerassessment, $group);
    $total = peerassessment_get_grouppeergradestotal($peerassessment, $group);

    error_log("peerassessment_get_simplegravg count=" . $count . " total=" . $total );
    if($count>0) {
        return round($total / $count, 2);
    } else {
        return NAN;
    }

    // return $count;
}


/**
 * Get adjusted group average. Rounded to two decimal places.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_adjustedgravg($peerassessment, $group) {
    global $DB;

    $peermarks = array();
    $averagetotal = 0;
    $count = 0;
    $groupaverage = 0;

    $members = groups_get_members($group->id);
    foreach ($members as $member) {
        $standarddev = peerassessment_get_indsd($peerassessment, $group, $member);
        $indaverage = peerassessment_get_simpleindavg($peerassessment, $group, $member);

        $peermarks[$member->id] = new stdClass();
        $peermarks[$member->id]->userid = $member->id;
        $peermarks[$member->id]->standarddev = $standarddev;

        if ($peermarks[$member->id]->standarddev <= get_config('peerassessment', 'standard_deviation')) {
            $peermarks[$member->id]->indaverage = $indaverage;
        } else {
            $peermarks[$member->id]->indaverage = 0;
        }
    }

    // THIS CAN'T BE DONE UNTIL INDIVIDUAL AVERAGES ARE ALL SET TO INDAV OR 0. NEEDS TO BE A SEPARATE FOREACH.
    foreach ($members as $member) {

        $averagetotal = $averagetotal + $peermarks[$member->id]->indaverage;
        if ($peermarks[$member->id]->standarddev <= get_config('peerassessment', 'standard_deviation')) {
            $count = $count + 1;
        }
    }

    $groupaverage = $averagetotal / $count;

    return round($groupaverage, 2);

}


/**
 * Get individual average.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_individualaverage($peerassessment, $group, stdClass $member) {
    global $DB;

    // Can't calculate grade if student does not belong to any group.
    if (!$group) {
        return null;
    }

    if ($peerassessment->calculationtype == PEERASSESSMENT_SIMPLE) {
        $average = peerassessment_get_simpleindavg($peerassessment, $group, $member);
    } else if ($peerassessment->calculationtype == PEERASSESSMENT_OUTLIER) {
        $average = peerassessment_get_adjustedindavg($peerassessment, $group, $member);
    } else {
        return null;
    }

    return $average;
}


/**
 * Get individual user average.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_simpleindavg($peerassessment, $group, $user) {
    global $DB;

    $count = peerassessment_get_indcount($peerassessment, $group, $user);
    $total = peerassessment_get_indpeergradestotal($peerassessment, $group, $user);

    if ($count ==0) {
        return '-';
    } else {
        return round($total / $count, 2);
    }
}


/**
 * Get adjusted individual user average which takes into account the standard deviation also
 * @param $peerassessment
 * @param $group
 */
function peerassessment_get_adjustedindavg($peerassessment, $group, $member) {
    global $DB;

    $thisperson = $member;

    $peermarks = array();
    $averagetotal = 0;
    $count = 0;

    $members = groups_get_members($group->id);
    foreach ($members as $member) {
        $standarddev = peerassessment_get_indsd($peerassessment, $group, $member);
        $indaverage = peerassessment_get_simpleindavg($peerassessment, $group, $member);

        $peermarks[$member->id] = new stdClass();
        $peermarks[$member->id]->userid = $member->id;
        $peermarks[$member->id]->standarddev = $standarddev;

        if ($peermarks[$member->id]->standarddev <= get_config('peerassessment', 'standard_deviation')) {
            $peermarks[$member->id]->indaverage = $indaverage;
        } else {
            $peermarks[$member->id]->indaverage = 0;
        }
    }

    // THIS CAN'T BE DONE UNTIL INDIVIDUAL AVERAGES ARE ALL SET TO INDAV OR 0. NEEDS TO BE A SEPARATE FOREACH.
    foreach ($members as $member) {

        $averagetotal = $averagetotal + $peermarks[$member->id]->indaverage;
        if ($peermarks[$member->id]->standarddev <= get_config('peerassessment', 'standard_deviation')) {
            $count = $count + 1;
        }
    }

    $groupaverage = 0;
    $groupaverage = $averagetotal / $count;

    foreach ($members as $member) {

        if ($peermarks[$member->id]->standarddev > get_config('peerassessment', 'standard_deviation')) {
            $peermarks[$member->id]->indaverage = round($groupaverage, 2);
        }
    }

    return $peermarks[$thisperson->id]->indaverage;

}


function peerassessment_get_indsd($peerassessment, $group, $user) {
    global $DB;

    $count = peerassessment_get_indcount($peerassessment, $group, $user);

    if ($count == 0) {
        return '-';
    }

    $peergrades = peerassessment_get_indpeergrades($peerassessment, $group, $user);

    $result = peerassessment_get_pstd_dev($peergrades);

    return round($result, 2);
}

function peerassessment_get_grade($peerassessment, $group, stdClass $member) {
    global $DB;

    // Can't calculate grade if student does not belong to any group.
    if (!$group) {
        return null;
    }

    if ($peerassessment->calculationtype == PEERASSESSMENT_SIMPLE) {
        $grade = peerassessment_get_simple_grade($peerassessment, $group, $member);
    } else if ($peerassessment->calculationtype == PEERASSESSMENT_OUTLIER) {
        $grade = peerassessment_get_outlier_adjusted_grade($peerassessment, $group, $member);
    } else if($peerassessment->calculationtype == PEERASSESSMENT_WEBPA) {
        $grade = peerassessment_get_simple_grade($peerassessment, $group, $member); // TODO
    } else {
        return null;
    }

    return $grade;
}

/**
 * Perform the calculation of a users final grade using the 'simple' calculation.
 * 
 * @return number
 */
function peerassessment_get_simple_grade($peerassessment, $group, stdClass $member) {
    global $CFG, $DB;
    //error_log("peerassessment_get_simple_grade for " . print_r($member,true) );
    $thisperson = $member;

    $peermarks = array();

    // Can't calculate grade if student does not belong to any group.
    if (!$group) {
        return null;
    }

    // $multiply = get_config('peerassessment', 'multiplyby');
    $multiplier = 5;
    $gravg = peerassessment_get_simplegravg($peerassessment, $group);
    $submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $group->id));
  
    if (empty($submission) || !isset($submission->grade) || is_nan($gravg) ) {
        return '-';
    }

    $members = groups_get_members($group->id);
    foreach ($members as $member) {

        $peermarks[$member->id] = new stdClass();
        $peermarks[$member->id]->userid = $member->id;
        $peermarks[$member->id]->indaverage = peerassessment_get_simpleindavg($peerassessment, $group, $member);
        $peermarks[$member->id]->final_grade = $submission->grade + (($peermarks[$member->id]->indaverage - $gravg) * $multiplier);
    }

    $grade = $peermarks[$thisperson->id]->final_grade;

    if ($grade > 100) {
        $grade = 100;
    }

    if ($grade < 0) {
        $grade = 0;
    }

    return $grade;
}

/**
 * Description
 * @param type $peerassessment 
 * @param type $group 
 * @param stdClass $member 
 * @return type
 */
function peerassessment_get_outlier_adjusted_grade($peerassessment, $group, stdClass $member) {
    global $CFG, $DB;

    $thisperson = $member;

    $peermarks = array();

    // Can't calculate grade if student does not belong to any group.
    if (!$group) {
        return null;
    }

    // $multiply = get_config('peerassessment', 'multiplyby');
    $multiplier = 4;
    $indavg = peerassessment_get_simpleindavg($peerassessment, $group, $member);
    $groupaverage = peerassessment_get_groupaverage($peerassessment, $group);
    $submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $group->id));

    if (!$submission || !isset($submission->grade)) {
        return '-';
    }

    $members = groups_get_members($group->id);
    foreach ($members as $member) {
        $standarddev = peerassessment_get_indsd($peerassessment, $group, $member);
        $indaverage = peerassessment_get_simpleindavg($peerassessment, $group, $member);

        $peermarks[$member->id] = new stdClass();
        $peermarks[$member->id]->userid = $member->id;
        $peermarks[$member->id]->standarddev = $standarddev;

        if ($peermarks[$member->id]->standarddev <= get_config('peerassessment', 'standard_deviation')) {
            $peermarks[$member->id]->indaverage = $indaverage;
        } else {
            $peermarks[$member->id]->indaverage = 0;
        }
    }

    foreach ($members as $member) {

        if ($peermarks[$member->id]->standarddev > get_config('peerassessment', 'standard_deviation')) {
            $peermarks[$member->id]->indaverage = $groupaverage;
        }

        $peermarks[$member->id]->mm = round(($peermarks[$member->id]->indaverage - $groupaverage) * $multiplier, 2);

        if (abs($peermarks[$member->id]->mm) < get_config('peerassessment', 'moderation')) {
            $peermarks[$member->id]->mm = 0;
        }

        $peermarks[$member->id]->final_grade = $submission->grade + $peermarks[$member->id]->mm;
    }

    $grade = $peermarks[$thisperson->id]->final_grade;

    if ($grade > 100) {
        $grade = 100;
    }

    if ($grade < 0) {
        $grade = 0;
    }

    return $grade;
}

/**
 * Fill up missing assessments with grade "0"
 */
function peerassessment_fillup() {

}

function peerassessment_submission_files($context, $group) {
    $allfiles = array();
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_peerassessment', 'submission', $group->id, 'sortorder', false)) {
        foreach ($files as $file) {
            $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

            $allfiles[] = "<a href='$fileurl'>" . $file->get_filename() . '</a>';
        }
    }
    return $allfiles;
}


function peerassessment_feedback_files($context, $group) {
    $allfiles = array();
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_peerassessment', 'feedback_files', $group->id, 'sortorder', false)) {
        foreach ($files as $file) {
            $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

            $allfiles[] = "<a href='$fileurl'>" . $file->get_filename() . '</a>';
        }
    }
    return $allfiles;
}

/**
 * Total all the grades awarded by the $user to other members of the group.
 * Return a structure that can be used to visualise the progress made in providing marks to peers in the group. 
 */
function peerassessment_grade_by_user($peerassessment, $user, $membersgradeable) {
    global $DB;

    $data = new stdClass(); // data->grade[member]
    $data ->grade = array();
    $data ->feedback = array();
    
    $mygrades = $DB->get_records('peerassessment_peers', array('peerassessment' => $peerassessment->id,
        'gradedby' => $user->id), '', 'id,sort,gradefor,feedback,grade');
    
    foreach( $mygrades as $grade) {
        
        $peerid = $grade ->gradefor;
        @$data->grade[$peerid] += $grade->grade; // @suppress "Undefined index" error reports
        @$data->feedback[$peerid] |= $grade->feedback;
    }
    
    // Make sure all the peers have an entry in the returning data array.
    foreach ($membersgradeable as $member) {
        if ( !array_key_exists( $member->id, $data ->grade ) ) {
            $data ->grade[$member->id] = '-';
        }
        if ( !array_key_exists( $member->id, $data ->feedback ) ) {
            $data ->feedback[$member->id] = '-';
        }
    }
    return $data;
}

function peerassessment_get_fileoptions($peerassessment) {
    return array('mainfile' => '', 'subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => $peerassessment->maxfiles,
        'accepted_types' => '*', 'return_types' => null);
}


/**
 * Find members of the group that did not submit feedback and graded peers.
 * @param $peerassessment
 * @param $group
 */
function peerassessment_outstanding($peerassessment, $group) {
    global $DB;

    $members = groups_get_members($group->id);
    foreach ($members as $k => $member) {
        if ($DB->get_record('peerassessment_peers', array('peerassessment' => $peerassessment->id, 'groupid' => $group->id,
            'gradedby' => $member->id), 'id', IGNORE_MULTIPLE)) {
            unset($members[$k]);
        }

    }
    return $members;
}

function peerassessment_teachers($context) {
    global $CFG;

    $contacts = array();
    if (empty($CFG->coursecontact)) {
        return $contacts;
    }
    $coursecontactroles = explode(',', $CFG->coursecontact);
    foreach ($coursecontactroles as $roleid) {
        $contacts += get_role_users($roleid, $context, true);
    }
    return $contacts;
}
/**
 * Student has provided some grades on their peers using the add_submission_form, save into database.
 * 
 */
function peerassessment_save($peerassessment, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable) {
    global $USER, $DB;

    // Form has been submitted, commit, display confirmation and redirect.
    // Create submission record if none yet.
    if (!$submission) {
        $submission = new stdClass();
        $submission->assignment = $peerassessment->id;
        $submission->userid = $USER->id;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $submission->groupid = $group->id;

        $submission->id = $DB->insert_record('peerassessment_submission', $submission);

        $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array('groupid' => $group->id)
            );

        $event = \mod_peerassessment\event\submission_created::create($params);
        $event->trigger();
    } else {
        // Just update.
        $submission->timemodified = time();
        $DB->update_record('peerassessment_submission', $submission);

        $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array('groupid' => $group->id)
            );

        $event = \mod_peerassessment\event\submission_updated::create($params);
        $event->add_record_snapshot('peerassessment_submission', $submission);
        $event->trigger();
    }

    // Save the file submitted.
    // Check if the file is different or the same.
    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

    // Check special case when there were no files at the time of submission and none were added.
    $skipfile = false;
    if ($data->files == 0 && count($draftfiles) == 0) {
        $skipfile = true;
    }

    if (!$skipfile) {
        // Get all contenthashes being submitted.
        $newhashes = array();
        foreach ($draftfiles as $file) {
            $newhashes[$file->get_contenthash()] = $file->get_contenthash();
        }

        // Get all contenthashes that are already submitted.
        $files = $fs->get_area_files($context->id, 'mod_peerassessment', 'submission', $group->id, 'sortorder', false);
        $oldhashes = array();
        foreach ($files as $file) {
            $oldhashes[$file->get_contenthash()] = $file->get_contenthash();
        }

        $samehashes = array_intersect($newhashes, $oldhashes);
        $addedhashes = array_diff($newhashes, $oldhashes);
        $deletedhashes = array_diff($oldhashes, $newhashes);

        $filesubmissioncount = count($newhashes);
        $filelist = array();
        $filedeletedcount = count($deletedhashes);

        if ($samehashes) {
            $filelist[] = ' Resubmitted:<br/>' . join('<br/>', $samehashes);
        }

        if ($addedhashes) {
            $filelist[] = ' Added:<br/>' . join('<br/>', $addedhashes);
        }

        if ($deletedhashes) {
            $deletedlist = 'Deleted:<br/>' . join('<br/>', $deletedhashes);
        }

        $filelist = join('<br/>', $filelist);

        if ($deletedhashes) {
            $params = array(
                    'objectid' => $submission->id,
                    'context' => $context,
                    'other' => array(
                        'filedeletedcount' => $filedeletedcount,
                        'deletedlist' => $deletedlist
                        )
                );

            $event = \mod_peerassessment\event\submission_files_deleted::create($params);
            $event->trigger();
        }

        if ($filelist) {
            $params = array(
                    'objectid' => $submission->id,
                    'context' => $context,
                    'other' => array(
                        'filesubmissioncount' => $filesubmissioncount,
                        'filelist' => $filelist
                        )
                );

            $event = \mod_peerassessment\event\submission_files_uploaded::create($params);
            $event->trigger();
        }

        if (count($newhashes) && $oldhashes != $newhashes) {
            // Hashes are different, submission has changed.
            $submission->submissionmodified = time();
            $submission->submissionmodifiedby = $USER->id;

            $DB->update_record('peerassessment_submission', $submission);
        }

        file_save_draft_area_files($draftitemid, $context->id, 'mod_peerassessment', 'submission', $group->id,
            peerassessment_get_fileoptions($peerassessment));
    }

    // Remove existing grades, in case it's an update.
    $DB->delete_records('peerassessment_peers',
        array('peerassessment' => $peerassessment->id, 'groupid' => $group->id, 'gradedby' => $USER->id));

    error_log("peerassessment_save() incoming data is " . print_r($data,true) );
    
    // Save the grades and feedback for your peers against each criteria.
    // Only save against criteria that have been specified otherwise we create a lot of "criteria#4 = grade zero" records
    $peerassessment_criteria = new peerassessment_criteria($peerassessment->id);
    $criterias = $peerassessment_criteria ->getCriteria(); // id => record
    $sorts = array();
    foreach ($criterias as $id => $record) {
        $sorts[] = $record->sort;
    }
    error_log("peerassessment_save() criteria=" . print_r($criterias,true) . " sort=". print_r($sorts, true));
 
    foreach ($sorts as $key => $sort) {
    
        foreach ($membersgradeable as $member) {

            $peer = new stdClass();
            $peer->peerassessment = $peerassessment->id;
            $peer->sort = $sort;
            $peer->groupid = $group->id;
            $peer->gradedby = $USER->id;
            $peer->gradefor = $member->id;
            $peer->timecreated = time();
            
            $field = 'grade__idx_'. $sort;
            if (isset($data->{$field}[$peer->gradefor]) ) {
                $peer->grade = $data->{$field}[$peer->gradefor];
            } else {
                $peer->grade = 0;
            }
            $field = 'feedback__idx_'. $sort;
            if (isset($data->{$field}[$peer->gradefor]) ) {
                $peer->feedback = $data->{$field}[$peer->gradefor];
            } 
            error_log("peerassessment_save() saving data field=" . print_r($peer,true) );
            $ret = $DB->insert_record('peerassessment_peers', $peer, true);
            error_log( "indent record returned $ret");
        }
// TODO add a log entry
//         $fullname = fullname($member);

//         $params = array(
//                 'objectid' => $peer->id,
//                 'context' => $context,
//                 'relateduserid' => $member->id,
//                 'other' => array(
//                     'grade' => $peer->grade,
//                     'fullname' => $fullname
//                     )
//             );

//         $event = \mod_peerassessment\event\peer_grade_created::create($params);
//         $event->add_record_snapshot('peerassessment_peers', $peer);
//         $event->trigger();
    }

    // Send email confirmation.
    if (!mail_confirmation_submission($course, $submission, $draftfiles, $membersgradeable, $data)) {
        throw new Exception("Submission saved but no email sent.");
    }
}

function mail_confirmation_submission($course, $submission, $draftfiles, $membersgradeable, $data) {
    global $CFG, $USER;
error_log("mail_confirmation_submission TODO " . print_r($data,true));
return true;

    $subject = get_string('confirmationmailsubject', 'peerassessment', $course->fullname);

    $a = new stdClass();
    $a->time = userdate(time());

    $files = array();
    foreach ($draftfiles as $draftfile) {
        $files[] = $draftfile->get_filename();
    }
    $a->files = implode("\n", $files);

    $grades = '';
    foreach ($membersgradeable as $member) {
        $grades .= fullname($member) . ': ' . $data->grade[$member->id] . "\n";
    }
    $a->grades = $grades;

    $a->url = $CFG->wwwroot . "/mod/peerassessment/view.php?n=" . $submission->assignment;

    $body = get_string('confirmationmailbody', 'peerassessment', $a);
    return email_to_user($USER, core_user::get_noreply_user(), $subject, $body);
}

function peerassessment_get_pstd_dev(array $a, $sample = false) {
    $n = count($a);

    if ($n === 0) {
        trigger_error("The array has zero elements", E_USER_WARNING);
        return false;
    }
    if ($sample && $n === 1) {
        trigger_error("The array has only 1 element", E_USER_WARNING);
        return false;
    }
    $mean = array_sum($a) / $n;

    $carry = 0.0;
    foreach ($a as $val) {
        $d = ((double) $val) - $mean;

        $carry += ($d * $d);
    }

    if ($sample) {
        --$n;
    }

    return sqrt($carry / $n);
}
