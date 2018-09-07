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
require_once($CFG->dirroot . '/mod/peerassessment/grade_form.php');
require_once("$CFG->libdir/filestorage/zip_archive.php");

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('peerassessment', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$peerassessment = $DB->get_record('peerassessment', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/peerassessment:grade', $context);

$params = array(
        'context' => $context
    );

$event = \mod_peerassessment\event\submissions_downloaded::create($params);
$event->trigger();

$allgroups = groups_get_all_groups($course->id, 0, $groupingid);
$allfiles = array();
$fs = get_file_storage();
$zip = new zip_archive();
$uniqid = uniqid();

if (!$dir = make_temp_directory('peerassessment_' . $peerassessment->id) . '_' . $uniqid) {
    die();
}
$zip->open($dir);

foreach ($allgroups as $group) {
    $groupfiles = array();
    if ($files = $fs->get_area_files($context->id, 'mod_peerassessment', 'submission', $group->id, 'sortorder', false)) {
        foreach ($files as $file) {
            $filepathinarchive = $group->name . '/' . $file->get_filename();

            $file->archive_file($zip, $filepathinarchive);
        }
        $allfiles[$group->name] = $groupfiles;
    }
}

if ($zip->close()) {
    send_temp_file($dir, clean_filename($peerassessment->name . '-'. $peerassessment->id) . ".zip");
} 