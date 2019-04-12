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

/**
 * Called to respond to the student submitting data with the submissions_form.php form.
 * Saves data into database.
 */
require_once(__DIR__. '/../../config.php');
require_once($CFG->dirroot . '/mod/peerassessment/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerassessment/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerassessment/locallib.php');
require_once($CFG->dirroot . '/mod/peerassessment/forms/confirm_form.php');
require_once($CFG->dirroot . '/mod/peerassessment/renderer.php');


$id = optional_param('id', 0, PARAM_INT); 	// Course_module ID, or
$n = optional_param('n', 0, PARAM_INT); 	// Peerassessment instance ID - it should be named as the first character of the module.

if ($id) {
    $cm = get_coursemodule_from_id('peerassessment', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $peerassessment = $DB->get_record('peerassessment', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $peerassessment = $DB->get_record('peerassessment', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $peerassessment->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerassessment', $peerassessment->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$groupingid = $peerassessment->submissiongroupingid;
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO make sure this is submission for assessment in correct state.
// TODO for increased security, only accept POST.

$params = array(
        'context' => $context
    );

$event = \mod_peerassessment\event\assessable_submitted::create($params);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/peerassessment/submissions.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerassessment->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mygroup = peerassessment_get_mygroup($course->id, $USER->id, $groupingid);
$membersgradeable = peerassessment_get_peers($course, $peerassessment, $groupingid, $mygroup);
$group = $DB->get_record('groups', array('id' => $mygroup), '*', MUST_EXIST);
$status = peerassessment_get_status($peerassessment, $group);
$isopen = peerassessment_is_open($peerassessment, $mygroup);

if (!$isopen->code) {
    print_error($isopen->text);
}
// Check if already submitted.
$submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $mygroup));

// Check if I already graded my peers.
$myassessments = $DB->get_records('peerassessment_peers', array('peerassessment' => $peerassessment->id, 'gradedby' => $USER->id));

// Visualise.
$mform = new mod_peerassessment_submissions_form(new moodle_url('submissions.php'), array('id' => $id, 'fileupload' => true, 'peers'
    => $membersgradeable, 'fileoptions' => peerassessment_get_fileoptions($peerassessment)));
/*
if (!$submission) {
    //form for new submission
    $mform = new mod_peerassessment_add_submission_form(new moodle_url('submissions.php'), array('id' => $id, 'fileupload' => true,
    'peers' => $membersgradeable,'fileoptions'=>peerassessment_get_fileoptions($peerassessment)));
} else if ($submission && !$myassessments) {
    //form for peer grading only, show file already submited
    $mform = new mod_peerassessment_add_submission_form(new moodle_url('submissions.php'), array('id' => $id, 'fileupload' => false,
    'peers' => $membersgradeable,'fileoptions'=>peerassessment_get_fileoptions($peerassessment)));
} else {
    throw new coding_exception('Wrong state');
}
*/

$itemid = 0; // ...peerassessment_submission.id.

// Fetches the file manager draft area, called 'attachments'.
$draftitemid = file_get_submitted_draft_itemid('submission');

// Copy all the files from the 'real' area, into the draft area.
file_prepare_draft_area($draftitemid, $context->id, 'mod_peerassessment', 'submission',
    $itemid, peerassessment_get_fileoptions($peerassessment));

if ($mform->is_cancelled()) {
    // Form cancelled, redirect.
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
} else if (($data = $mform->get_data())) {
    peerassessment_save($peerassessment, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable);
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
} else {
    // This is for submission only really, so...
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
}