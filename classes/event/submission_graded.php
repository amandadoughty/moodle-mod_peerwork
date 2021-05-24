<?php
// This file is part of Moodle - http://moodle.org/
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
 * The submission_graded event.
 *
 * @package    mod_peerwork
 * @copyright  2015 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The submission_graded event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int grade: Grade given to group..
 *      - int groupid: The group ID.
 *      - string groupname: The name of the group.
 * }
 *
 * @since     Moodle 2.8
 * @copyright 2015 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class submission_graded extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'peerwork_submission';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubmission_graded', 'mod_peerwork');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Grade group: (id={$this->other['groupid']}, groupname={$this->other['groupname']}).
        Grade: {$this->other['grade']} / 100 " .
            "in the 'peerwork' submission with " .
            "id '{$this->objectid}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url(
            '/mod/peerwork/details.php',
            array(
                'id' => $this->contextinstanceid,
                'groupid' => $this->other['groupid']
                )
            );
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['grade'])) {
            throw new \coding_exception('The \'grade\' value must be set in other.');
        }
        if (!isset($this->other['groupid'])) {
            throw new \coding_exception('The \'groupid\' value must be set in other.');
        }
        if (!isset($this->other['groupname'])) {
            throw new \coding_exception('The \'groupname\' value must be set in other.');
        }
    }
}
