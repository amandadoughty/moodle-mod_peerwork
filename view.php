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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/peerwork/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or ...
$n = optional_param('n', 0, PARAM_INT); // ... peerwork instance ID - it should be named as the first character of the module.
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

$groupingid = $peerwork->pwgroupingid;
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$modinfo = get_fast_modinfo($course);
$cminfo = $modinfo->get_cm($cm->id);
$info = new \core_availability\info_module($cminfo);

require_capability('mod/peerwork:view', $context);

$params = array(
    'objectid' => $cm->instance,
    'context' => $context
);

$PAGE->set_url('/mod/peerwork/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($peerwork->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('limitedwidth');

$event = \mod_peerwork\event\course_module_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $peerwork);
$event->trigger();

// Teacher view.
if (has_capability('mod/peerwork:grade', $context)) {

    // Output starts here.
    echo $OUTPUT->header();

    $allgroups = groups_get_all_groups($course->id, 0, $groupingid);
    $anynongraded = false;

    $t = new html_table();
    $t->attributes['class'] = 'userenrolment';
    $t->id = 'mod-peerwork-summary-table';
    $t->head = [
        get_string('group'),
        get_string('nomembers', 'mod_peerwork'),
        get_string('nopeergrades', 'mod_peerwork'),
        get_string('status'),
        get_string('grade', 'mod_peerwork'),
        ''
    ];

    foreach ($allgroups as $group) {
        $members = groups_get_members($group->id);
        // Filter groups based on any restrictions.
        $newmembers = $info->filter_user_list($members);

        if ($members && !$newmembers) {
            continue;
        }

        $submission = $DB->get_record('peerwork_submission', array('peerworkid' => $peerwork->id, 'groupid' => $group->id));
        $status = peerwork_get_status($peerwork, $group, $submission);
        $grader = new mod_peerwork\group_grader($peerwork, $group->id, $submission);
        $wasgraded = $grader->was_graded();
        $detailsurl = new moodle_url('details.php', ['id' => $cm->id, 'groupid' => $group->id]);
        $anynongraded = $anynongraded || !$wasgraded;

        $menu = new action_menu();
        $menu->add_secondary_action(new action_link(
            $detailsurl,
            $wasgraded ? get_string('edit') : get_string('grade', 'mod_peerwork')
        ));
        $menu->add_secondary_action(new action_link(
            new moodle_url('export.php', ['id' => $cm->id, 'groupid' => $group->id, 'sesskey' => sesskey()]),
            get_string('export', 'mod_peerwork')
        ));

        if (!$wasgraded) {
            $menu->add_secondary_action(new action_link(
                new moodle_url('clearsubmissions.php', ['id' => $cm->id, 'groupid' => $group->id, 'sesskey' => sesskey()]),
                get_string('clearsubmission', 'mod_peerwork'),
                new confirm_action(get_string('confimrclearsubmission', 'mod_peerwork'))
            ));
        }

        if ($status->code == PEERWORK_STATUS_GRADED) {
            $menu->add_secondary_action(new action_link(
                new moodle_url('release.php', ['id' => $cm->id, 'groupid' => $group->id, 'sesskey' => sesskey()]),
                get_string('releasegrades', 'mod_peerwork')
            ));
        }

        $duedate = peerwork_due_date($peerwork);

        if ($duedate !== PEERWORK_DUEDATE_PASSED) {
            $PAGE->requires->js_call_amd('mod_peerwork/inplace_editable');
        }

        $gradeinplace = new core\output\inplace_editable(
            'mod_peerwork',
            'groupgrade_' . $peerwork->id,
            $group->id,
            true,
            $wasgraded ? format_float($grader->get_grade(), -1) : '-',
            $wasgraded ? $grader->get_grade() : null,
            get_string('editgrade', 'mod_peerwork', $group->name),
            get_string('editgrade', 'mod_peerwork', $group->name)
        );
        $gradecell = new html_table_cell($OUTPUT->render($gradeinplace));
        $gradecell->attributes['class'] = 'inplace-grading';

        $row = new html_table_row();
        $row->cells = array(
            $OUTPUT->action_link($detailsurl, $group->name),
            count($members),
            peerwork_get_number_peers_graded($peerwork->id, $group->id),
            $status->text,
            $gradecell,
            $OUTPUT->render($menu)
        );
        $t->data[] = $row;
    }
    echo html_writer::table($t);

    echo $OUTPUT->box_start('generalbox', null);

    echo $OUTPUT->single_button(new moodle_url('export.php', array('id' => $cm->id, 'groupid' => 0, 'sesskey' => sesskey())),
        get_string("exportxls", 'mod_peerwork'), 'get');


    echo $OUTPUT->single_button(new moodle_url('downloadallsubmissions.php', array('id' => $cm->id)),
        get_string("downloadallsubmissions", 'mod_peerwork'), 'post');





    echo $OUTPUT->single_button(new moodle_url('release.php', ['id' => $cm->id,  'groupid' => 0, 'sesskey' => sesskey()]),
        get_string("releaseallgradesforallgroups", 'mod_peerwork'), 'get');

    if ($anynongraded) {
        $clearbutton = new single_button(
            new moodle_url('clearsubmissions.php', ['id' => $cm->id, 'groupid' => 0, 'sesskey' => sesskey()]),
            get_string('clearallsubmissionsforallgroups', 'mod_peerwork')
        );
        $clearbutton->add_confirm_action(get_string('confimrclearsubmissions', 'mod_peerwork'));
        echo $OUTPUT->render($clearbutton);
    }

    echo $OUTPUT->box_end();

    // Student view.
} else {
    // Student output displays summary of submissions made so far and provides a button to start editing.
    $mygroup = peerwork_get_mygroup($course->id, $USER->id, $groupingid);
    $membersgradeable = peerwork_get_peers($course->id, $peerwork, $groupingid, $mygroup);
    $groupmode = groups_get_activity_groupmode($cm);

    // Check if already submitted.
    $submission = $DB->get_record('peerwork_submission', array('peerworkid' => $peerwork->id, 'groupid' => $mygroup));

    // Check if I already graded my peers.
    $myassessments = $DB->get_records('peerwork_peers', array('peerwork' => $peerwork->id, 'gradedby' => $USER->id));
    $group = $DB->get_record('groups', array('id' => $mygroup), '*', MUST_EXIST);
    $status = peerwork_get_status($peerwork, $group);

    $duedate = peerwork_due_date($peerwork);
    $renderer = $PAGE->get_renderer('mod_peerwork');

    // Establish the status of the assessment.
    $isopen = peerwork_is_open($peerwork, $mygroup);

    // Collect data on how this user graded their peers.
    $data = array();
    $data['files']       = peerwork_submission_files($context, $group);
    $data['outstanding'] = peerwork_outstanding($peerwork, $group);

    // Get the grade info from the gradebook.
    $gradinginfo = grade_get_grades(
        $course->id,
        'mod',
        'peerwork',
        $peerwork->id,
        [$USER->id]
    );

    if (!$isopen->code || !$edit) {

        // If graded and grade not hidden in gradebook.
        if (peerwork_can_student_view_grade_and_feedback_from_status($status, $gradinginfo)) {
            // Get the grade from the gradebook.
            if (isset($gradinginfo->items[0]->grades[$USER->id])) {
                $grade = $gradinginfo->items[0]->grades[$USER->id];
                $data['mygrade'] = $grade->str_grade;
            }

            $data['feedback'] = $submission->feedbacktext;
            $data['feedback_files'] = peerwork_feedback_files($context, $group);
            $pac = new mod_peerwork_criteria($peerwork->id);
            $data['criteria'] = $pac->get_criteria();

            if (peerwork_can_students_view_peer_grades($peerwork)) {
                $data['peergrades'] = peerwork_get_peer_grades_received($peerwork->id, $mygroup, $USER->id);
            }
            if (peerwork_can_students_view_peer_justification($peerwork)) {
                $justificationtype = $peerwork->justificationtype;
                $data['justificationtype'] = $justificationtype;
                $data['justifications'] = peerwork_get_justifications_received($peerwork->id, $mygroup, $USER->id);
            }
        }

        // Editable status.
        if (!$isopen->code) {
            $editabletext = get_string('noteditablebecause', 'mod_peerwork', $isopen->text);
        } else {
            $editabletext = get_string('editablebecause', 'mod_peerwork', $isopen->text);
        }

        // Output starts here.
        echo $OUTPUT->header();

        // Show mod details.
        echo $OUTPUT->heading(format_string($peerwork->name));
        echo $OUTPUT->box(format_string($peerwork->intro));
        $summary = new mod_peerwork\output\peerwork_summary($group, $data, $membersgradeable, $peerwork,
            $status->text . ' ' . $editabletext);
        echo $renderer->render($summary);

        // Submissions are allowed.
        if ($isopen->code) {
            $btnlabel = get_string('editsubmission', 'mod_peerwork');
            if (!peerwork_get_number_peers_graded($peerwork->id, $group->id, $USER->id)) {
                $btnlabel = get_string('addsubmission', 'mod_peerwork');
            }
            $url = new moodle_url('view.php', ['edit' => true, 'id' => $id]);
            echo $OUTPUT->single_button($url, $btnlabel, 'get');
        }

        echo $OUTPUT->footer();
        die();
    }

    // If we got here, it means that submissions are opened and the student is about to edit etheir submission.
    // File attachment is not compulsory therefore we enforce the submission form if the above is not submitted.
    $foptions = peerwork_get_fileoptions($peerwork);
    $draftitemid = file_get_submitted_draft_itemid('submission');
    file_prepare_draft_area($draftitemid, $context->id, 'mod_peerwork', 'submission', $mygroup, $foptions);

    $entry = new stdClass();
    $entry->submission = $draftitemid;

    if ($myassessments) {
        $data = peerwork_grade_by_user($peerwork, $USER, $membersgradeable);
        $entry->grade = $data->grade;
        $entry->feedback = $data->feedback;
    }

    // Check if there are any files at the time of opening the form.
    $files = peerwork_submission_files($context, $group);

    $url = new moodle_url('view.php', array('edit' => true, 'id' => $id));
    $mform = new mod_peerwork_submissions_form($url->out(false), array('id' => $id, 'filecount' => count($files),
        'peerworkid' => $peerwork->id, 'fileupload' => $foptions['maxfiles'] > 0, 'peers' => $membersgradeable,
        'fileoptions' => $foptions, 'peerwork' => $peerwork, 'submission' => $submission, 'files' => $files,
        'myassessments' => $myassessments));
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

} // End of student output
echo $OUTPUT->footer();
