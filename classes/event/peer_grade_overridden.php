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
 * The peer_grade_overridden event.
 *
 * @package    mod_peerwork
 * @copyright  2015 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_peerwork submission created event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int gradefor: Peer who's grade has ben overridden.
 *      - int peergrade: Original grade given.
 *      - int grade: Overridden grade.
 *      - string comments: Reason for the override.
 * }
 *
 * @package    mod_peerwork
 * @since      Moodle 2.8
 * @copyright  2015 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peer_grade_overridden extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'peerwork_peers';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpeer_grade_overridden', 'mod_peerwork');
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/peerwork/overridegrades.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "User with id '{$this->userid}' changed the grade given by the user with id '{$this->relateduserid}' " .
            "to the user with id '{$this->other['gradefor']}'" .
            " from '{$this->other['peergrade']}' to  '{$this->other['grade']}'. Comments: {$this->other['comments']}'";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!$this->relateduserid) {
            throw new \coding_exception('The \'relateduserid\' value must be set.');
        }
        if (!isset($this->other['gradefor'])) {
            throw new \coding_exception('The \'gradefor\' value must be set in other.');
        }
        if (!array_key_exists('peergrade', $this->other)) {
            $this->other['peergrade'] = '-';
        }
        if (!isset($this->other['grade'])) {
            throw new \coding_exception('The \'grade\' value must be set in other.');
        }
        if (!isset($this->other['comments'])) {
            throw new \coding_exception('The \'comments\' value must be set in other.');
        }
    }
}
