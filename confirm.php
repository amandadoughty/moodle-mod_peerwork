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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/peerassessment/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerassessment/add_submission_form.php');
require_once($CFG->dirroot . '/mod/peerassessment/locallib.php');
require_once($CFG->dirroot . '/mod/peerassessment/confirm_form.php');

$id = optional_param('id', 0, PARAM_INT); // Uses course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // peerassessment instance ID - it should be named as the first character of the module.

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

// @todo make sure this is submission for assessment in correct state
// @todo for increased security, only accept POST

// Print the page header.

$PAGE->set_url('/mod/peerassessment/confirm.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerassessment->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mygroup = peerassessment_get_mygroup($course, $USER->id, $groupingid);
$membersgradeable = peerassessment_get_peers($course, $peerassessment, $groupingid, $mygroup);
$submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $mygroup));
$duedate = peerassessment_due_date($peerassessment);
$isopen = peerassessment_is_open($peerassessment);

if (!$isopen->code) {
    print_error($isopen->text);
}

$data = new stdClass();
$data->id = null;
$data->submission = null;
foreach ($membersgradeable as $member) {
    $data->feedback[$member->id] = null;
    $data->grade[$member->id] = null;
}
$draftitemid = file_get_submitted_draft_itemid('submission');

$data->fileoptions = peerassessment_get_fileoptions($peerassessment);
$mform = new mod_peerassessment_confirm_form(null, $data);

if ($mform->is_cancelled()) {
    // Form cancelled, redirect.
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
} else if (($data = $mform->get_data())) {
    // Form has been submitted. Create submission record if none yet.
    peerassessment_save($peerassessment, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable);

    // Send notifications if late submission.
    if ($duedate == PEERASSESSMENT_DUEDATE_PASSED) {
        $teachers = peerassessment_teachers($context);
        foreach ($teachers as $teacher) {
            $timeupdated = userdate(time());

            $postsubject = get_string('latesubmissionsubject', 'peerassessment') . ': ' . fullname($USER, true)
            . ' -> ' . $peerassessment->name;
            $info = new stdClass();
            $info->user = fullname($USER, true);
            $info->name = $peerassessment->name;
            $posttext = get_string('latesubmissiontext', 'peerassessment', $info);
            // $posthtml = ($teacher->mailformat == 1) ? $this->email_teachers_html($info) : '';

            $eventdata = new stdClass();
            $eventdata->modulename = 'mod_peerassessment';
            $eventdata->userfrom = $USER;
            $eventdata->userto = $teacher;
            $eventdata->subject = $postsubject;
            $eventdata->fullmessage = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $posttext;
            $eventdata->smallmessage = $postsubject;

            $eventdata->name = 'late_submission';
            $eventdata->component = 'mod_peerassessment';
            $eventdata->notification = 1;
            $eventdata->contexturl = $CFG->wwwroot . '/mod/peerassessment/confirm.php?id=' . $cm->id;;
            $eventdata->contexturlname = format_string($peerassessment->name, true);;

            message_send($eventdata);
        }
    }
    redirect(new moodle_url('view.php', array('id' => $cm->id)), 'Submission saved', 5);
} else {
    redirect(new moodle_url('view.php', array('id' => $cm->id)));
    return;
}
