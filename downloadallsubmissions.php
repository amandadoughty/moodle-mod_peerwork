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
 * Download submissions
 *
 * @package    mod_peerwork
 * @copyright  2020 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->libdir . '/filestorage/zip_archive.php');

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'peerwork');
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_sesskey();
require_capability('mod/peerwork:grade', $cm->context);

$peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);
$allgroups = groups_get_all_groups($course->id, 0, $peerwork->pwgroupingid);

// Increase the server timeout to handle the creation and sending of large zip files.
core_php_time_limit::raise();

$fs = get_file_storage();
$groupfiles = [];

foreach ($allgroups as $group) {
    if ($files = $fs->get_area_files($context->id, 'mod_peerwork', 'submission', $group->id, 'sortorder', false)) {

        foreach ($files as $file) {
            if ($file->is_directory() and $file->get_filename() == '.') {
                continue;
            }

            $filepathinarchive = clean_filename($group->name) . DIRECTORY_SEPARATOR . $file->get_filename();
            $groupfiles[$filepathinarchive] = $file;
        }
    }
}

$zipper   = get_file_packer('application/zip');
$filename = shorten_filename(clean_filename($peerwork->name . "-" . date("Ymd")) . ".zip");
$temppath = tempnam($CFG->tempdir . '/', 'peerwork_');

if ($zipper->archive_to_pathname($groupfiles, $temppath)) {
    $params = ['context' => $context];
    $event = \mod_peerwork\event\submissions_downloaded::create($params);
    $event->trigger();
    // Send file and delete after sending.
    send_temp_file($temppath, $filename);
    // We will not get here - send_temp_file calls exit.
} else {
    throw new moodle_exception('cannotdownloaddir', 'repository');
}
