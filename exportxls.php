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
require_once($CFG->libdir . '/excellib.class.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('peerassessment', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$peerassessment = $DB->get_record('peerassessment', array('id' => $cm->instance), '*', MUST_EXIST);

$groupingid = $peerassessment->submissiongroupingid;
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$params = array(
        'context' => $context
    );

$event = \mod_peerassessment\event\submissions_exported::create($params);
$event->trigger();

require_capability('mod/peerassessment:grade', $context);

$header = array(
    'Student',
    'Group',
    'Group average total',
    'Group average score',
    'Coursework grade',
    'Final grade',
    'Graded on',
    'Graded by',
    'Feedback'
);

$content = array();
$allgroups = groups_get_all_groups($course->id, 0, $groupingid);

foreach ($allgroups as $group) {

    $groupid = $group->id;
    $membersgradeable = peerassessment_get_peers($course, $peerassessment, $groupingid, $groupid);
    $submission = $DB->get_record('peerassessment_submission',
            array('assignment' => $peerassessment->id, 'groupid' => $groupid));
    $members = groups_get_members($groupid);

    foreach ($members as $member) {
        $row = array();
        $row[] = fullname($member);
        $row[] = $group->name;
        $row[] = peerassessment_get_individualaverage($peerassessment, $group, $member);
        $row[] = peerassessment_get_groupaverage($peerassessment, $group);
        if (isset($submission->grade)) {
            $row[] = $submission->grade;
        }
        $row[] = peerassessment_get_grade($peerassessment, $group, $member);
        if (isset($submission->timegraded)) {
            $row[] = userdate($submission->timegraded);
        }
        if (isset($submission->gradedby)) {
            $teacher = $DB->get_record('user', array('id' => $submission->gradedby));
            $row[] = fullname($teacher);
        }
        if (isset($submission->feedbacktext)) {
            $row[] = html_to_text($submission->feedbacktext);
        }
        $content[] = $row;
    }

}

$workbook = new MoodleExcelWorkbook(clean_filename($peerassessment->name.'-'.$peerassessment->id).'.xlsx', 'Excel2007');

$worksheet = $workbook->add_worksheet('All Grades');

for ($i = 0; $i < count($header); $i++) {
    $worksheet->write_string(0, $i, $header[$i]);
}

for ($i = 0; $i < count($content); $i++) {
    for ($j = 0; $j < count($content[$i]); $j++) {
        $worksheet->write_string ($i + 1, $j, $content[$i][$j]);
    }
}

foreach ($allgroups as $group) {
    $worksheet = $workbook->add_worksheet($group->name);
    $members = groups_get_members($group->id);
    $membersgradeable = peerassessment_get_peers($course, $peerassessment, $groupingid, $group->id);
    $data = array();
    $header = array('Student');
    foreach ($members as $member) {
        $row = array(fullname($member));
        $header[] = 'Grade for ' . fullname($member);
        $header[] = 'Feedback for ' . fullname($member);
        $grades = peerassessment_grade_by_user($peerassessment, $member, $membersgradeable);
        foreach (groups_get_members($group->id) as $peer) {
            $row[] = $grades->grade[$peer->id];
            $row[] = html_to_text($grades->feedback[$peer->id]);
        }
        $row[] = peerassessment_get_individualaverage($peerassessment, $group, $member);
        $row[] = peerassessment_get_grade($peerassessment, $group, $member);
        $data[] = $row;
    }
    $header[] = 'Average group score';
    $header[] = 'Final grade';

    for ($i = 0; $i < count($header); $i++) {
        $worksheet->write_string(0, $i, $header[$i]);
    }
    for ($i = 0; $i < count($data); $i++) {
        for ($j = 0; $j < count($data[$i]); $j++) {
            $worksheet->write_string ($i + 1, $j, $data[$i][$j]);
        }
    }

}

$workbook->close();
