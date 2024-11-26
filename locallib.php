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
 * Local lib.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('PEERWORK_STATUS_NOT_SUBMITTED', 0);
define('PEERWORK_STATUS_SUBMITTED', 1);
define('PEERWORK_STATUS_GRADED', 2);
define('PEERWORK_STATUS_NOT_SUBMITTED_CLOSED', 3);
define('PEERWORK_STATUS_RELEASED', 4);

define('PEERWORK_DUEDATE_NOT_USED', 0);
define('PEERWORK_DUEDATE_OK', 1);
define('PEERWORK_DUEDATE_PASSED', 2);

define('PEERWORK_FROMDATE_NOT_USED', 0);
define('PEERWORK_FROMDATE_OK', 1);
define('PEERWORK_FROMDATE_BEFORE', 2);

define('MOD_PEERWORK_JUSTIFICATION_DISABLED', 0);       // No justification required.
define('MOD_PEERWORK_JUSTIFICATION_HIDDEN', 1);         // Justification hidden to students and peers.
define('MOD_PEERWORK_JUSTIFICATION_VISIBLE_ANON', 2);   // Justification visible to all but anonymously.
define('MOD_PEERWORK_JUSTIFICATION_VISIBLE_USER', 3);   // Justification visible to all with identity visible.

define('MOD_PEERWORK_JUSTIFICATION_SUMMARY', 0);       // Single justification for grades.
define('MOD_PEERWORK_JUSTIFICATION_CRITERIA', 1);      // Justification for each criteria.

define('MOD_PEERWORK_PEER_GRADES_HIDDEN', 0);           // Peer grades hidden to students.
define('MOD_PEERWORK_PEER_GRADES_VISIBLE_ANON', 2);     // Peer grades visible to all but anonymously.
define('MOD_PEERWORK_PEER_GRADES_VISIBLE_USER', 3);     // Peer grades visible to all with identity visible.

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/grouplib.php');

/**
 * Get peers.
 *
 * @param stdClass $course The course.
 * @param stdClass $peerwork The instance.
 * @param int $groupingid The grouping ID.
 * @param int $group The group ID.
 * @param int $userid The ID of the user that is retrieving its peers.
 * @return array
 */
function peerwork_get_peers($course, $peerwork, $groupingid, $group = null, $userid = null) {
    global $USER;
    $userid = !$userid ? $USER->id : $userid;

    if (!$group) {
        $group = peerwork_get_mygroup($course, $userid, $groupingid);
    }

    $members = groups_get_members($group);
    $membersgradeable = $members;

    if (!$peerwork->selfgrading) {
        unset($membersgradeable[$userid]);
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
 * @return int|null The group id.
 */
function peerwork_get_mygroup($courseid, $userid, $groupingid = 0, $die = true) {
    global $CFG;

    $mygroups = groups_get_all_groups($courseid, $userid, $groupingid);

    if (count($mygroups) == 0) {
        if ($die) {
            throw new moodle_exception('youdonotbelongtoanygroup', 'mod_peerwork');
        }
        return null;
    } else if (count($mygroups) > 1) {
        if ($die) {
            throw new moodle_exception('youbelongtomorethanonegroup', 'mod_peerwork');
        }
        return null;
    }

    $mygroup = array_shift($mygroups);
    return $mygroup->id;
}

/**
 * Gets the status, one of PEERWORK_STATUS_*.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $group The group.
 * @param stdClass $submission The submission, if already known.
 * @return object Returns the status.
 */
function peerwork_get_status($peerwork, $group, $submission = null) {
    global $DB;

    if ($submission === null) {
        $submission = $DB->get_record('peerwork_submission', ['peerworkid' => $peerwork->id, 'groupid' => $group->id]);
    }

    if ($submission && ($submission->peerworkid != $peerwork->id || $submission->groupid != $group->id)) {
        throw new coding_exception('Invalid submission object');
    }

    $status = new stdClass();
    $duedate = peerwork_due_date($peerwork);

    if ($submission && $submission->released) {
        $status->code = PEERWORK_STATUS_RELEASED;
        $user = $DB->get_record('user', array('id' => $submission->releasedby));
        $status->text = get_string('releasedbyon', 'mod_peerwork', [
            'name' => fullname($user),
            'date' => userdate($submission->released)
        ]);
        return $status;
    }

    if ($submission && $submission->timegraded) {
        $status->code = PEERWORK_STATUS_GRADED;
        $user = $DB->get_record('user', array('id' => $submission->gradedby));
        $status->text = get_string('gradedbyon', 'mod_peerwork', [
            'name' => fullname($user),
            'date' => userdate($submission->timegraded)
        ]);
        return $status;
    }

    if (!$submission && $duedate == PEERWORK_DUEDATE_PASSED) {
        $status->code = PEERWORK_STATUS_NOT_SUBMITTED_CLOSED;
        $text = get_string('nothingsubmittedyetduedatepassednago', 'mod_peerwork', format_time(time() - $peerwork->duedate));
        $status->text = $text;
        return $status;
    }

    if (!$submission) {
        $status->code = PEERWORK_STATUS_NOT_SUBMITTED;
        $status->text = get_string('nothingsubmittedyet', 'mod_peerwork');
        return $status;
    }

    $modified = '';

    if ($submission->timecreated != $submission->timemodified) {
        $modified = get_string(
            'lasteditedon', 'mod_peerwork',
            [
                'date' => userdate($submission->timecreated)
            ]
        );
    }

    if ($duedate == PEERWORK_DUEDATE_PASSED) {
        $user = $DB->get_record('user', array('id' => $submission->userid));
        $status->code = PEERWORK_STATUS_SUBMITTED;
        $status->text =
            get_string(
                'firstsubmittedbyon', 'mod_peerwork',
                [
                    'name' => fullname($user),
                    'date' => userdate($submission->timecreated)
                ]
            ) . $modified .
            ' ' .
            get_string(
                'duedatepassedago', 'mod_peerwork',
                format_time(time() - $peerwork->duedate)
            );
        $latepeers = mod_peerwork_get_late_peers($peerwork, $submission);

        if (!empty($latepeers)) {
            $status->text .= ' ' . html_writer::tag('span', get_string('thesestudentspastduedate', 'mod_peerwork', implode(', ',
                array_map(function($peer) {
                    return get_string('studentondate', 'mod_peerwork', [
                        'fullname' => fullname($peer),
                        'date' => userdate($peer->timegraded, get_string('strftimedatetimeshort', 'core_langconfig'))
                    ]);
                }, $latepeers)
            )), ['class' => 'submitted-past-due-date']);
        }

        return $status;

    } else {
        $user = $DB->get_record('user', array('id' => $submission->userid));
        $status->code = PEERWORK_STATUS_SUBMITTED;
        $status->text =
            get_string(
                'firstsubmittedbyon', 'mod_peerwork',
                [
                    'name' => fullname($user),
                    'date' => userdate($submission->timecreated)
                ]
            ) . $modified;
        return $status;
    }
}

/**
 * Determine if there are any submissions.
 *
 * @param course_module $cm
 * @return bool
 */
function peerwork_has_submissions($cm) {
    global $DB;

    $peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);
    $hassubmissions = $DB->record_exists('peerwork_submission', ['peerworkid' => $peerwork->id]);

    return $hassubmissions;
}

/**
 * Determine if there are any submissions with grades released.
 *
 * @param course_module $cm
 * @return bool
 */
function peerwork_has_released_grades($cm) {
    global $DB;

    $peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);
    $sql = 'peerworkid = :peerworkid AND timegraded > 0 AND released > 0';
    $gradesreleased = $DB->record_exists_select('peerwork_submission', $sql, ['peerworkid' => $peerwork->id]);

    return $gradesreleased;
}

