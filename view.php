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
 * This is a peer grading based group assignment activity for Moodle 2.4
 *
 * This module enables a group of students submit one file as their
 * assignment. In addition to the file submission the students can rate
 * their fellow group members activity on the project.
 * The activity then calculates the grade the students receive.
 * The teacher still has to apply the grade to the submission
 * The final grade is based on the teacher grade and the group grade.
 *
 * @package    mod
 * @subpackage peerassessment
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/peerassessment/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerassessment/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerassessment/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // peerassessment instance ID - it should be named as the first character of the module.
$edit = optional_param('edit', false, PARAM_BOOL);


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

$params = array(
            'objectid' => $cm->instance,
            'context' => $context
        );

$event = \mod_peerassessment\event\course_module_viewed::create($params);
$event->add_record_snapshot('course', $course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
$event->add_record_snapshot($cm->modname, $peerassessment);
$event->trigger();


// Print the page header.

$PAGE->set_url('/mod/peerassessment/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerassessment->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Other things you may want to set - remove if not needed
// $PAGE->set_cacheable(false);
// $PAGE->set_focuscontrol('some-html-id');
// $PAGE->add_body_class('peerassessment-'.$somevar);
// end.

// If not submitted yet, and student and part of a group
// allow for submission.

// Output starts here.
echo $OUTPUT->header();

// Show mod details.
echo $OUTPUT->heading(format_string($peerassessment->name));
echo $OUTPUT->box(format_string($peerassessment->intro));

// If teacher then display statuses for all groups.
if (has_capability('mod/peerassessment:grade', $context)) {
    $duedate = peerassessment_due_date($peerassessment);
    if ($duedate != PEERASSESSMENT_DUEDATE_NOT_USED) {
        echo $OUTPUT->box('Due date: ' . userdate($peerassessment->duedate));
        if ($duedate == PEERASSESSMENT_DUEDATE_PASSED) {
            echo $OUTPUT->box('Assessment closed for: ' . format_time(time() - $peerassessment->duedate));
        } else {
            echo $OUTPUT->box('Time remaining: ' . format_time(time() - $peerassessment->duedate));

        }
    }

    $allgroups = groups_get_all_groups($course->id, 0, $groupingid);

    $t = new html_table();
    $t->attributes['class'] = 'userenrolment';
    $t->id = 'mod-peerassessment-summary-table';
    $t->head = array('name', '# members', '# peer grades', 'status', 'actions');
    foreach ($allgroups as $group) {
        $members = groups_get_members($group->id);
        $status = peerassessment_get_status($peerassessment, $group);
        $grades = peerassessment_get_peer_grades($peerassessment, $group, $members, false);

        $options = array();
        /* if ($status->code != PEERASSESSMENT_STATUS_SUBMITTED) {
            $options = array('disabled' => true);
        }*/

        $row = new html_table_row();
        $actions = '';
        $status = peerassessment_get_status($peerassessment, $group);
        if ($status->code == PEERASSESSMENT_STATUS_GRADED) {
            $actions .= $OUTPUT->single_button(new moodle_url('details.php', array('id' => $cm->id,
                'groupid' => $group->id)), "Edit", 'get');
        } else {
            $actions .= $OUTPUT->single_button(new moodle_url('details.php', array('id' => $cm->id,
                'groupid' => $group->id)), "Grade", 'get');
        }
        $actions .= $OUTPUT->single_button(new moodle_url('export.php', array('id' => $cm->id,
            'groupid' => $group->id)), "Export", 'post');
        $row->cells = array($OUTPUT->action_link(new moodle_url('details.php', array('id' => $cm->id,
            'groupid' => $group->id)), $group->name),
            count($members),
            count($grades->grades),
            $status->text,
            $actions
        );
        $t->data[] = $row;
    }
    echo html_writer::table($t);

    echo $OUTPUT->box_start('generalbox', null);
    echo $OUTPUT->single_button(new moodle_url('exportxls.php', array('id' => $cm->id,  'groupingid' => $groupingid)),
        get_string("exportxls", 'mod_peerassessment'), 'post', array("class" => 'yui3-u singlebutton'));
    echo $OUTPUT->single_button(new moodle_url('downloadallsubmissions.php', array('id' => $cm->id)),
        get_string("downloadallsubmissions", 'mod_peerassessment'), 'post', array("class" => 'yui3-u singlebutton'));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die();
}

$mygroup = peerassessment_get_mygroup($course->id, $USER->id, $groupingid);
$membersgradeable = peerassessment_get_peers($course->id, $peerassessment, $groupingid, $mygroup);
$groupmode = groups_get_activity_groupmode($cm);

if ($groupmode) {
    groups_get_activity_group($cm, true);
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/peerassessment/view.php?id='.$id);
}

// Check if already submitted.
$submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id, 'groupid' => $mygroup));

