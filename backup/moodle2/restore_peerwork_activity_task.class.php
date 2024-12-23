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
 * Restore task.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/peerwork/backup/moodle2/restore_peerwork_stepslib.php');

/**
 * Restore task.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_peerwork_activity_task extends restore_activity_task {

    /**
     * Settings.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_peerwork_activity_structure_step('peerwork_structure', 'peerwork.xml'));
    }

    /**
     * Define the contents to decode.
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('peerwork', ['intro'], 'peerwork');

        return $contents;
    }

    /**
     * Define the decoding rules for links.
     *
     * @return restore_decode_rule[] The rules.
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('PEERWORKVIEWBYID', '/mod/peerwork/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('PEERWORKINDEX', '/mod/peerwork/index.php?id=$1', 'course');

        return $rules;

    }

}