/**
 * Get the justifications.
 *
 * @param int $peerworkid The peerwork ID.
 * @param int $groupid The group ID.
 * @return Array indexed by grader, then graded.
 */
function peerwork_get_justifications($peerworkid, $groupid) {
    global $DB;
    $justifications = $DB->get_records('peerwork_justification', ['peerworkid' => $peerworkid, 'groupid' => $groupid]);
    return array_reduce($justifications, function($carry, $row) {
        if (!isset($carry[$row->gradedby])) {
            $carry[$row->gradedby][$row->criteriaid] = [];
        }
        $carry[$row->gradedby][$row->criteriaid][$row->gradefor] = $row;
        return $carry;
    }, []);
}

/**
 * Get the justifications received.
 *
 * @param int $peerworkid The peerwork ID.
 * @param int $groupid The group ID.
 * @param int $userid The user ID.
 * @return Array indexed by grader
 */
function peerwork_get_justifications_received($peerworkid, $groupid, $userid) {
    global $DB;
    $justifications = $DB->get_records('peerwork_justification', [
        'peerworkid' => $peerworkid,
        'groupid' => $groupid,
        'gradefor' => $userid
    ]);
    return array_reduce($justifications, function($carry, $row) {
        $carry[$row->criteriaid][$row->gradedby] = $row;
        return $carry;
    }, []);
}

/**
 * Get the peer grades.
 *
 * @param int $peerworkid The peerwork ID.
 * @param int $groupid The group ID.
 * @param int $userid The user ID.
 * @return Array indexed by criteriaid, then graderid.
 */
function peerwork_get_peer_grades_received($peerworkid, $groupid, $userid) {
    global $DB;
    $peergrades = $DB->get_records('peerwork_peers', [
        'peerwork' => $peerworkid,
        'groupid' => $groupid,
        'gradefor' => $userid
    ]);
    return array_reduce($peergrades, function($carry, $row) {
        if (!isset($carry[$row->criteriaid])) {
            $carry[$row->criteriaid] = [];
        }
        $carry[$row->criteriaid][$row->gradedby] = $row;
        return $carry;
    }, []);
}

/**
 * Was due date used and has it passed?
 *
 * @param stdClass $peerwork The instance.
 * @return int PEERWORK_DUEDATE_* constant.
 */
function peerwork_due_date($peerwork) {
    if (!$peerwork->duedate) {
        return PEERWORK_DUEDATE_NOT_USED;
    }

    if ($peerwork->duedate < time()) {
        return PEERWORK_DUEDATE_PASSED;
    } else {
        return PEERWORK_DUEDATE_OK;
    }
}

/**
 * Was from date used and is it after?
 *
 * @param stdClass $peerwork The instance.
 * @return int PEERWORK_FROMDATE_* constant.
 */
function peerwork_from_date($peerwork) {
    if (!$peerwork->fromdate) {
        return PEERWORK_FROMDATE_NOT_USED;
    }

    if ($peerwork->fromdate > time()) {
        return PEERWORK_FROMDATE_BEFORE;
    } else {
        return PEERWORK_FROMDATE_OK;
    }
}

/**
 * Whether the student can view their grade and feedback.
 *
 * @param stdClass $status The status.
 * @param array $gradinginfo Array of grade information objects.
 *
 * @return bool
 */
function peerwork_can_student_view_grade_and_feedback_from_status($status, $gradinginfo) {
    global $USER;

    $hidden = false;

    if ($gradinginfo &&
        isset($gradinginfo->items[0]->grades[$USER->id]) &&
        $gradinginfo->items[0]->grades[$USER->id]->hidden
    ) {
        $hidden = true;
    }

    return $status->code == PEERWORK_STATUS_RELEASED && !$hidden;
}

/**
 * Return whether students can view their peer grades.
 *
 * @param stdClass $peerwork The peerwork instance.
 * @return bool
 */
function peerwork_can_students_view_peer_grades($peerwork) {
    return in_array($peerwork->peergradesvisibility, [
        MOD_PEERWORK_PEER_GRADES_VISIBLE_ANON,
        MOD_PEERWORK_PEER_GRADES_VISIBLE_USER,
    ]);
}

/**
 * Return whether students can view their peer justifications.
 *
 * @param stdClass $peerwork The peerwork instance.
 * @return bool
 */
function peerwork_can_students_view_peer_justification($peerwork) {
    return in_array($peerwork->justification, [
        MOD_PEERWORK_JUSTIFICATION_VISIBLE_ANON,
        MOD_PEERWORK_JUSTIFICATION_VISIBLE_USER,
    ]);
}

/**
 * Whether the submission was graded, from its status.
 *
 * @param stdClass $status The status.
 * @return bool
 */
function peerwork_was_submission_graded_from_status($status) {
    return in_array($status->code, [PEERWORK_STATUS_GRADED, PEERWORK_STATUS_RELEASED]);
}

/**
 * Can student $user submit/edit based on the current status?
 *
 * @param stdClass $peerwork The instance.
 * @param int $groupid The group ID.
 * @return object
 */
function peerwork_is_open($peerwork, $groupid = 0) {
    global $DB;
    $status = new stdClass();
    $status->code = false;

    // Is it before from date?
    $fromdate = peerwork_from_date($peerwork);
    if ($fromdate == PEERWORK_FROMDATE_BEFORE) {
        $status->text = get_string('assessmentnotopenyet', 'mod_peerwork');
        return $status;
    }

    $course = $DB->get_record('course', array('id' => $peerwork->course), '*', MUST_EXIST);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);

    // Is it already graded?
    $pstatus = peerwork_get_status($peerwork, $group);
    if (peerwork_was_submission_graded_from_status($pstatus)) {
        $status->text = get_string('assessmentalreadygraded', 'mod_peerwork');
        return $status;
    }

    // Is it after due date?
    $duedate = peerwork_due_date($peerwork);
    if ($duedate == PEERWORK_DUEDATE_PASSED) {
        if ($peerwork->allowlatesubmissions) {
            $status->code = true;
            $status->text = get_string('latesubmissionsallowedafterduedate', 'mod_peerwork');
        } else {
            $status->text = get_string('latesubmissionsnotallowedafterduedate', 'mod_peerwork');
        }
        return $status;
    }

    // If we are here it means it's between from date and due date.
    $status->code = true;
    $status->text = get_string('assessmentopen', 'mod_peerwork');
    return $status;
}

