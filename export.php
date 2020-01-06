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
 * Export.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->libdir . '/csvlib.class.php');

$id = required_param('id', PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'peerwork');
$peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
require_capability('mod/peerwork:grade', $cm->context);

$PAGE->set_url(new moodle_url('/mod/peerwork/export.php', ['id' => $id, 'groupid' => $groupid]));

if (empty($groupid)) {
    $groupids = array_keys(groups_get_all_groups($course->id, 0, $cm->groupingid));
} else {
    $groupids = [$groupid];
}

if (empty($groupids)) {
    throw new moodle_exception('nogroups', 'mod_peerwork');
}

$context = $cm->context;
$params = [
    'context' => $context
];
$event = \mod_peerwork\event\submissions_exported::create($params);
$event->trigger();

$headers = [
    get_string('group'),
    get_string('groupsubmittedon', 'mod_peerwork'),
    get_string('student', 'core_grades'),
    get_string('username', 'core'),
    get_string('email', 'core'),
    get_string('groupgrade', 'mod_peerwork'),
    get_string('studentcalculatedgrade', 'mod_peerwork'),
    get_string('studentfinalweightedgrade', 'mod_peerwork'),
    get_string('studentrevisedgrade', 'mod_peerwork'),
    get_string('studentfinalgrade', 'mod_peerwork'),
    get_string('feedback', 'mod_peerwork'),
    get_string('gradedby', 'mod_peerwork'),
    get_string('gradedon', 'mod_peerwork'),
    get_string('releasedby', 'mod_peerwork'),
    get_string('releasedon', 'mod_peerwork'),
];

$filename = clean_filename($peerwork->name . '-' . $id . '_' . ($groupid ? $groupid : 'all'));
$csvexport = new csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

$ingroupparams = [];
$ingroupsql = ' = 0';
if (!empty($groupids)) {
    list($ingroupsql, $ingroupparams) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
}

$stufields = user_picture::fields('u', ['email', 'username'], 'user_id', 'user_');
$graderfields = user_picture::fields('ug', null, 'grader_id', 'grader_');
$releaserfields = user_picture::fields('ur', null, 'reluser_id', 'reluser_');

$uniqid = $DB->sql_concat_join("'-'", ['g.id', 'COALESCE(s.id, 0)', 'COALESCE(u.id, 0)']);
$sql = "SELECT $uniqid, $stufields, $graderfields, $releaserfields,
               s.id AS submissionid, s.grade as groupgrade, s.timegraded, s.released, s.timecreated,
               s.feedbacktext, gg.prelimgrade AS studentcalculatedgrade, gg.grade AS studentgrade,
               gg.revisedgrade, g.name as groupname
          FROM {peerwork} p
          JOIN {groups} g
            ON g.id $ingroupsql
          JOIN {groups_members} gm
            ON gm.groupid = g.id
     LEFT JOIN {user} u
            ON u.id = gm.userid
     LEFT JOIN {peerwork_submission} s
            ON s.groupid = g.id
           AND s.peerworkid = p.id
     LEFT JOIN {peerwork_grades} gg
            ON gg.submissionid = s.id
           AND gg.userid = u.id
     LEFT JOIN {user} ug
            ON ug.id = s.gradedby
     LEFT JOIN {user} ur
            ON ur.id = s.releasedby
         WHERE p.id = :peerworkid
      ORDER BY g.id, u.id";
$params = ['peerworkid' => $peerwork->id] + $ingroupparams;
$recordset = $DB->get_recordset_sql($sql, $params);

foreach ($recordset as $record) {
    $student = user_picture::unalias($record, ['email', 'username'], 'user_id', 'user_');
    $grader = user_picture::unalias($record, null, 'grader_id', 'grader_');
    $releaser = user_picture::unalias($record, null, 'reluser_id', 'reluser_');

    $csvexport->add_data([
        $record->groupname,
        !empty($record->timecreated) ? userdate($record->timecreated) : '',
        fullname($student),
        $student->username,
        $student->email,
        $record->groupgrade ?? '',
        $record->studentcalculatedgrade ?? '',
        $record->studentgrade ?? '',
        $record->revisedgrade ?? '',
        $record->revisedgrade ?? $record->studentgrade ?? '',
        trim(html_to_text($record->feedbacktext ?? '')),
        !empty($grader->id) ? fullname($grader) : '',
        !empty($record->timegraded) ? userdate($record->timegraded) : '',
        !empty($releaser->id) ? fullname($releaser) : '',
        !empty($record->released) ? userdate($record->released) : '',
    ]);
}
$recordset->close();
$csvexport->download_file();
