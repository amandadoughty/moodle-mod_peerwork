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
 * View provides a summary page the content of which depends if you are the teacher or a submitting student.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/peerwork/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerwork/forms/submissions_form.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->libdir . '/gradelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n = optional_param('n', 0, PARAM_INT); // peerwork instance ID - it should be named as the first character of the module.
$edit = optional_param('edit', false, PARAM_BOOL);

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

$params = array(
    'objectid' => $cm->instance,
    'context' => $context
);

$event = \mod_peerwork\event\course_module_viewed::create($params);
$event->add_record_snapshot('course', $course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
$event->add_record_snapshot($cm->modname, $peerwork);
$event->trigger();


// Print the page header.

$PAGE->set_url('/mod/peerwork/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerwork->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// If not submitted yet, and student and part of a group
// allow for submission.


if (has_capability('mod/peerwork:grade', $context)) {

    // Output starts here.
    echo $OUTPUT->header();

    // Show mod details.
    echo $OUTPUT->heading(format_string($peerwork->name));
    echo $OUTPUT->box(format_string($peerwork->intro));
    /**
     *
     * Teacher output
     * If teacher then display a summary of the groups and the number of submissions made.
     *
     */
    $duedate = peerwork_due_date($peerwork);
    if ($duedate != peerwork_DUEDATE_NOT_USED) {
        echo $OUTPUT->box('Due date: ' . userdate($peerwork->duedate));
        if ($duedate == peerwork_DUEDATE_PASSED) {
            echo $OUTPUT->box('Assessment closed for: ' . format_time(time() - $peerwork->duedate));
        } else {
            echo $OUTPUT->box('Time remaining: ' . format_time(time() - $peerwork->duedate));

        }
    }

    $allgroups = groups_get_all_groups($course->id, 0, $groupingid);

    $t = new html_table();
    $t->attributes['class'] = 'userenrolment';
    $t->id = 'mod-peerwork-summary-table';
    $t->head = array('name', '# members', '# peer grades', 'status', 'actions');
    foreach ($allgroups as $group) {
        $members = groups_get_members($group->id);
        $status = peerwork_get_status($peerwork, $group);
        $grades = peerwork_get_peer_grades($peerwork, $group, $members, false);

        $options = array();
        /* if ($status->code != peerwork_STATUS_SUBMITTED) {
            $options = array('disabled' => true);
        }*/

        $row = new html_table_row();
        $actions = '';
        $status = peerwork_get_status($peerwork, $group);
        if ($status->code == peerwork_STATUS_GRADED) {
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
        get_string("exportxls", 'mod_peerwork'), 'post', array("class" => 'yui3-u singlebutton'));
    echo $OUTPUT->box_end();


} else { // end of teacher only output
    /**
     *
     * Student output displays summary of submissions amde so far and provides a button to start editing.
     *
     */


    $mygroup = peerwork_get_mygroup($course->id, $USER->id, $groupingid);
    $membersgradeable = peerwork_get_peers($course->id, $peerwork, $groupingid, $mygroup);
    $groupmode = groups_get_activity_groupmode($cm);

    // Check if already submitted.
    $submission = $DB->get_record('peerwork_submission', array('assignment' => $peerwork->id, 'groupid' => $mygroup));

    // Check if I already graded my peers.
    $myassessments = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id, 'gradedby' => $USER->id));
    $group = $DB->get_record('groups', array('id' => $mygroup), '*', MUST_EXIST);
    $status = peerwork_get_status($peerwork, $group);

    $duedate = peerwork_due_date($peerwork);

    // No point in doing any checks if it's not open.
    $hidden = false;
    $gradinginfo = grade_get_grades($course->id, 'mod', 'peerwork', $peerwork->id, array($USER->id));
    if ($gradinginfo && isset($gradinginfo->items[0]->grades[$USER->id]) && $gradinginfo->items[0]->grades[$USER->id]->hidden) {
        $hidden = true;
    }

    $output = $PAGE->get_renderer('mod_peerwork');

    // Establish the status of the assessment.
    $isopen = peerwork_is_open($peerwork, $mygroup);

    // Collect data on how this user graded their peers.
    $data = array();
    $data['igraded']     = peerwork_grade_by_user($peerwork, $USER, $membersgradeable);
    $data['files']       = peerwork_submission_files($context, $group);
    $data['outstanding'] = peerwork_outstanding($peerwork, $group);

    if (!$isopen->code) {   // Student and we are due to submit.

        // If graded and grade not hidden in gradebook.
        if ($status->code == peerwork_STATUS_GRADED && !$hidden) {
            // My grade.
            $data['mygrade'] = peerwork_get_grade($peerwork, $group, $USER);

            // Feedback.
            $data['feedback'] = $submission->feedbacktext;
            $data['feedback_files'] = peerwork_feedback_files($context, $group);
        }

        // Output starts here.
        echo $OUTPUT->header();

        // Show mod details.
        echo $OUTPUT->heading(format_string($peerwork->name));
        echo $OUTPUT->box(format_string($peerwork->intro));

        $summary = new peerwork_summary($group, $data, $membersgradeable, $peerwork, $status->text .
            ' Not editable because: ' . $isopen->text);
        echo $output->render($summary);
        $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));

        echo $OUTPUT->footer();
        die();
    }

    // File attachment is not compulsory
    // therefore we enforce the submission form if the above is not submitted.
    $foptions = peerwork_get_fileoptions($peerwork);
    if (!$myassessments || $edit == true) {

        $draftitemid = file_get_submitted_draft_itemid('submission');

        file_prepare_draft_area($draftitemid, $context->id, 'mod_peerwork', 'submission', $mygroup, $foptions);

        $entry = new stdClass();
        // Add the draftitemid to the form, so that 'file_get_submitted_draft_itemid' can retrieve it.
        $entry->submission = $draftitemid;

        if ($myassessments) {
            $data = peerwork_grade_by_user($peerwork, $USER, $membersgradeable);
            $entry->grade = $data->grade;
            $entry->feedback = $data->feedback;
        }

        // Check if there are any files at the time of opening the form.
        $files = peerwork_submission_files($context, $group);

        $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));
        $mform = new mod_peerwork_submissions_form($url->out(false), array('id' => $id, 'files' => count($files),
            'peerworkid' => $peerwork->id, 'fileupload' => $foptions['maxfiles'] > 0, 'peers' => $membersgradeable,
            'fileoptions' => $foptions, 'peerwork' => $peerwork));
        $mform->set_data($entry);

        $redirecturl = new moodle_url('view.php', array('id' => $cm->id));
        if ($mform->is_cancelled()) {
            redirect($redirecturl);

        } else if (($data = $mform->get_data())) {
            peerwork_save($peerwork, $submission, $group, $course, $cm, $context, $data, $draftitemid, $membersgradeable);
            redirect(new moodle_url('view.php', array('id' => $cm->id)));
        }

        // Output starts here.
        echo $OUTPUT->header();

        // Show mod details.
        echo $OUTPUT->heading(format_string($peerwork->name));
        echo $OUTPUT->box(format_string($peerwork->intro));

        $mform->display();
        $params = array(
            'context' => $context
        );

        $event = \mod_peerwork\event\submission_viewed::create($params);
        $event->trigger();

    } else {

        // Output starts here.
        echo $OUTPUT->header();

        // Show mod details.
        echo $OUTPUT->heading(format_string($peerwork->name));
        echo $OUTPUT->box(format_string($peerwork->intro));

        // If graded.
        if ($status->code == peerwork_STATUS_GRADED && !$hidden) {
            // My grade.
            $data['mygrade'] = peerwork_get_grade($peerwork, $group, $USER);

            // Feedback.
            $data['feedback'] = $submission->feedbacktext;
            $data['feedback_files'] = peerwork_feedback_files($context, $group);
        }
        $data['maxfiles'] = $foptions['maxfiles'];
        $summary = new peerwork_summary($group, $data, $membersgradeable, $peerwork, $status->text .
            '<p>Editable because: ' . $isopen->text . '</p>');
        echo $output->render($summary);
        $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));
        echo $OUTPUT->single_button($url, get_string('editsubmission', 'mod_peerwork'), 'get');

    }
} // End of student output
echo $OUTPUT->footer();
