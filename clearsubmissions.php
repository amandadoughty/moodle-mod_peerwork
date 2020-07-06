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
 * Clear submissions.
 *
 * @package    mod_peerwork
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$groupid = required_param('groupid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'peerwork');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();
require_capability('mod/peerwork:grade', $context);

$PAGE->set_url('/mod/peerwork/clearsubmissions.php', ['id' => $cm->id, 'groupid' => $groupid]);

$peerwork = $DB->get_record('peerwork', ['id' => $cm->instance], '*', MUST_EXIST);

mod_peerwork_clear_submissions($peerwork, $context, $groupid);

redirect(new moodle_url('/mod/peerwork/view.php', ['id' => $cm->id]));
