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
 * Services.
 *
 * @package    mod_peerwork
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_peerwork_unlock_grader' => [
        'classname' => 'mod_peerwork\external',
        'methodname' => 'unlock_grader',
        'description' => 'Unlock a student\'s editing status.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_peerwork_unlock_submission' => [
        'classname' => 'mod_peerwork\external',
        'methodname' => 'unlock_submission',
        'description' => 'Unlock a submission allowing students to change it.',
        'type' => 'write',
        'ajax' => true,
    ],
];