/**
 * Get grades for all peers in a group.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $group The group.
 * @param stdClass[] $membersgradeable The members that are gradeable.
 * @param bool $full Whether to return a full result.
 * @return object
 */
function peerwork_get_peer_grades($peerwork, $group, $membersgradeable = null, $full = true) {
    global $DB;

    $return = new stdClass();
    $calculator = calculator_instance($peerwork);

    $peers = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id, 'groupid' => $group->id));
    $grades = [];
    $overrides = [];
    $feedback = [];
    $comments = [];

    foreach ($peers as $peer) {
        $grades[$peer->criteriaid][$peer->gradedby][$peer->gradefor] = $peer->grade;
        $overrides[$peer->criteriaid][$peer->gradedby][$peer->gradefor] = $peer->peergrade;
        $feedback[$peer->criteriaid][$peer->gradedby][$peer->gradefor] = $peer->feedback;
        $comments[$peer->criteriaid][$peer->gradedby][$peer->gradefor] = $peer->comments;
    }

    // Translate the scales to grades.
    $grades = $calculator->translate_scales_to_scores($grades);
    $overrides = $calculator->translate_scales_to_scores($overrides);

    // Anything not proceessed about gets a default string.
    if ($full) {
        foreach (array_keys($grades) as $critid) {
            foreach ($membersgradeable as $member1) {
                if (!isset($grades[$critid][$member1->id])) {
                    $grades[$critid][$member1->id] = [];
                }
                if (!isset($overrides[$critid][$member1->id])) {
                    $overrides[$critid][$member1->id] = [];
                }
                foreach ($membersgradeable as $member2) {
                    if (!isset($grades[$critid][$member1->id][$member2->id])) {
                        $grades[$critid][$member1->id][$member2->id] = '-';
                    }
                    if (!isset($overrides[$critid][$member1->id][$member2->id])) {
                        $overrides[$critid][$member1->id][$member2->id] = '-';
                    }
                    if (!isset($feedback[$critid][$member1->id][$member2->id])) {
                        $feedback[$critid][$member1->id][$member2->id] = '-';
                    }
                    if (!isset($comments[$critid][$member1->id][$member2->id])) {
                        $comments[$critid][$member1->id][$member2->id] = '-';
                    }
                }
            }
        }
    }

    $return->grades = $grades;
    $return->overrides = $overrides;
    $return->feedback = $feedback;
    $return->comments = $comments;

    return $return;
}

/**
 * Get the number of peers graded.
 *
 * @param int $peerworkid The intance.
 * @param int $groupid The group ID.
 * @param int $userid Optionally, to get the number of grades rated by this user.
 * @return int The number.
 */
function peerwork_get_number_peers_graded($peerworkid, $groupid, $userid = null) {
    global $DB;

    $sql = 'peerwork = ? AND groupid = ?';
    $params = [$peerworkid, $groupid];

    if (!empty($userid)) {
        $sql .= ' AND gradedby = ?';
        $params[] = $userid;
    }

    return $DB->count_records_select('peerwork_peers', $sql, $params, 'COUNT(DISTINCT gradedby)');
}

/**
 * Calculate and return the PA result, but cached for the request.
 *
 * @param stdClass $peerwork The module instance.
 * @param stdClass $group The group.
 * @param stdClass $submission The submission, to prevent a double fetch.
 * @return mod_peerwork\pa_result|null Null when the submission was not found or graded.
 */
function peerwork_get_cached_pa_result($peerwork, $group, $submission = null) {
    return peerwork_get_pa_result($peerwork, $group, $submission);
}

/**
 * Calculate and return the PA result.
 *
 * @param stdClass $peerwork The module instance.
 * @param stdClass $group The group.
 * @param stdClass $submission The submission, to prevent a double fetch.
 * @param bool $beforeoverride Whether to fetch the results before any teacher override.
 * @return mod_peerwork\pa_result|null Null when the submission was not found or graded.
 */
function peerwork_get_pa_result($peerwork, $group, $submission = null, $beforeoverride = false) {
    global $DB;

    if (!$submission) {
        $submission = $DB->get_record('peerwork_submission', [
            'peerworkid' => $peerwork->id,
            'groupid' => $group->id
        ]);
    }

    if (!$submission || !isset($submission->grade)) {
        return;
    } else if ($submission->groupid != $group->id || $submission->peerworkid != $peerwork->id) {
        throw new coding_exception('Invalid submission provided');
    }

    $groupmark = $submission->grade;
    $paweighting = $submission->paweighting / 100;
    $noncompletionpenalty = $peerwork->noncompletionpenalty / 100;
    $selfgrade = $peerwork->selfgrading;

    $marks = [];
    $members = groups_get_members($group->id);

    foreach ($members as $member) {
        $awarded = peerwork_grades_by_user($peerwork, $member, $members, $beforeoverride);
        $marks[$member->id] = array_filter($awarded->grade, function($grade) {
            return is_array($grade);
        });
    }

    $calculator = calculator_instance($peerwork);

    return $calculator->calculate($marks, $groupmark, $noncompletionpenalty, $paweighting, $selfgrade);
}

/**
 * Create HTML links to files that have been submitted to the peerworkment.
 *
 * @param context $context The context.
 * @param stdClass $group The group.
 * @return string[] Array of formatted HTML strings.
 */
function peerwork_submission_files($context, $group) {
    $allfiles = array();
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_peerwork', 'submission', $group->id, 'sortorder', false)) {
        foreach ($files as $file) {
            $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());

            $allfiles[] = "<a href='$fileurl'>" . s($file->get_filename()) . '</a>';
        }
    }
    return $allfiles;
}

/**
 * Get feedback files.
 *
 * @param context $context The context.
 * @param stdClass $group The group.
 * @return string[]
 */
function peerwork_feedback_files($context, $group) {
    $allfiles = array();
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'mod_peerwork', 'feedback_files', $group->id, 'sortorder', false)) {
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
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $user The user.
 * @param stdClass[] $membersgradeable The user's peers.
 */
function peerwork_grade_by_user($peerwork, $user, $membersgradeable) {
    global $DB;

    $data = new stdClass();
    $data->grade = array();
    $data->feedback = array();

    $mygrades = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id,
        'gradedby' => $user->id), '', 'id,criteriaid,gradefor,feedback,grade');

    foreach ($mygrades as $grade) {
        $peerid = $grade->gradefor;
        @$data->grade[$peerid] += $grade->grade;
        @$data->feedback[$peerid] |= $grade->feedback;
    }

    // Make sure all the peers have an entry in the returning data array.
    foreach ($membersgradeable as $member) {
        if (!array_key_exists( $member->id, $data->grade)) {
            $data->grade[$member->id] = '-';
        }
        if (!array_key_exists( $member->id, $data->feedback)) {
            $data->feedback[$member->id] = '-';
        }
    }
    return $data;
}

