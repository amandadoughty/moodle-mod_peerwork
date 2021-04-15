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
 * Override.
 *
 * @package    mod_peerwork
 * @copyright  2020 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/peerwork/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');

$id = required_param('id', PARAM_INT);
$peerworkid = required_param('pid', PARAM_INT);
$groupid = required_param('gid', PARAM_INT);
$gradedbyid = required_param('uid', PARAM_INT);

$cm = get_coursemodule_from_id('peerwork', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);

// Print the standard page header and check access rights.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/peerwork/override.php', ['id' => $cm->id, 'groupid' => $groupid]);
$PAGE->set_title(format_string($peerwork->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
require_capability('mod/peerwork:grade', $context);

$gradedby = new stdClass();
$gradedby->id = $gradedbyid;
$members = groups_get_members($groupid);
$grades = peerwork_grades_overrides_by_user($peerwork, $gradedby, $members);
$header = get_string('gradesgivenby', 'peerwork', fullname($members[$gradedby->id]));

$customdata['peerworkid'] = $peerworkid;
$customdata['gradedby'] = $gradedby;
$customdata['groupid'] = $groupid;
$customdata['peers'] = $members;
$customdata['grades'] = $grades;
$customdata['selfgrading'] = $peerwork->selfgrading;

$action = new moodle_url('override.php', ['id' => $id, 'pid' => $peerworkid, 'gid' => $groupid, 'uid' => $gradedbyid]);
$parenturl = new moodle_url('details.php', ['id' => $cm->id, 'groupid' => $groupid]);

$mform = new mod_peerwork_override_form($action->out(false), $customdata);

if ($mform->is_cancelled()) {
    redirect($parenturl);
} else if ($fromform = $mform->get_data()) {
    peerwork_peer_override(
        $fromform->peerworkid,
        $fromform->gradedby,
        $fromform->groupid,
        $fromform->overridden,
        $fromform->gradeoverrides,
        $fromform->comments
    );
    redirect($parenturl);
} else {
    echo $OUTPUT->header();
    echo "<h2>$header</h2>";
    $mform->display();
    echo $OUTPUT->footer();
}
