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
 * CUL peerwork plugin event handlers.
 *
 * @package    mod_peerwork
 * @copyright  2020 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Event observer.
 *
 * Responds to group events emitted by the Moodle event manager.
 */
class mod_peerwork_observer {

    /**
     * Event handler.
     *
     * Called by observers to handle notification sending.
     *
     * @param \core\event\base $event The event object.
     *
     * @return boolean true
     *
     */
    public static function group_member_added(\core\event\base $event) {
        self::group_members_updated($event, false);
    }

    /**
     * Event handler.
     *
     * Called by observers to handle notification sending.
     *
     * @param \core\event\base $event The event object.
     *
     * @return boolean true
     *
     */
    public static function group_member_removed(\core\event\base $event) {
        self::group_members_updated($event);
    }

    /**
     * Function updates the gradebook when there is a change
     * to the group members.
     *
     * @param \core\event\base $event The event object.
     * @param boolean $removed Whether the change was a member removed.
     *
     */
    protected static function group_members_updated(\core\event\base $event, $removed = true) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/peerwork/lib.php');

        $groupid = $event->objectid;
        $userid = $event->relateduserid;
        $members = groups_get_members($groupid);

        $sql = "SELECT DISTINCT p.*
            FROM {peerwork} p
            INNER JOIN {peerwork_submission} ps
            ON p.id = ps.peerworkid
            INNER JOIN {groups} g
            ON ps.groupid = g.id
            WHERE g.id = :groupid";

        $params = ['groupid' => $groupid];

        try {
            $peerworks = $DB->get_records_sql($sql, $params);
        } catch (exception $e) {
            $params = [
                'context' => $context,
                'objectid' => $peerwork->id,
                'other' => [
                    'error' => $e->getMessage(),
                    'groupid' => $groupid
                ]
            ];

            $newevent = \mod_peerwork\event\gradebookupdate_failed::create($params);
            $newevent->trigger();
        }

        if ($peerworks) {
            foreach ($peerworks as $id => $peerwork) {
                $cm = get_coursemodule_from_instance('peerwork', $peerwork->id, $peerwork->course, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                if ($removed) {
                    // Delete record from peerwork_grades if it exists.
                    $submission = $DB->get_record('peerwork_submission', ['peerworkid' => $peerwork->id, 'groupid' => $groupid]);

                    if ($submission) {
                        $peerworkgrades = peerwork_get_local_grades($peerwork->id, $submission->id);

                        if (isset($peerworkgrades[$userid])) {
                            $record = $peerworkgrades[$userid];
                            $DB->delete_records('peerwork_grades', ['id' => $record->id]);
                        }
                    }

                    // Delete gradebook grade.
                    require_once($CFG->libdir . '/gradelib.php');

                    $grade = [];

                    $gradinginfo = grade_get_grades(
                        $peerwork->course,
                        'mod',
                        'peerwork',
                        $peerwork->id,
                        [$userid]
                    );

                    $grade['userid'] = $userid;
                    $grade['itemid'] = $gradinginfo->items[0]->id;
                    $gradegrade = grade_grade::fetch($grade);

                    if ($gradegrade) {
                        $gradegrade->delete();
                    }

                    /* We do not delete peerwork_peers records as the user may have been
                    removed from the group in error and we do not want to lose their peer grades. */
                }

                try {
                    mod_peerwork_update_calculation($peerwork);
                } catch (exception $e) {
                    $params = [
                        'context' => $context,
                        'objectid' => $peerwork->id,
                        'relateduserid' => $member->id,
                        'other' => [
                            'error' => $e->getMessage(),
                            'groupid' => $groupid
                        ]
                    ];

                    $newevent = \mod_peerwork\event\gradebookupdate_failed::create($params);
                    $newevent->trigger();
                }
            }
        }
    }
}
