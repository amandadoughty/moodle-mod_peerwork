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
 * Privacy provider.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\privacy;
defined('MOODLE_INTERNAL') || die();

use context;
use context_module;
use grade_scale;
use moodle_recordset;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

require_once($CFG->libdir . '/grade/grade_scale.php');
require_once($CFG->dirroot . '/mod/peerwork/locallib.php');

/**
 * Privacy provider.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider  {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('peerwork_submission', [
            'groupid' => 'privacy:metadata:submission:groupid',
            'userid' => 'privacy:metadata:submission:userid',
            'grade' => 'privacy:metadata:submission:grade',
            'paweighting' => 'privacy:metadata:submission:paweighting',
            'feedbacktext' => 'privacy:metadata:submission:feedbacktext',
            'timegraded' => 'privacy:metadata:submission:timegraded',
            'gradedby' => 'privacy:metadata:submission:gradedby',
            'released' => 'privacy:metadata:submission:released',
            'releasedby' => 'privacy:metadata:submission:releasedby',
            'timecreated' => 'privacy:metadata:submission:timecreated',
            'timemodified' => 'privacy:metadata:submission:timemodified',
        ], 'privacy:metadata:submission');

        $collection->add_database_table('peerwork_peers', [
            'grade' => 'privacy:metadata:peers:grade',
            'gradedby' => 'privacy:metadata:peers:gradedby',
            'gradefor' => 'privacy:metadata:peers:gradefor',
            'feedback' => 'privacy:metadata:peers:feedback',
            'timecreated' => 'privacy:metadata:peers:timecreated',
            'timemodified' => 'privacy:metadata:peers:timemodified',
            'peergrade' => 'privacy:metadata:peers:peergrade',
            'overriddenby' => 'privacy:metadata:peers:overriddenby',
            'comments' => 'privacy:metadata:peers:comments',
            'timeoverridden' => 'privacy:metadata:peers:timeoverridden',
        ], 'privacy:metadata:peers');

        $collection->add_database_table('peerwork_justification', [
            'gradedby' => 'privacy:metadata:justification:gradedby',
            'gradefor' => 'privacy:metadata:justification:gradefor',
            'justification' => 'privacy:metadata:justification:justification'
        ], 'privacy:metadata:justification');

        $collection->add_database_table('peerwork_grades', [
            'userid' => 'privacy:metadata:grades:userid',
            'prelimgrade' => 'privacy:metadata:grades:prelimgrade',
            'grade' => 'privacy:metadata:grades:grade',
            'revisedgrade' => 'privacy:metadata:grades:revisedgrade',
        ], 'privacy:metadata:grades');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $defaultparams = [
            'modulename' => 'peerwork',
            'contextlevel' => CONTEXT_MODULE
        ];
        $defaultsql = "SELECT ctx.id
                         FROM {course_modules} cm
                         JOIN {modules} m 
                           ON cm.module = m.id 
                          AND m.name = :modulename
                         JOIN {context} ctx
                           ON cm.id = ctx.instanceid
                          AND ctx.contextlevel = :contextlevel
                         JOIN {peerwork} p
                           ON cm.instance = p.id ";

        $sql = $defaultsql .
            "JOIN {peerwork_submission} ps
               ON ps.peerworkid = p.id
            WHERE ps.userid = :userid1
               OR ps.gradedby = :userid2
               OR ps.releasedby = :userid3";
        $params = $defaultparams + ['userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid];
        $contextlist->add_from_sql($sql, $params);

        $sql = $defaultsql .
            "JOIN {peerwork_peers} pp
               ON pp.peerwork = p.id
            WHERE pp.gradedby = :userid1
               OR pp.gradefor = :userid2
               OR pp.overriddenby = :userid3";
        $params = $defaultparams + ['userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid];
        $contextlist->add_from_sql($sql, $params);

        $sql = $defaultsql .
            "JOIN {peerwork_justification} pj
               ON pj.peerworkid = p.id
            WHERE pj.gradedby = :userid1
               OR pj.gradefor = :userid2";
        $params = $defaultparams + ['userid1' => $userid, 'userid2' => $userid];
        $contextlist->add_from_sql($sql, $params);

        $sql = $defaultsql .
            "JOIN {peerwork_grades} pg
               ON pg.peerworkid = p.id
            WHERE pg.userid = :userid";
        $params = $defaultparams + ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('peerwork', $context->instanceid);
        if (!$cm) {
            return;
        }

        $id = $cm->instance;
        $userlist->add_from_sql('userid', 'SELECT userid FROM {peerwork_submission} WHERE peerworkid = ?', [$id]);
        $userlist->add_from_sql('gradedby', 'SELECT gradedby FROM {peerwork_submission} WHERE peerworkid = ?', [$id]);
        $userlist->add_from_sql('releasedby', 'SELECT releasedby FROM {peerwork_submission} WHERE peerworkid = ?', [$id]);
        $userlist->add_from_sql('gradedby', 'SELECT gradedby FROM {peerwork_peers} WHERE peerwork = ?', [$id]);
        $userlist->add_from_sql('gradefor', 'SELECT gradefor FROM {peerwork_peers} WHERE peerwork = ?', [$id]);
        $userlist->add_from_sql('overriddenby', 'SELECT overriddenby FROM {peerwork_peers} WHERE peerwork = ?', [$id]);
        $userlist->add_from_sql('gradedby', 'SELECT gradedby FROM {peerwork_justification} WHERE peerworkid = ?', [$id]);
        $userlist->add_from_sql('gradefor', 'SELECT gradefor FROM {peerwork_justification} WHERE peerworkid = ?', [$id]);
        $userlist->add_from_sql('userid', 'SELECT userid FROM {peerwork_grades} WHERE peerworkid = ?', [$id]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        $peerworkidstocmids = static::get_peerwork_ids_to_cmids_from_contexts($contextlist->get_contexts());
        $peerworkids = array_keys($peerworkidstocmids);
        if (empty($peerworkids)) {
            return;
        }

        // Initialise the data in each context.
        foreach ($contextlist->get_contexts() as $context) {
            $data = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $data);
        }

        list($insql, $inparams) = $DB->get_in_or_equal($peerworkids, SQL_PARAMS_NAMED);

        // Fetch the record for the overall grade.
        $sql = "SELECT g.id, g.prelimgrade, g.grade, g.revisedgrade, s.grade AS groupgrade, s.timegraded, s.timecreated,
                    s.timemodified, s.feedbacktext, s.feedbackformat, s.peerworkid, s.groupid
                  FROM {peerwork_grades} g
                  JOIN {peerwork_submission} s
                    ON s.id = g.submissionid
                 WHERE g.peerworkid $insql
                   AND g.userid = :userid";
        $params = ['userid' => $userid] + $inparams;
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $context = context_module::instance($peerworkidstocmids[$record->peerworkid]);
            $path = [get_string('privacy:path:grade', 'mod_peerwork')];
            writer::with_context($context)->export_area_files($path, 'mod_peerwork', 'submission', $record->groupid);
            writer::with_context($context)->export_area_files($path, 'mod_peerwork', 'feedback_files', $record->groupid);
            writer::with_context($context)->export_data($path, (object) [
                'group_grade' => $record->groupgrade,
                'group_feedback' => format_text($record->feedbacktext, $record->feedbackformat, ['context' => $context]),
                'group_submission_created_on' => $record->timecreated ? transform::datetime($record->timecreated) : '-',
                'group_submission_updated_on' => $record->timemodified ? transform::datetime($record->timemodified) : '-',
                'your_calculated_grade' => $record->prelimgrade,
                'your_grade' => $record->grade,
                'your_revised_grade' => $record->revisedgrade ?? '-',
                'time_graded' => $record->timegraded ? transform::datetime($record->timegraded) : '-',
            ]);
        }
        $recordset->close();

        // Fetch the records associated with a submission.
        $sql = "SELECT s.*
                  FROM {peerwork_submission} s
                 WHERE s.peerworkid $insql
                   AND (s.userid = :userid1 OR s.gradedby = :userid2 OR s.releasedby = :userid3)
              ORDER BY s.peerworkid";
        $params = ['userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid] + $inparams;
        $recordset = $DB->get_recordset_sql($sql, $params);
        static::recordset_loop_and_export($recordset, 'peerworkid', [],
            function($carry, $record) use ($user, $userid, $peerworkidstocmids) {
                $context = context_module::instance($peerworkidstocmids[$record->peerworkid]);
                $path = [get_string('privacy:path:submission', 'mod_peerwork')];
                writer::with_context($context)->export_area_files($path, 'mod_peerwork', 'submission', $record->groupid);
                $carry[] = (object) [
                    'submitted_or_updated_by_you' => transform::yesno($record->userid == $userid),
                    'graded_by_you' => transform::yesno($record->gradedby == $userid),
                    'grade_released_by_you' => transform::yesno($record->releasedby == $userid),
                    'submitted_on' => $record->timecreated ? transform::datetime($record->timecreated) : '-',
                    'updated_on' => $record->timemodified ? transform::datetime($record->timemodified) : '-',
                    'graded_on' => $record->timegraded ? transform::datetime($record->timegraded) : '-',
                    'released_on' => $record->released ? transform::datetime($record->released) : '-',
                ];
                return $carry;
            },
            function($peerworkid, $data) use ($peerworkidstocmids) {
                $context = context_module::instance($peerworkidstocmids[$peerworkid]);
                $path = [get_string('privacy:path:submission', 'mod_peerwork')];
                writer::with_context($context)->export_data($path, (object) [
                    'submissions' => $data
                ]);
            }
        );

        // Local scale cache.
        $scalecache = [];
        $scalegetter = function($scaleid) use (&$scalecache) {
            if (!isset($scalecache[$scaleid])) {
                $scale = grade_scale::fetch(['id' => $scaleid]);
                $scale->load_items();
                $scalecache[$scaleid] = $scale;
            }
            return $scalecache[$scaleid];
        };

        // Fetch the records for peer grading stuff.
        $sql = "SELECT
                    p.id,
                    p.peerwork AS peerworkid,
                    p.grade,
                    p.gradedby,
                    p.gradefor,
                    p.peergrade,
                    p.overriddenby,
                    p.feedback,
                    p.timecreated,
                    p.timemodified,
                    p.comments,
                    p.timeoverridden,
                    c.description AS c_desc,
                    c.descriptionformat AS c_descformat,
                    c.grade AS c_grade,
                    j.justification,
                    pw.justification AS pw_justification
                  FROM {peerwork_peers} p
                  JOIN {peerwork} pw
                    ON pw.id = p.peerwork
                  JOIN {peerwork_criteria} c
                    ON c.id = p.criteriaid
             LEFT JOIN {peerwork_justification} j
                    ON j.gradedby = p.gradedby
                   AND j.gradefor = p.gradefor
                   AND j.peerworkid = p.peerwork
                 WHERE pw.id $insql
                   AND (p.gradedby = :userid1 OR p.gradefor = :userid2 OR p.overriddenby = :userid3)
              ORDER BY p.peerwork, p.id";
        $params = ['userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid] + $inparams;
        $recordset = $DB->get_recordset_sql($sql, $params);

        static::recordset_loop_and_export($recordset, 'peerworkid', [],
            function($carry, $record) use ($user, $userid, $scalegetter, $peerworkidstocmids) {
                $context = context_module::instance($peerworkidstocmids[$record->peerworkid]);
                $scale = $record->c_grade < 0 ? $scalegetter(abs($record->c_grade)) : null;
                $grade = $scale ? static::transform_scale_grade($scale, $record->grade) : $record->grade;
                $peergrade = $scale ? static::transform_scale_grade($scale, $record->peergrade) : $record->peergrade;

                // We do not reveal the justification when it was specifically hidden because peers
                // would have been told that the justification would not be given, and revealing it
                // is not advisable.
                $showjustification = $record->pw_justification != MOD_PEERWORK_JUSTIFICATION_HIDDEN;

                $carry[] = (object) [
                    'peer_graded_is_you' => transform::yesno($record->gradefor == $userid),
                    'peer_grading_is_you' => transform::yesno($record->gradedby == $userid),
                    'peer_override_is_you' => transform::yesno($record->overriddenby == $userid),
                    'grade_given' => $grade,
                    'peergrade_given' => $peergrade,
                    'feedback_given' => $record->feedback,
                    'justification_given' => $showjustification ? $record->justification : '',
                    'comments_given' => $record->comments,
                    'time_graded' => transform::datetime($record->timecreated),
                    'time_grade_updated' => transform::datetime($record->timemodified),
                    'time_grade_overridden' => transform::datetime($record->timeoverridden),
                    'criterion' => format_text($record->c_desc, $record->c_descformat, ['context' => $context])
                ];
                return $carry;
            },
            function($peerworkid, $data) use ($peerworkidstocmids) {
                $context = context_module::instance($peerworkidstocmids[$peerworkid]);
                writer::with_context($context)->export_data([get_string('privacy:path:peergrades', 'mod_peerwork')], (object) [
                    'grades' => $data
                ]);
            }
        );
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('peerwork', $context->instanceid);
        if (!$cm) {
            return;
        }

        $id = $cm->instance;
        $DB->delete_records('peerwork_peers', ['peerwork' => $id]);
        $DB->delete_records('peerwork_justification', ['peerworkid' => $id]);
        $DB->delete_records('peerwork_submission', ['peerworkid' => $id]);
        $DB->delete_records('peerwork_grades', ['peerworkid' => $id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;

        $peerworkidstocmids = static::get_peerwork_ids_to_cmids_from_contexts($contextlist->get_contexts());
        $peerworkids = array_keys($peerworkidstocmids);
        if (empty($peerworkids)) {
            return;
        }

        // Delete all the things that do not affect the well functioning of the plugin.
        list($insql, $inparams) = $DB->get_in_or_equal($peerworkids, SQL_PARAMS_NAMED);

        // Delete the records of the teacher grade received.
        $sql = "peerworkid $insql AND userid = :userid";
        $params = ['userid' => $userid] + $inparams;
        $DB->delete_records_select('peerwork_grades', $sql, $params);

        // Delete the records of the peer grade received, or given.
        $sql = "peerwork $insql AND (gradedby = :userid1 OR gradefor = :userid2)";
        $params = ['userid1' => $userid, 'userid2' => $userid] + $inparams;
        $DB->delete_records_select('peerwork_peers', $sql, $params);

        // Delete the records of the peer justification received, or given.
        $sql = "peerworkid $insql AND (gradedby = :userid1 OR gradefor = :userid2)";
        $params = ['userid1' => $userid, 'userid2' => $userid] + $inparams;
        $DB->delete_records_select('peerwork_justification', $sql, $params);

        // We do not delete the submission because it belongs to the group, and removing
        // it would essentially break the module. The same goes for the other fields such
        // as who graded the submission, or released its grades, we cannot delete those records.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('peerwork', $context->instanceid);
        if (!$cm) {
            return;
        }

        // Delete all the things that do not affect the well functioning of the plugin.
        $id = $cm->instance;
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        list($insql2, $inparams2) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete the records of the teacher grade received.
        $sql = "peerworkid = :id AND userid $insql";
        $params = ['id' => $id] + $inparams;
        $DB->delete_records_select('peerwork_grades', $sql, $params);

        // Delete the records of the peer grade received, or given.
        $sql = "peerwork = :id AND (gradedby $insql OR gradefor $insql2)";
        $params = ['id' => $id] + $inparams + $inparams2;
        $DB->delete_records_select('peerwork_peers', $sql, $params);

        // Delete the records of the peer justification received, or given.
        $sql = "peerworkid = :id AND (gradedby $insql OR gradefor $insql2)";
        $params = ['id' => $id] + $inparams + $inparams2;
        $DB->delete_records_select('peerwork_justification', $sql, $params);

        // We do not delete the submission because it belongs to the group, and removing
        // it would essentially break the module. The same goes for the other fields such
        // as who graded the submission, or released its grades, we cannot delete those records.
    }

    /**
     * Return a dict of peerwork IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$peerworkid => $cmid].
     */
    protected static function get_peerwork_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;
        if (empty($cmids)) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT c.id, cm.id AS cmid
              FROM {peerwork} c
              JOIN {modules} m
                ON m.name = :peerwork
              JOIN {course_modules} cm
                ON cm.instance = c.id
               AND cm.module = m.id
             WHERE cm.id $insql";
        $params = array_merge($inparams, ['peerwork' => 'peerwork']);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Return a dict of peerwork IDs mapped to their course module ID.
     *
     * @param context[] $contexts The contexts.
     * @return array In the form of [$peerworkid => $cmid].
     */
    protected static function get_peerwork_ids_to_cmids_from_contexts(array $contexts) {
        $cmids = array_filter(array_map(function($context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                return;
            }
            return $context->instanceid;
        }, $contexts));
        return static::get_peerwork_ids_to_cmids_from_cmids($cmids);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    protected static function recordset_loop_and_export(moodle_recordset $recordset, $splitkey, $initial,
            callable $reducer, callable $export) {

        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }

    /**
     * Transform scale grade.
     *
     * @param grade_scale $scale The scale.
     * @param int $grade The grade.
     * @return string
     */
    protected static function transform_scale_grade(grade_scale $scale, $grade) {
        if ($grade === null) {
            return '-';
        }
        return $scale->scale_items[$grade];
    }
}
