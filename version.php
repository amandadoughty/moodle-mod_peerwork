<?php
// This file is part of a 3rd party created plugin for Moodle - http://moodle.org/.
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

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2019022102;      // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2015111600;      // Requires this Moodle version
$plugin->cron      = 0;               // Period for cron to check this plugin (secs)
$plugin->component = 'mod_peerassessment'; // To check on upgrade, that plugin sits in correct place
$plugin->release   = '3.3+ version';   // Readable release description.