/**
 * All the grades awarded by the $user to other members of the group.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $user The user.
 * @param stdClass[] $membersgradeable The user's peers.
 * @param bool $beforeoverride Whether to fetch the results before any teacher override.
 * @return array $data
 */
function peerwork_grades_by_user($peerwork, $user, $membersgradeable, $beforeoverride) {
    global $DB;

    $data = new stdClass();
    $data->grade = [];

    $mygrades = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id,
        'gradedby' => $user->id), '', 'id,criteriaid,gradefor,grade,peergrade');

    foreach ($mygrades as $grade) {
        $peerid = $grade->gradefor;

        if (!array_key_exists($peerid, $membersgradeable)) {
            continue;
        }

        if ($beforeoverride) {
            $data->grade[$peerid][] = $grade->peergrade;
        } else {
            $data->grade[$peerid][] = $grade->grade;
        }
    }

    return $data;
}

/**
 * All the grades and the overrides awarded by the $user/teacher
 * to other members of the group.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $user The user.
 * @param stdClass[] $membersgradeable The user's peers.
 * @return array $data
 */
function peerwork_grades_overrides_by_user($peerwork, $user, $membersgradeable) {
    global $DB;

    $data = new stdClass();
    $data->grade = [];
    $data->feedback = [];

    $mygrades = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id,
        'gradedby' => $user->id), '', 'id,criteriaid,gradefor,grade,peergrade,comments');

    foreach ($mygrades as $grade) {
        $peerid = $grade->gradefor;
        $criteriaid = $grade->criteriaid;
        $data->grade[$peerid][$criteriaid] = [
            'grade' => $grade->grade,
            'peergrade' => $grade->peergrade,
            'comments' => $grade->comments
        ];
    }

    return $data;
}

/**
 * Get submission file options.
 *
 * @param stdClass $peerwork The instance from database.
 * @return array
 */
function peerwork_get_fileoptions($peerwork) {
    return array('mainfile' => '', 'subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => $peerwork->maxfiles,
        'accepted_types' => '*', 'return_types' => null);
}

/**
 * Find members of the group that did not submit feedback and graded peers.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $group The group.
 * @return object[] The outstanding members.
 */
function peerwork_outstanding($peerwork, $group) {
    global $DB;

    $members = groups_get_members($group->id);
    foreach ($members as $k => $member) {
        if ($DB->get_record('peerwork_peers', array('peerwork' => $peerwork->id, 'groupid' => $group->id,
            'gradedby' => $member->id), 'id', IGNORE_MULTIPLE)) {
            unset($members[$k]);
        }

    }
    return $members;
}

/**
 * Get teachers.
 *
 * @param context $context The context.
 * @return object[]
 */
