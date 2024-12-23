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

declare(strict_types=1);

namespace mod_peerwork\completion;

use context_module;
use core_completion\activity_custom_completion;
use grade_grade;
use grade_item;

/**
 * Activity custom completion subclass for the peerwork activity.
 *
 * Class for defining mod_peerwork's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given peerwork instance and a user.
 *
 * @package   mod_peerwork
 * @copyright 2022 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Check user has graded peers requirement for completion.
     *
     * @return bool True if the user has graded their peers.
     */
    protected function check_graded_peers(): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/peerwork/locallib.php');

        $peerwork = $DB->get_record('peerwork', ['id' => $this->cm->instance], '*', MUST_EXIST);

        // Check whether the user has graded all their peers.
        if ($this->cm->customdata['customcompletionrules']['completiongradedpeers']) {
            $groupid = peerwork_get_mygroup($this->cm->course, $this->userid, $peerwork->pwgroupingid, false);

            // The user does not have the expected group.
            if (!$groupid) {
                return false;
            }

            $course = $this->cm->get_course();
            $peers = peerwork_get_peers($course, $peerwork, $peerwork->pwgroupingid, $groupid, $this->userid);
            $gradedcount = $DB->count_records_select(
                'peerwork_peers',
                'peerwork = ?',
                [$peerwork->id],
                'COUNT(DISTINCT gradefor)'
            );
            return count($peers) <= $gradedcount;
        }

        return false;
    }

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        switch ($rule) {
            case 'completiongradedpeers':
                $status = static::check_graded_peers();
                break;
        }

        return empty($status) ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completiongradedpeers',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $description['completiongradedpeers'] = get_string('completiongradedpeers', 'mod_peerwork');

        return $description;
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpassgrade',
            'completiongradedpeers',
        ];
    }
}
