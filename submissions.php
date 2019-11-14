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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Called to respond to the student submitting data with the submissions_form.php form.
 * Saves data into database.
 */
require_once(__DIR__. '/../../config.php');
require_once($CFG->dirroot . '/mod/peerwork/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerwork/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->dirroot . '/mod/peerwork/forms/confirm_form.php');
require_once($CFG->dirroot . '/mod/peerwork/renderer.php');


$id = optional_param('id', 0, PARAM_INT); 	// Course_module ID, or
$n = optional_param('n', 0, PARAM_INT); 	// peerwork instance ID - it should be named as the first character of the module.

if ($id) {
    $cm = get_coursemodule_from_id('peerwork', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $peerwork = $DB->get_record('peerwork', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $peerwork = $DB->get_record('peerwork', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $peerwork->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerwork', $peerwork->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$groupingid = $cm->groupingid;
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO make sure this is submission for assessment in correct state.

$params = array(
    'context' => $context
);

$event = \mod_peerwork\event\assessable_submitted::create($params);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/peerwork/submissions.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerwork->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mygroup = peerwork_get_mygroup($course->id, $USER->id, $groupingid);
$membersgradeable = peerwork_get_peers($course, $peerwork, $groupingid, $mygroup);
$group = $DB->get_record('groups', array('id' => $mygroup), '*', MUST_EXIST);
$status = peerwork_get_status($peerwork, $group);
$isopen = peerwork_is_open($peerwork, $mygroup);

if (!$isopen->code) {
    print_error($isopen->text);
}
// Check if already submitted.
$submission = $DB->get_record('peerwork_submission', array('assignment' => $peerwork->id, 'groupid' => $mygroup));

// Check if I already graded my peers.
$myassessments = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id, 'gradedby' => $USER->id));

// Visualise.
$mform = new mod_peerwork_submissions_form(new moodle_url('submissions.php'), array('id' => $id, 'fileupload' => true,
    'peerworkid' => $peerwork->id, 'peers' => $membersgradeable, 'fileoptions' => peerwork_get_fileoptions($peerwork)));

$itemid = 0; // Will be peerwork_submission.id.

// Fetches the file manager draft area, called 'attachments'.
$draftitemid = file_get_submitted_draft_itemid('submission');

// Copy all the files from the 'real' area, into the draft area.
file_prepare_draft_area($draftitemid, $context->id, 'mod_peerwork', 'submission',
    $itemid, peerwork_get_fileoptions($peerwork));

if ($mform->is_cancelled()) {
    // Form cancelled, redirect.
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
} else if (($data = $mform->get_data())) {
    peerwork_save($peerwork, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable);
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
} else {
    // This is for submission only really, so...
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
}