function peerwork_teachers($context) {
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
 * Get the local grade of a user.
 *
 * @param int $peerworkid The peerwork ID.
 * @param int $submissionid The submission ID.
 * @param int $userid The user ID.
 * @return object|null
 */
function peerwork_get_user_local_grade($peerworkid, $submissionid, $userid) {
    global $DB;
    $record = $DB->get_record('peerwork_grades', [
        'peerworkid' => $peerworkid,
        'submissionid' => $submissionid,
        'userid' => $userid
    ]);

    if (!$record) {
        return null;
    }

    return $record->revisedgrade != null ? $record->revisedgrade : $record->grade;
}

/**
 * Get local grades.
 *
 * @param int $peerworkid The peerwork ID.
 * @param int $submissionid The submission ID.
 * @return array Indexed by userid.
 */
function peerwork_get_local_grades($peerworkid, $submissionid) {
    global $DB;
    $records = $DB->get_records('peerwork_grades', [
        'peerworkid' => $peerworkid,
        'submissionid' => $submissionid
    ], '', '*');
    $userids = array_map(function($record) {
        return $record->userid;
    }, $records);
    return array_combine($userids, $records);
}

/**
 * Update local grades.
 *
 * @param stdClass $peerwork The instance.
 * @param stdClass $group The group.
 * @param stdClass $submission The submission.
 * @param array $userids The list of user IDs.
 * @param array|null $revisedgrades The full list of revised grades indexed by member id. A missing key means not revised.
 *                                  If null, we assume that none should be changed.
 */
function peerwork_update_local_grades($peerwork, $group, $submission, $userids, $revisedgrades = null) {
    global $DB;

    $result = peerwork_get_pa_result($peerwork, $group, $submission);
    $existingrecords = peerwork_get_local_grades($peerwork->id, $submission->id);

    foreach ($userids as $userid) {
        $record = isset($existingrecords[$userid]) ? $existingrecords[$userid] : null;
        if (!$record) {
            $record = (object) [
                'peerworkid' => $peerwork->id,
                'submissionid' => $submission->id,
                'userid' => $userid,
            ];
        }

        $record->score = $result->get_score($userid);
        $record->prelimgrade = $result->get_preliminary_grade($userid);
        $record->grade = $result->get_grade($userid);

        if ($revisedgrades !== null) {
            $record->revisedgrade = $revisedgrades[$userid] ?? null;
        }
        if (!empty($record->id)) {
            $DB->update_record('peerwork_grades', $record);
        } else {
            $DB->insert_record('peerwork_grades', $record);
        }
    }

    if ($submission->released) {
        peerwork_update_grades($peerwork);
    }
}

/**
 * Student has provided some grades on their peers using the add_submission_form, save into database and trigger events.
 *
 * @param unknown $peerwork
 * @param unknown $submission - database record in stdClass
 * @param unknown $group
 * @param unknown $course
 * @param unknown $cm
 * @param unknown $context
 * @param stdClass $data Must include all non-locked data from the submissions form.
 * @param unknown $draftitemid
 * @param unknown $membersgradeable
 * @throws Exception
 */
function peerwork_save($peerwork, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable) {
    global $CFG, $USER, $DB;

    $event = \mod_peerwork\event\assessable_submitted::create(['context' => $context]);
    $event->trigger();

    // Create or update the submission.
    list($submission, $draftfiles) = mod_peerwork_save_submission($peerwork, $submission, $group, $context, $data, $draftitemid);

    $lockedpeerids = mod_peerwork_get_locked_peers($peerwork, $USER->id);
    $peeruserparams = ['peerwork' => $peerwork->id, 'groupid' => $group->id, 'gradedby' => $USER->id];

    // Capture all timecreated to maintain them across saves.
    $uniqid = $DB->sql_concat('criteriaid', "'-'", 'gradefor');
    $origtimecreated = $DB->get_records_menu('peerwork_peers', $peeruserparams, '', "$uniqid, timecreated");

    // Selectively delete all records for peers that are guaranteed not to be locked. We can't just look at the
    // locked status of each row because we consider a user to be locked when one of their entries is locked.
    // The reason why we delete all records in the table is because it's the legacy method that was used.
    $notlockedpeersql = '!= 0';
    $notlockedpeerparams = [];
    if (!empty($lockedpeerids)) {
        list($notlockedpeersql, $notlockedpeerparams) = $DB->get_in_or_equal($lockedpeerids, SQL_PARAMS_NAMED, 'param', false);
    }
    $sql = "peerwork = :peerwork
        AND groupid = :groupid
        AND gradedby = :gradedby
        AND locked = 0
        AND gradefor $notlockedpeersql";
    $DB->delete_records_select('peerwork_peers', $sql, array_merge($peeruserparams, $notlockedpeerparams));

    // Save the grades.
    $pac = new mod_peerwork_criteria($peerwork->id);
    $criteria = $pac->get_criteria();
    foreach ($criteria as $criterion) {
        foreach ($membersgradeable as $member) {

            // Skipped the locked peers. This should theoretically never happen as we
            // should not be receiving data for peers that have been marked locked.
            if (in_array($member->id, $lockedpeerids)) {
                continue;
            }

            $uniqid = "{$criterion->id}-{$member->id}";

            $peer = new stdClass();
            $peer->peerwork = $peerwork->id;
            $peer->criteriaid = $criterion->id;
            $peer->groupid = $group->id;
            $peer->gradedby = $USER->id;
            $peer->gradefor = $member->id;
            $peer->feedback = null;
            $peer->locked = $peerwork->lockediting;
            $peer->timecreated = isset($origtimecreated[$uniqid]) ? $origtimecreated[$uniqid] : time();
            $peer->timemodified = time();
            $field = 'grade_idx_'. $criterion->id;
            if (isset($data->{$field}[$peer->gradefor])) {
                $peer->grade = max(0, (int) $data->{$field}[$peer->gradefor]);
            } else {
                $peer->grade = 0;
            }
            // Save the original peer grade given. Grade may be overridden later.
            $peer->peergrade = $peer->grade;
            $peer->comments = null;
            $peer->overriddenby = null;
            $peer->timeoverridden = null;

            $peer->id = $DB->insert_record('peerwork_peers', $peer, true);

            $params = array(
                'objectid' => $peer->id,
                'context' => $context,
                'relateduserid' => $member->id,
                'other' => array(
                    'grade' => $peer->grade,
                )
            );

            $event = \mod_peerwork\event\peer_grade_created::create($params);
            $event->add_record_snapshot('peerwork_peers', $peer);
            $event->trigger();
        }
    }

    // Save the justification.
    $justificationtype = $peerwork->justificationtype;

    if ($peerwork->justification != MOD_PEERWORK_JUSTIFICATION_DISABLED) {
        foreach ($membersgradeable as $member) {

            // Skip the locked peers.
            if (in_array($member->id, $lockedpeerids)) {
                continue;
            }

            $params = [
                'peerworkid' => $peerwork->id,
                'groupid' => $group->id,
                'gradefor' => $member->id,
                'gradedby' => $USER->id,
                'criteriaid' => 0
            ];
            if ($justificationtype == MOD_PEERWORK_JUSTIFICATION_SUMMARY) {
                $record = $DB->get_record('peerwork_justification', $params);

                if (!$record) {
                    $record = (object) $params;
                }

                $record->justification = trim(isset($data->justifications[$member->id]) ? $data->justifications[$member->id] : '');

                if (!empty($record->id)) {
                    $DB->update_record('peerwork_justification', $record);
                } else {
                    $DB->insert_record('peerwork_justification', $record);
                }
            } else if ($justificationtype == MOD_PEERWORK_JUSTIFICATION_CRITERIA) {
                foreach ($criteria as $id => $criterion) {
                    $params['criteriaid'] = $criterion->id;
                    $record = $DB->get_record('peerwork_justification', $params);

                    if (!$record) {
                        $record = (object) $params;
                    }

                    $text = isset($data->{'justification_' . $id}[$member->id]) ? $data->{'justification_' . $id}[$member->id] : '';
                    $record->justification = trim($text);

                    if (!empty($record->id)) {
                        $DB->update_record('peerwork_justification', $record);
                    } else {
                        $DB->insert_record('peerwork_justification', $record);
                    }
                }
            }
        }
    }

    // Suggest to check, and eventually update, the completion state.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $peerwork->completiongradedpeers) {
        $completion->update_state($cm, COMPLETION_COMPLETE);
    }

    // Send email confirmation.
    if (!mod_peerwork_mail_confirmation_submission($course, $submission, $draftfiles, $membersgradeable, $data)) {
        throw new moodle_exception("Submission saved but no email sent.");
    }
}

/**
 * Teacher has overridden some grades for peers using the override_form, save into database and trigger events.
 *
 * @param int $peerworkid
 * @param int $gradedby
 * @param int $groupid
 * @param array $overridden
 * @param array $grades
 * @param array $comments
 */
