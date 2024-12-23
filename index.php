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
 * Index.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_peerwork\event\course_module_instance_list_viewed;

require_once(dirname(__FILE__, 3) . '/config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

$params = [
    'context' => $coursecontext,
];

$event = course_module_instance_list_viewed::create($params);
$event->trigger();

$PAGE->set_url('/mod/peerwork/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

if (!$peerworks = get_all_instances_in_course('peerwork', $course)) {
    notice(get_string('nopeerworks', 'peerwork'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head = [get_string('week'), get_string('name')];
    $table->align = ['center', 'left'];
} else if ($course->format == 'topics') {
    $table->head = [get_string('topic'), get_string('name')];
    $table->align = ['center', 'left', 'left', 'left'];
} else {
    $table->head = [get_string('name')];
    $table->align = ['left', 'left', 'left'];
}

foreach ($peerworks as $peerwork) {
    if (!$peerwork->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/peerwork/view.php', ['id' => $peerwork->coursemodule]),
            format_string($peerwork->name, true),
            ['class' => 'dimmed']);
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/peerwork/view.php', ['id' => $peerwork->coursemodule]),
            format_string($peerwork->name, true));
    }

    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = [$peerwork->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'peerwork'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();
