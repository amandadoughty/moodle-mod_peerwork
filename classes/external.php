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
 * External.
 *
 * @package    mod_peerwork
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/peerwork/locallib.php');

use context_module;
use external_api;
use external_function_parameters;
use external_value;

/**
 * External.
 *
 * @package    mod_peerwork
 * @copyright  2020 Xi'an Jiaotong-Liverpool University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function unlock_grader_parameters() {
        return new external_function_parameters([
            'peerworkid' => new external_value(PARAM_INT),
            'graderid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Unlock a grader.
     *
     * @param int $peerworkid The peerwork ID.
     * @param int $graderid The grader ID.
     * @return bool
     */
    public static function unlock_grader($peerworkid, $graderid) {
        $params = self::validate_parameters(self::unlock_grader_parameters(),
            ['peerworkid' => $peerworkid, 'graderid' => $graderid]);
        $peerworkid = $params['peerworkid'];
        $graderid = $params['graderid'];

        $cm = get_coursemodule_from_instance('peerwork', $peerworkid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/peerwork:grade', $context);

        mod_peerwork_unlock_grader($peerworkid, $graderid);

        return true;
    }

    /**
     * External function returns.
     *
     * @return external_function_parameters
     */
    public static function unlock_grader_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function unlock_submission_parameters() {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Unlock a submission.
     *
     * @param int $submissionid The submission ID.
     * @return bool
     */
    public static function unlock_submission($submissionid) {
        global $DB;
        $params = self::validate_parameters(self::unlock_submission_parameters(), ['submissionid' => $submissionid]);
        $submissionid = $params['submissionid'];

        $submission = $DB->get_record('peerwork_submission', ['id' => $submissionid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerwork', $submission->peerworkid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/peerwork:grade', $context);

        // This function validates that the submission belongs to the peerwork instance.
        mod_peerwork_unlock_submission($submissionid);

        return true;
    }

    /**
     * External function returns.
     *
     * @return external_function_parameters
     */
    public static function unlock_submission_returns() {
        return new external_value(PARAM_BOOL);
    }
}