// Check if I already graded my peers.
$myassessments = $DB->get_records('peerassessment_peers', array('peerassessment' => $peerassessment->id, 'gradedby' => $USER->id));
$group = $DB->get_record('groups', array('id' => $mygroup), '*', MUST_EXIST);
$status = peerassessment_get_status($peerassessment, $group);
$isopen = peerassessment_is_open($peerassessment, $mygroup);
$duedate = peerassessment_due_date($peerassessment);

// No point in doing any checks if it's not open.
$hidden = false;
$gradinginfo = grade_get_grades($course->id, 'mod', 'peerassessment', $peerassessment->id, array($USER->id));
if ($gradinginfo &&
    isset($gradinginfo->items[0]->grades[$USER->id]) &&
    $gradinginfo->items[0]->grades[$USER->id]->hidden
) {
    $hidden = true;
}

if (!$isopen->code) {
    $data = array();

    // How I graded others.
    $data['igraded'] = peerassessment_grade_by_user($peerassessment, $USER, $membersgradeable);

    $output = $PAGE->get_renderer('mod_peerassessment');
    $data['files'] = peerassessment_submission_files($context, $group);
    $data['outstanding'] = peerassessment_outstanding($peerassessment, $group);

    // If graded and grade not hidden in gradebook.
    if ($status->code == PEERASSESSMENT_STATUS_GRADED && !$hidden) {
        // My grade.
        $data['mygrade'] = peerassessment_get_grade($peerassessment, $group, $USER);

        // Feedback.
        $data['feedback'] = $submission->feedbacktext;
        $data['feedback_files'] = peerassessment_feedback_files($context, $group);
    }

    $summary = new peerassessment_summary($group, $data, $membersgradeable, $peerassessment, $status->text .
        ' Not editable because: ' . $isopen->text);
    echo $output->render($summary);
    $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));

    echo $OUTPUT->footer();
    die();
}

// Sending feedback & grades is compulsory, file attachment is not
// therefore we enforce the submission form if the above is not submitted.
if (!$myassessments || $edit == true) {

    $draftitemid = null;
    file_prepare_draft_area($draftitemid, $context->id, 'mod_peerassessment', 'submission', $mygroup,
        peerassessment_get_fileoptions($peerassessment));

    $entry = new stdClass();
    // Add the draftitemid to the form, so that 'file_get_submitted_draft_itemid' can retrieve it.
    $entry->submission = $draftitemid;

    if ($myassessments) {
        $data = peerassessment_grade_by_user($peerassessment, $USER, $membersgradeable);
        $entry->grade = $data->grade;
        $entry->feedback = $data->feedback;
    }

    // Check if there are any files at the time of opening the form.
    $files = peerassessment_submission_files($context, $group);

    $mform = new mod_peerassessment_submissions_form(new moodle_url('submissions.php'), array('id' => $id, 'files' => count($files),
        'fileupload' => true, 'peers' => $membersgradeable, 'fileoptions' => peerassessment_get_fileoptions($peerassessment)));
    $mform->set_data($entry);
    $mform->display();

    $params = array(
                'context' => $context
            );

    $event = \mod_peerassessment\event\submission_viewed::create($params);
    $event->trigger();

} else {
    $data = array();

    // How I graded others.
    $data['igraded'] = peerassessment_grade_by_user($peerassessment, $USER, $membersgradeable);

    $output = $PAGE->get_renderer('mod_peerassessment');
    $data['files'] = peerassessment_submission_files($context, $group);
    $data['outstanding'] = peerassessment_outstanding($peerassessment, $group);

    // If graded.
    if ($status->code == PEERASSESSMENT_STATUS_GRADED && !$hidden) {
        // My grade.
        $data['mygrade'] = peerassessment_get_grade($peerassessment, $group, $USER);

        // Feedback.
        $data['feedback'] = $submission->feedbacktext;
        $data['feedback_files'] = peerassessment_feedback_files($context, $group);
    }
    $summary = new peerassessment_summary($group, $data, $membersgradeable, $peerassessment, $status->text .
        ' Editable because: ' . $isopen->text);
    echo $output->render($summary);
    $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));
    echo $OUTPUT->single_button($url, 'Edit submission');

}
echo $OUTPUT->footer();