function peerwork_peer_override($peerworkid, $gradedby, $groupid, $overridden, $grades, $comments) {
    global $CFG, $USER, $DB;

    $cm = get_coursemodule_from_instance('peerwork', $peerworkid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $pac = new mod_peerwork_criteria($peerworkid);
    $criteria = $pac->get_criteria();
    $members = groups_get_members($groupid);
    $peerwork = $DB->get_record('peerwork', ['id' => $peerworkid], '*', MUST_EXIST);

    foreach ($criteria as $criterion) {
        // Get existing peer scores.
        $peerworkpeers = $DB->get_records(
            'peerwork_peers',
            [
                'peerwork' => $peerworkid,
                'groupid' => $groupid,
                'gradedby' => $gradedby,
                'criteriaid' => $criterion->id
            ],
            '',
            'gradefor, grade, peergrade, comments, id, peerwork, criteriaid,
             groupid, gradedby, feedback, locked, timecreated, timemodified'
        );

        foreach ($members as $member) {
            // Do nothing if grade has not been overridden.
            if (
                !isset($overridden['overridden_idx_' . $criterion->id]) ||
                !isset($overridden['overridden_idx_' . $criterion->id][$member->id])
            ) {
                continue;
            }

            $grade = $grades['gradeoverride_idx_' . $criterion->id][$member->id];
            $comment = $comments['comments_idx_' . $criterion->id][$member->id];

            // If peer grade record exists then update it.
            if (array_key_exists($member->id, $peerworkpeers)) {
                $peerworkpeer = $peerworkpeers[$member->id];
                // If grade or comment has changed.
                if ($peerworkpeer->grade != $grade || $peerworkpeer->comments != $comment) {
                    $peerworkpeer->grade = $grade;
                    $peerworkpeer->comments = $comment;
                    $peerworkpeer->overriddenby = $USER->id;
                    $peerworkpeer->timeoverridden = time();

                    $DB->update_record('peerwork_peers', $peerworkpeer, true);

                    $peergrade = $peerworkpeer->peergrade ? $peerworkpeer->peergrade : get_string('none');

                    $params = array(
                        'objectid' => $peerworkid,
                        'context' => $context,
                        'relateduserid' => $gradedby,
                        'other' => array(
                            'gradefor' => $peerworkpeer->gradefor,
                            'grade' => $peerworkpeer->grade,
                            'peergrade' => $peergrade,
                            'comments' => $peerworkpeer->comments
                        )
                    );

                    $event = \mod_peerwork\event\peer_grade_overridden::create($params);
                    $event->add_record_snapshot('peerwork_peers', $peerworkpeer);
                    $event->trigger();
                }
            } else {
                $peerworkpeer = new stdClass();
                $peerworkpeer->peerwork = $peerworkid;
                $peerworkpeer->groupid = $groupid;
                $peerworkpeer->gradedby = $gradedby;
                $peerworkpeer->gradefor = $member->id;
                $peerworkpeer->criteriaid = $criterion->id;
                $peerworkpeer->grade = $grade;
                $peerworkpeer->peergrade = null;
                $peerworkpeer->comments = $comment;
                $peerworkpeer->overriddenby = $USER->id;
                $peerworkpeer->timeoverridden = time();
                $peerworkpeer->feedback = null;
                $peerworkpeer->locked = $peerwork->lockediting;
                $peerworkpeer->timecreated = 0;
                $peerworkpeer->timemodified = 0;

                $peerworkpeer->id = $DB->insert_record('peerwork_peers', $peerworkpeer, true);

                $params = array(
                    'objectid' => $peerworkid,
                    'context' => $context,
                    'relateduserid' => $gradedby,
                    'other' => array(
                        'gradefor' => $peerworkpeer->gradefor,
                        'grade' => $peerworkpeer->grade,
                        'peergrade' => get_string('none', 'mod_peerwork'),
                        'comments' => $peerworkpeer->comments
                    )
                );

                $event = \mod_peerwork\event\peer_grade_overridden::create($params);
                $event->add_record_snapshot('peerwork_peers', $peerworkpeer);
                $event->trigger();
            }
        }
    }

    mod_peerwork_update_calculation($peerwork);
}

/**
 * Save the submission.
 *
 * @param stdClass $peerwork The module.
 * @param stdClass $submission The submission.
 * @param stdClass $group The group.
 * @param context $context The context.
 * @param stdClass $data The form data.
 * @param int $draftitemid The draft item ID.
 * @return array First value is the submission, second is the array of draft files.
 */
function mod_peerwork_save_submission($peerwork, $submission, $group, $context, $data, $draftitemid) {
    global $DB, $USER;

    // Early bail when the submission is locked.
    if ($submission && $submission->locked) {
        return [$submission, []];
    }

    // Create submission record if none yet.
    if (!$submission) {
        $submission = new stdClass();
        $submission->peerworkid = $peerwork->id;
        $submission->userid = $USER->id;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $submission->groupid = $group->id;
        $submission->locked = $peerwork->lockediting;

        $submission->id = $DB->insert_record('peerwork_submission', $submission);

        $params = array(
            'objectid' => $submission->id,
            'context' => $context,
            'other' => array('groupid' => $group->id)
        );

        $event = \mod_peerwork\event\submission_created::create($params);
        $event->trigger();

    } else {
        // Just update.
        $submission->timemodified = time();
        $submission->locked = $peerwork->lockediting;
        $DB->update_record('peerwork_submission', $submission);

        $params = array(
            'objectid' => $submission->id,
            'context' => $context,
            'other' => array('groupid' => $group->id)
        );

        $event = \mod_peerwork\event\submission_updated::create($params);
        $event->add_record_snapshot('peerwork_submission', $submission);
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
        $files = $fs->get_area_files($context->id, 'mod_peerwork', 'submission', $group->id, 'sortorder', false);
        $oldhashes = array();
        foreach ($files as $file) {
            $oldhashes[$file->get_contenthash()] = $file->get_contenthash();
        }

        $addedhashes = array_diff($newhashes, $oldhashes);
        $deletedhashes = array_diff($oldhashes, $newhashes);

        if ($deletedhashes) {
            $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array(
                    'deletedlist' => $deletedhashes
                )
            );

            $event = \mod_peerwork\event\submission_files_deleted::create($params);
            $event->trigger();
        }

        if ($addedhashes) {
            $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array(
                    'filelist' => $addedhashes
                )
            );

            $event = \mod_peerwork\event\submission_files_uploaded::create($params);
            $event->trigger();
        }

        if (count($newhashes) && $oldhashes != $newhashes) {
            // Hashes are different, submission has changed.
            $submission->submissionmodified = time();
            $submission->submissionmodifiedby = $USER->id;

            $DB->update_record('peerwork_submission', $submission);
        }

        file_save_draft_area_files($draftitemid, $context->id, 'mod_peerwork', 'submission', $group->id,
            peerwork_get_fileoptions($peerwork));
    }

    return [$submission, $draftfiles];
}

/**
 * Mail confirmation.
 *
 * @param stdClass $course The course.
 * @param stdClass $submission The submission.
 * @param array $draftfiles The files.
 * @param array $membersgradeable The members.
 * @param stdClass $data The data.
 * @return bool
 */
function mod_peerwork_mail_confirmation_submission($course, $submission, $draftfiles, $membersgradeable, $data) {
    global $CFG, $USER;
    // TODO Mail confirmation.
    return true;

    $subject = get_string('confirmationmailsubject', 'peerwork', $course->fullname);

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

    $a->url = $CFG->wwwroot . "/mod/peerwork/view.php?n=" . $submission->peerworkid;

    $body = get_string('confirmationmailbody', 'peerwork', $a);
    return email_to_user($USER, core_user::get_noreply_user(), $subject, $body);
}

/**
 * Clear the submissions.
 *
 * @param stdClass $peerwork The peerwork.
 * @param context $context The context.
 * @param int $groupid The group ID, or 0 for all groups.
 * @return void
 */
