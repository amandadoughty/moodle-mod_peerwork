<?php
// This file is part of 3rd party created module for Moodle - http://moodle.org/
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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once( dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/peerwork/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerwork/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->dirroot . '/mod/peerwork/forms/details_form.php');

/**
 * This provides a teacher with a summary view of the assessment for a group, detailing who has submitted.
 */

$id = required_param('id', PARAM_INT);
$groupid = required_param('groupid', PARAM_INT);

$cm             = get_coursemodule_from_id('peerwork', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$peerwork = $DB->get_record('peerwork', array('id' => $cm->instance), '*', MUST_EXIST);
$submission     = $DB->get_record('peerwork_submission', array('assignment' => $peerwork->id, 'groupid' => $groupid));
$members        = groups_get_members($groupid);
$group          = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
$status         = peerwork_get_status($peerwork, $group);



// Print the standard page header and check access rights.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/peerwork/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerwork->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
require_capability('mod/peerwork:grade', $context);


// Start the form, initialise with some data.
$data = array('id' => $id, 'groupid' => $groupid); //, 'feedback_files' => $draftitemid


$mform = new mod_peerwork_details_form();
if ($status->code == peerwork_STATUS_GRADED) {
//     $data['feedback']['text'] = "KM" . $submission->feedbacktext;
//     $data['feedback']['format'] = $submission->feedbackformat;
    $data['grade'] = $submission->grade;
}
$data['groupname'] = $group->name;
$data['status'] = $status->text;
$submissionfiles = peerwork_submission_files($context, $group);	// creates <a href HTML
$data['submission'] = empty($submissionfiles) ? get_string('nothingsubmitted', 'peerwork') : implode('<br/>', $submissionfiles); 


// Get the peer grades awarded so far, then for each criteria
// output a HTML tabulation of the peers and the grades awarded and received.
// TODO instead of HTML fragment can we build this with form elments?
$grades = peerwork_get_peer_grades($peerwork, $group, $members, false);
$pac = new peerwork_criteria( $peerwork->id );
$data['peergradesawarded'] = '';
foreach( $pac ->getCriteria() as $criteria ) {
	
	$sort = $criteria->sort;

	$t = new html_table();
	$t->attributes['class'] = 'userenrolment';
	$t->id = 'mod-peerwork-summary-table';
	$t->head[] = '';
	$t->caption = $criteria ->description;
	
	foreach ($members as $member) {
		$t->head[] = fullname($member);
		$row = new html_table_row();
		$row->cells = array();
		$row->cells[] = fullname($member);
		
		foreach ($members as $peer) {
			if( array_key_exists( $member->id, $grades->grades[$sort] ) &&  array_key_exists( $peer->id, $grades->grades[$sort][$member->id])) {
				$row->cells[] = $grades->grades[$sort][$member->id][$peer->id];
			} else {
				$row->cells[] = '-';
			}
			
		}
		$t->data[] = $row;
	}
	$data['peergradesawarded'] .= html_writer::table($t); // Write the table for this criterion into the HTML placeholder element.
}


// If assignment has been graded then pass the required data to create a table showing calculated grades. 
$finalgrades = array(); // Becomes userid => grade 
if ($status->code == peerwork_STATUS_GRADED) {
	$data['finalgrades'] = array();
	
	foreach ($members as $member) { 
		$gradebook = 99;	//TODO current (if any) gradebook grade
		$data['finalgrades'][] = array(	'memberid'=>$member->id, 
										'contribution' => peerwork_get_score($peerwork, $group, $member), 
										'fullname'=>fullname($member), 
										'calcgrade'=>peerwork_get_grade($peerwork, $group, $member), 
										'finalgrade'=>$gradebook );
	}
}
$mform->set_data($data);


if ($mform->is_cancelled()) {
    // Form cancelled, redirect.
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
} else if (($data = $mform->get_data())) {
	//
    // Form has been submitted, save form values to database then redirect to re-display form.
    //
    if (!$submission) {
        $submission = new stdClass();
        $submission->assignment = $peerwork->id;
        $submission->groupid = $group->id;
    }
    $submission->grade = $data->grade;
    $submission->gradedby = $USER->id;
    $submission->timegraded = time();
    $submission->feedbacktext = $data->feedback['text'];
    $submission->feedbackformat = $data->feedback['format'];

    // add final grade here
    //$submission->finalgrade = peerwork_get_grade($peerwork, $group, $member);

    //error_log("details.php submitting form with " . print_r($submission,true) );
    if (isset($submission->id)) {
        $DB->update_record('peerwork_submission', $submission);
    } else {
        $submission->id = $DB->insert_record('peerwork_submission', $submission);
    }

    // Update grades for every group member.
    foreach ($members as $member) {
        peerwork_update_grades($peerwork, $member->id);
    }
    // Save the file submitted.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_peerwork', 'feedback_files',
        $group->id, mod_peerwork_details_form::$fileoptions);

    $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array(
                    'groupid' => $group->id,
                    'groupname' => $group->name,
                    'grade' => $data->grade
                    )
            );

    $event = \mod_peerwork\event\submission_graded::create($params);
    $event->add_record_snapshot('peerwork_submission', $submission);
    $event->trigger();

    redirect(new moodle_url('details.php', array('id' => $id, 'groupid' => $groupid)));
}

// 
// Form should now be setup to display, so do the output.
//
$params = array(
                'objectid' => $cm->id,
                'context' => $context,
                'other' => array('groupid' => $group->id)
            );

$event = \mod_peerwork\event\submission_grade_form_viewed::create($params);
$event->trigger();

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
