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
 * Unlock submission
 *
 * @package   mod_peerwork
 * @author    Amanda Doughty <amanda.doughty@synergy-learning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');

class unlock_submission extends \external_api {
    /**
     * External function parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'submissionid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Unlock a grader.
     *
     * @param int $submissionid The submission ID.
     *
     * @return bool
     */
    public static function execute($submissionid) {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['submissionid' => $submissionid]);
        $submissionid = $params['submissionid'];

        $submission = $DB->get_record('peerwork_submission', ['id' => $submissionid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerwork', $submission->peerworkid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/peerwork:grade', $context);

        // This function validates that the submission belongs to the peerwork instance.
        mod_peerwork_unlock_submission($submissionid);

        return true;
    }

    /**
     * External function returns.
     *
     * @return \external_value
     */
    public static function execute_returns() {
        return new \external_value(PARAM_BOOL);
    }
}