function mod_peerwork_clear_submissions($peerwork, $context, $groupid = 0) {
    global $DB;

    if ($groupid > 0) {
        $sql = 'peerworkid = :peerworkid AND groupid = :groupid AND COALESCE(timegraded, 0) = 0';
        $submissions = $DB->get_records_select('peerwork_submission', $sql, [
            'peerworkid' => $peerwork->id,
            'groupid' => $groupid
        ]);
    } else {
        $sql = 'peerworkid = :peerworkid AND COALESCE(timegraded, 0) = 0 AND released = 0';
        $submissions = $DB->get_records_select('peerwork_submission', $sql, ['peerworkid' => $peerwork->id]);
    }

    foreach ($submissions as $submission) {
        $id = $submission->id;
        $groupid = $submission->groupid;

        // Delete database values.
        $DB->delete_records('peerwork_peers', ['peerwork' => $peerwork->id, 'groupid' => $groupid]);
        $DB->delete_records('peerwork_justification', ['peerworkid' => $peerwork->id, 'groupid' => $groupid]);
        $DB->delete_records('peerwork_grades', ['peerworkid' => $peerwork->id, 'submissionid' => $id]);
        $DB->delete_records('peerwork_submission', ['id' => $id]);

        // Delete the submission files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_peerwork', 'submission', $groupid);

        // Trigger the event.
        $params = [
            'objectid' => $id,
            'context' => $context,
            'other' => [
                'groupid' => $groupid
            ]
        ];
        $event = \mod_peerwork\event\submission_cleared::create($params);
        $event->add_record_snapshot('peerwork_submission', $submission);
        $event->trigger();
    }
}

/**
 * Get the peers that submitted late.
 *
 * @param stdClass $peerwork Peerwork record.
 * @param stdClass $submission Submission record.
 * @return array Where keys are user IDs, and values are \core_user\fields::for_userpic() and timegraded.
 */
function mod_peerwork_get_late_peers($peerwork, $submission) {
    global $DB;

    if (peerwork_due_date($peerwork) !== PEERWORK_DUEDATE_PASSED) {
        return [];
    }

    // Group all grades given and select the first grading time (they should be grading everyone
    // at the same time anyway), for those graders where the first time was past the due date.
    $ufieldsapi = \core_user\fields::for_userpic();
    $ufields = 'u.id, u.username' . $ufieldsapi->get_sql('u')->selects;
    $sql = "SELECT $ufields, MIN(p.timecreated) AS timegraded
              FROM {peerwork_peers} p
              JOIN {user} u
                ON u.id = p.gradedby
             WHERE p.groupid = :groupid
               AND p.peerwork = :peerworkid
          GROUP BY p.gradedby, $ufields
            HAVING MIN(p.timecreated) > :duedate";
    $params = [
        'groupid' => $submission->groupid,
        'peerworkid' => $peerwork->id,
        'duedate' => !empty($peerwork->duedate) ? $peerwork->duedate : time() + DAYSECS * 99
    ];

    return array_reduce($DB->get_records_sql($sql, $params), function($carry, $record) {
        $carry[$record->id] = $record;
        return $carry;
    }, []);
}

/**
 * Lock editing across the entire activity.
 *
 * @param stdClass $peerwork The peerwork instance.
 * @return void
 */
function mod_peerwork_lock_editing($peerwork) {
    global $DB;
    $DB->execute("UPDATE {peerwork_peers} SET locked = 1 WHERE peerwork = ?", [$peerwork->id]);
    $DB->execute("UPDATE {peerwork_submission} SET locked = 1 WHERE peerworkid = ?", [$peerwork->id]);
}

/**
 * Unlock editing across the entire activity.
 *
 * @param stdClass $peerwork The peerwork instance.
 * @return void
 */
function mod_peerwork_unlock_editing($peerwork) {
    global $DB;
    $DB->execute("UPDATE {peerwork_peers} SET locked = 0 WHERE peerwork = ?", [$peerwork->id]);
    $DB->execute("UPDATE {peerwork_submission} SET locked = 0 WHERE peerworkid = ?", [$peerwork->id]);
}

/**
 * Unlock editing for a single student.
 *
 * @param int $peerworkid The peerwork instance ID.
 * @param int $graderid The student ID.
 * @return void
 */
function mod_peerwork_unlock_grader($peerworkid, $graderid) {
    global $DB;
    $DB->execute("UPDATE {peerwork_peers} SET locked = 0 WHERE peerwork = ? AND gradedby = ?", [$peerworkid, $graderid]);
}

/**
 * Unlock editing for of a submission.
 *
 * @param int $submissionid The submission ID.
 * @return void
 */
function mod_peerwork_unlock_submission($submissionid) {
    global $DB;
    $DB->execute("UPDATE {peerwork_submission} SET locked = 0 WHERE id = ?", [$submissionid]);
}

/**
 * Get the list of locked graders.
 *
 * A grader is considered locked when any of their grades are being locked.
 *
 * @param int $peerworkid The module ID.
 * @return array
 */
function mod_peerwork_get_locked_graders($peerworkid) {
    global $DB;
    return $DB->get_fieldset_select('peerwork_peers', 'DISTINCT gradedby', 'peerwork = ? AND locked = 1', [$peerworkid]);
}

/**
 * Get the list of locked peers.
 *
 * Note that as of time of writing, a student is locked when at one of their entries is
 * flagged as being locked. Thus, we do not currently support unlocking a specific criterion
 * for a specific user. If any criterion is locked, then justifications and peer grades are
 * locked for the student being graded.
 *
 * @param stdClass $peerwork The module.
 * @param int $gradedby The grading user.
 * @return array
 */
function mod_peerwork_get_locked_peers($peerwork, $gradedby) {
    global $DB;
    $sql = 'peerwork = :peerworkid AND gradedby = :gradedby AND locked = 1';
    return $DB->get_fieldset_select('peerwork_peers', 'DISTINCT gradefor', $sql, [
        'peerworkid' => $peerwork->id,
        'gradedby' => $gradedby
    ]);
}

/**
 * Update local grades across the entire activity.
 *
 * @param stdClass $peerwork The peerwork instance.
 * @return void
 */
function mod_peerwork_update_calculation($peerwork) {
    global $DB;

    $submissions = $DB->get_records('peerwork_submission', ['peerworkid' => $peerwork->id]);

    if ($submissions) {
        foreach ($submissions as $submission) {
            if ($submission->timegraded) {
                $groupid = $submission->groupid;
                $group = $DB->get_record('groups', ['id' => $groupid], '*', MUST_EXIST);
                $members = groups_get_members($groupid);
                peerwork_update_local_grades($peerwork, $group, $submission, array_keys($members));
            }
        }
    }
}

/**
 * Returns calculator class name.
 *
 * @param string $calculator calculator name.
 *
 * @return string
 */
function calculator_class($calculator) {
    global $CFG;

    if (!$calculator) {
        debugging('No calculator is set');
        return '\\mod_peerwork\peerworkcalculator_plugin';
    }

    $plugin = 'peerworkcalculator_' . $calculator;
    $classname = '\\' . $plugin . '\calculator';
    $disabled = get_config($plugin, 'disabled');

    if (!class_exists($classname)) {
        debugging($classname . ' is missing or disabled');

        // Get the default.
        $defaultcalculator = get_config('peerwork', 'calculator');
        $plugin = 'peerworkcalculator_' . $defaultcalculator;
        $classname = '\\' . $plugin . '\calculator';
        $disabled = get_config($plugin, 'disabled');

        // Fall back to base.
        if (!class_exists($classname)) {
            return '\\mod_peerwork\peerworkcalculator_plugin';
        }
    }

    if (!in_array('mod_peerwork\peerworkcalculator_plugin', class_parents($classname))) {
        throw new coding_exception($classname . ' does not extend peerwork_calculator_plugin class');
    }

    return $classname;
}

