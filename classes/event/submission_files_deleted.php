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
 * The submission__files_uploaded event.
 *
 * @package    mod_peerwork
 * @copyright  2015 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_peerwork submission _files_uploaded event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string deletedlist: List of content hashes of deleted files.
 * }
 *
 * @package    mod_peerwork
 * @since      Moodle 2.8
 * @copyright  2015 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_files_deleted extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'peerwork_submission';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubmission_files_deleted', 'mod_peerwork');
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/peerwork/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $list = $this->other['deletedlist'];
        $count = count($list);
        return "The user with id '{$this->userid}' deleted {$count} file(s) " .
            "in the submission with id '{$this->objectid}'. The file hashes were: " . implode(', ', $list);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['deletedlist'])) {
            throw new \coding_exception('The \'deletedlist\' value must be set in other.');
        }
    }
}
