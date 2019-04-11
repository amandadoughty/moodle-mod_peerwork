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
 * @package    mod
 * @subpackage peerassessment
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once( dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/peerassessment/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerassessment/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerassessment/locallib.php');
require_once($CFG->dirroot . '/mod/peerassessment/forms/details_form.php');

/**
 * This provides a teacher with a summary view of the assessment for a group, detailing who has submitted.
 */

$id = required_param('id', PARAM_INT);
$groupid = required_param('groupid', PARAM_INT);

$cm             = get_coursemodule_from_id('peerassessment', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$peerassessment = $DB->get_record('peerassessment', array('id' => $cm->instance), '*', MUST_EXIST);
$submission     = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $groupid));
$members        = groups_get_members($groupid);
$group          = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
$status         = peerassessment_get_status($peerassessment, $group);



// Print the standard page header and check access rights.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/peerassessment/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerassessment->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
require_capability('mod/peerassessment:grade', $context);


// Start the form, initialise with some data.
$data = array('id' => $id, 'groupid' => $groupid); //, 'feedback_files' => $draftitemid


$mform = new mod_peerassessment_details_form();
if ($status->code == PEERASSESSMENT_STATUS_GRADED) {
//     $data['feedback']['text'] = "KM" . $submission->feedbacktext;
//     $data['feedback']['format'] = $submission->feedbackformat;
    $data['grade'] = $submission->grade;
}
$data['groupname'] = $group->name;
$data['status'] = $status->text;
$submissionfiles = peerassessment_submission_files($context, $group);	// creates <a href HTML
$data['submission'] = empty($submissionfiles) ? get_string('nothingsubmitted', 'peerassessment') : implode('<br/>', $submissionfiles); 


// Get the peer grades awarded so far, then for each criteria
// output a HTML tabulation of the peers and the grades awarded and received.
// TODO instead of HTML fragment can we build this with form elments?
$grades = peerassessment_get_peer_grades($peerassessment, $group, $members, false);
$pac = new peerassessment_criteria( $peerassessment->id );
$data['peergradesawarded'] = '';
foreach( $pac ->getCriteria() as $criteria ) {
	
	$sort = $criteria->sort;

	$t = new html_table();
	$t->attributes['class'] = 'userenrolment';
	$t->id = 'mod-peerassessment-summary-table';
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
if ($status->code == PEERASSESSMENT_STATUS_GRADED) {
	$data['finalgrades'] = array();
	
	foreach ($members as $member) { 
		$gradebook = 99;	//TODO current (if any) gradebook grade
		$data['finalgrades'][] = array(	'memberid'=>$member->id, 
										'contribution' => peerassessment_get_score($peerassessment, $group, $member), 
										'fullname'=>fullname($member), 
										'calcgrade'=>peerassessment_get_grade($peerassessment, $group, $member), 
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
        $submission->assignment = $peerassessment->id;
        $submission->groupid = $group->id;
    }
    $submission->grade = $data->grade;
    $submission->gradedby = $USER->id;
    $submission->timegraded = time();
    $submission->feedbacktext = $data->feedback['text'];
    $submission->feedbackformat = $data->feedback['format'];

    // add final grade here
    //$submission->finalgrade = peerassessment_get_grade($peerassessment, $group, $member);

    //error_log("details.php submitting form with " . print_r($submission,true) );
    if (isset($submission->id)) {
        $DB->update_record('peerassessment_submission', $submission);
    } else {
        $submission->id = $DB->insert_record('peerassessment_submission', $submission);
    }

    // Update grades for every group member.
    foreach ($members as $member) {
        peerassessment_update_grades($peerassessment, $member->id);
    }
    // Save the file submitted.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_peerassessment', 'feedback_files',
        $group->id, mod_peerassessment_details_form::$fileoptions);

    $params = array(
                'objectid' => $submission->id,
                'context' => $context,
                'other' => array(
                    'groupid' => $group->id,
                    'groupname' => $group->name,
                    'grade' => $data->grade
                    )
            );

    $event = \mod_peerassessment\event\submission_graded::create($params);
    $event->add_record_snapshot('peerassessment_submission', $submission);
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

$event = \mod_peerassessment\event\submission_grade_form_viewed::create($params);
$event->trigger();

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