/**
 * Returns instance of calculator class
 *
 * @param stdClass $peerwork
 *
 * @return object Instance of a calculator
 */
function calculator_instance($peerwork) {
    global $CFG;

    $calculator = $peerwork->calculator;
    $classname = calculator_class($calculator);
    $calculatorinstance = new $classname($peerwork, $calculator);

    return $calculatorinstance;
}

/**
 * Load the plugins.
 *
 * @param stdClass $peerwork
 * @param string $subtype - calculator
 *
 * @return array - The sorted list of plugins
 */
function load_plugins($peerwork, $subtype) {
    global $CFG;
    $result = [];
    $sortedresult = [];
    $names = core_component::get_plugin_list($subtype);

    foreach ($names as $name => $path) {
        $shortsubtype = substr($subtype, strlen('peerwork'));
        $pluginclass = 'peerwork' . $shortsubtype . '_' . $name . '\\' . $shortsubtype;

        if (!class_exists($pluginclass)) {
            throw new coding_exception($pluginclass . ' does not exist');
        } else {
            $plugin = new $pluginclass($peerwork, $name);
            $idx = $plugin->get_sort_order();

            while (array_key_exists($idx, $result)) {
                $idx += 1;
            }

            $result[$idx][$name] = $plugin;
        }
    }

    ksort($result);

    foreach ($result as $plugins) {
        $sortedresult = $sortedresult + $plugins;
    }

    return $sortedresult;
}

/**
 * Add one plugins settings to edit plugin form.
 *
 * @param peerwork_plugin $plugin The plugin to add the settings form
 * @param peerwork_plugin $peerwork
 * @param array $pluginsenabled A list of form elements to be added to a select.
 *                              The new element is added to this array by this function.
 * @return void
 */
function get_enabled_plugins($plugin, $peerwork, & $pluginsenabled) {
    global $CFG, $DB;

    $name = $plugin->get_type();
    $value = $plugin->get_name();

    if ($plugin->is_visible() && $plugin->is_configurable()) {
        $pluginsenabled[$name] = $value;
    } else if (isset($peerwork->calculator) && ($peerwork->calculator == $name)) {
        // The calculator is no longer enabled but is still being used.
        $pluginsenabled[$name] = $value;
    }
}

/**
 * Add one plugins settings to edit plugin form.
 *
 * @param MoodleQuickForm $mform The form to add the configuration settings to.
 *                               This form is modified directly (not returned).
 * @param peerwork_plugin $peerwork
 * @param string $selected The selected plugin to get settings for.
 * @return void
 */
function add_plugin_settings(MoodleQuickForm $mform, $peerwork, $selected) {
    global $CFG;

    $calculatorplugins = load_plugins($peerwork, 'peerworkcalculator');
    $plugin = $calculatorplugins[$selected];
    $plugin->get_settings($mform);
}

/**
 * Add settings to edit plugin form.
 *
 * @param MoodleQuickForm $mform The form to add the configuration settings to.
 * This form is modified directly (not returned).
 * @param fieldset|null $peerwork Existing record if updating or null if adding new.
 * @return void
 */
function add_all_calculator_plugins(MoodleQuickForm $mform, $peerwork) {
    $mform->addElement('header', 'calculatortypes', get_string('calculatortypes', 'peerwork'));
    $calculatorplugins = load_plugins($peerwork, 'peerworkcalculator');
    $calculatorpluginsenabled = [];

    foreach ($calculatorplugins as $name => $plugin) {
        $calculatorpluginnames[$name] = $plugin->get_name();
        get_enabled_plugins($plugin, $peerwork, $calculatorpluginsenabled);
    }

    if (count($calculatorpluginsenabled) > 1) {
        $gradesexistmsg = get_string('gradesexistmsg', 'peerwork');
        $gradesexisthtml = '<div class=\'alert alert-warning\'>' . $gradesexistmsg . '</div>';
        $mform->addElement('static', 'gradesexistmsg', '', $gradesexisthtml);
        $mform->addElement('selectyesno',
            'recalculategrades',
            get_string('recalculategrades', 'peerwork')
        );
        $mform->setType('recalculategrades', PARAM_BOOL);
        $mform->addHelpButton('recalculategrades', 'recalculategrades', 'peerwork');

        $mform->addElement(
            'select',
            'calculator',
            get_string('calculator', 'peerwork'),
            $calculatorpluginsenabled
        );
        $mform->setType('calculator', PARAM_TEXT);
        $mform->disabledIf('calculator', 'recalculategrades', 'eq', 0);

        // Button to update calculator-specific options on format change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('updatecalculator');
        $mform->addElement('submit', 'updatecalculator', get_string('calculatorupdate', 'mod_peerwork'));
    } else if (count($calculatorpluginsenabled) == 1) {
        $value = key($calculatorpluginsenabled);
        $mform->addElement('hidden', 'calculator');
        $mform->setType('calculator', PARAM_TEXT);
        $mform->setDefault('calculator', $value);
    } else {
        $mform->addElement('hidden', 'calculator');
        $mform->setType('calculator', PARAM_TEXT);

        $mform->addElement(
            'static',
            'nocalculator',
            get_string('calculator', 'peerwork'),
            get_string('nocalculator', 'peerwork')
        );
    }

    // Dummy element to be place calculator settings.
    $mform->addElement('static', 'calculatorsettings');
}

/**
 * Allow each plugin an opportunity to update the defaultvalues
 * passed in to the settings form (needed to set up draft areas for
 * editor and filemanager elements)
 *
 * @param array $defaultvalues
 */
function plugin_data_preprocessing(&$defaultvalues) {
    $calculatorplugins = core_component::get_plugin_list('peerworkcalculator');

    foreach ($calculatorplugins as $name => $filepath) {
        $pluginclass = 'peerworkcalculator_' . $name . '\\calculator';
        $plugin = new $pluginclass(null, $name);

        if ($plugin->is_visible()) {
            $plugin->data_preprocessing($defaultvalues);
        }
    }
}

/**
 * Update the settings for a single plugin.
 *
 * @param peerwork_plugin $plugin The plugin to update
 * @param stdClass $formdata The form data
 * @return bool false if an error occurs
 */
function update_plugin_instance(\mod_peerwork\peerwork_plugin $plugin, stdClass $formdata) {
    if ($plugin->is_visible()) {
        if (!$plugin->save_settings($formdata)) {
            throw new moodle_exception($plugin->get_error());
            return false;
        }
    }

    return true;
}
