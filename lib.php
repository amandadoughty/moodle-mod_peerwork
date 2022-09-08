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
 * Lib.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * All the automagically called functions
 */

defined('MOODLE_INTERNAL') || die();

require_once('locallib.php');

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function peerwork_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the peerwork definition into the database. Called automagically when submitting the mod_form form.
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $peerwork An object from the form in mod_form.php
 * @param mod_peerwork_mod_form $mform The form.
 * @return int The id of the newly inserted peerwork record
 */
function peerwork_add_instance(stdClass $peerwork, mod_peerwork_mod_form $mform = null) {
    global $DB;

    $peerwork->timecreated = time();
    $peerwork->id = $DB->insert_record('peerwork', $peerwork);

    // Now save all the criteria.
    $pac = new mod_peerwork_criteria($peerwork->id);
    $pac->update_instance($peerwork);

    peerwork_grade_item_update($peerwork);

    // Now save the plugin data.
    $calculatorplugins = load_plugins($peerwork, 'peerworkcalculator');

    foreach ($calculatorplugins as $name => $plugin) {
        update_plugin_instance($plugin, $peerwork);
    }

    return $peerwork->id;
}

/**
 * Settings
 * Called automatically when saving peerwork setttings.
 * Updates an instance of the peerwork details in the database,
 * the criteria settings are added to a separate table (peerwork_criteria)
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $peerwork An object from the form in mod_form.php
 * @param mod_peerwork_mod_form $mform The form.
 * @return boolean Success/Fail
 */
function peerwork_update_instance(stdClass $peerwork, mod_peerwork_mod_form $mform = null) {
    global $DB;

    $prevlockediting = $DB->get_field('peerwork', 'lockediting', ['id' => $peerwork->instance], IGNORE_MISSING);

    $prevcalculator = $DB->get_field('peerwork', 'calculator', ['id' => $peerwork->instance], IGNORE_MISSING);

    $peerwork->timemodified = time();
    $peerwork->id = $peerwork->instance;
    $return1 = $DB->update_record('peerwork', $peerwork);

    // Now save all the criteria.
    $pac = new mod_peerwork_criteria($peerwork->id);
    $return2 = $pac->update_instance($peerwork);

    // Update locking across activity.
    if ($prevlockediting != $peerwork->lockediting) {
        if ($peerwork->lockediting) {
            mod_peerwork_lock_editing($peerwork);
        } else {
            mod_peerwork_unlock_editing($peerwork);
        }
    }

    // Update local grades across activity.
    if ($prevcalculator != $peerwork->calculator) {
        mod_peerwork_update_calculation($peerwork);
    }

    peerwork_update_grades($peerwork);

    // Now save the plugin data.
    $calculatorplugins = load_plugins($peerwork, 'peerworkcalculator');

    foreach ($calculatorplugins as $name => $plugin) {
        update_plugin_instance($plugin, $peerwork);
    }

    return $return1 && $return2;
}

/**
 * Removes an instance of the peerwork from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function peerwork_delete_instance($id) {
    global $DB;

    if (!$peerwork = $DB->get_record('peerwork', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('peerwork_peers', ['peerwork' => $id]);
    $DB->delete_records('peerwork_justification', ['peerworkid' => $id]);
    $DB->delete_records('peerwork_criteria', ['peerworkid' => $id]);
    $DB->delete_records('peerwork_submission', ['peerworkid' => $id]);
    $DB->delete_records('peerwork_grades', ['peerworkid' => $id]);
    $DB->delete_records('peerwork', ['id' => $id]);

    return true;
}

/**
 * Returns a small object with summary information about what a user has done.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The current course record.
 * @param stdClass $user The record of the user we are generating report for.
 * @param cm_info $mod The course module info.
 * @param stdClass $peerwork The module instance record.
 * @return stdClass|null
 */
function peerwork_user_outline($course, $user, $mod, $peerwork) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done.
 *
 * @param stdClass $course The current course record.
 * @param stdClass $user The record of the user we are generating report for.
 * @param cm_info $mod The course module info.
 * @param stdClass $peerwork The module instance record.
 * @return void, is supposed to echo directly.
 */
function peerwork_user_complete($course, $user, $mod, $peerwork) {
}

/**
 * Prepares the recent activity data.
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function peerwork_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {

}

/**
 * Prints single activity item.
 *
 * @param stdClass $activity The activity.
 * @param int $courseid The course ID.
 * @param stdClass $detail The detail.
 * @param array $modnames The module names.
 * @param bool $viewfullnames Whether to view full names.
 * @return void
 */
function peerwork_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 **/
function peerwork_cron() {
    return true;
}

/**
 * Returns all other caps used in the module.
 *
 * @return array
 */
function peerwork_get_extra_capabilities() {
    return array();
}

/**
 * Checks if scale is used.
 *
 * @param int $scaleid
 * @return boolean True when used.
 */
function peerwork_scale_used_anywhere($scaleid) {
    global $DB;
    return $scaleid && $DB->record_exists('peerwork_criteria', ['grade' => -$scaleid]);
}

/**
 * Creates or updates grade item for the give peerwork instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $peerwork instance object with extra cmidnumber and modname property
 * @param array $grades The grades.
 * @return void
 */
function peerwork_grade_item_update(stdClass $peerwork, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($peerwork->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax'] = 100;
    $item['grademin'] = 0;

    return grade_update('mod/peerwork', $peerwork->course, 'mod',
        'peerwork', $peerwork->id, 0, $grades, $item);
}

/**
 * Update peerwork grades in the gradebook.
 *
 * This updates the grades based on what was recorded when the educator saved them,
 * and only when the grades have been released. So it is possible that grades won't
 * change when the settings of the module itself change.
 *
 * @param stdClass $peerwork instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param bool $nullifnone Whether to use null if none.
 * @return void
 */
function peerwork_update_grades(stdClass $peerwork, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;

    $sql = "SELECT g.id, g.userid, g.grade, g.revisedgrade, s.feedbacktext, s.feedbackformat
             FROM {peerwork_grades} g
             JOIN {peerwork_submission} s
               ON g.submissionid = s.id
            WHERE g.peerworkid = :peerworkid
              AND s.released > 0";

    if ($userid == 0) {
        $sql .= ' AND g.userid != :userid';
    } else {
        $sql .= ' AND g.userid = :userid';
    }

    $params = [
        'peerworkid' => $peerwork->id,
        'userid' => $userid,
    ];

    $grades = [];
    $records = $DB->get_recordset_sql($sql, $params);
    foreach ($records as $record) {
        $userid = $record->userid;
        $grades[$userid] = [
            'rawgrade' => $record->revisedgrade !== null ? $record->revisedgrade : $record->grade,
            'userid' => $userid,
            'feedback' => $record->feedbacktext ?? '',
            'feedbackformat' => $record->feedbackformat ?? FORMAT_PLAIN,
        ];
    }
    $records->close();

    peerwork_grade_item_update($peerwork, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function peerwork_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for peerwork file areas
 *
 * @package mod_peerwork
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function peerwork_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the peerwork file areas
 *
 * @package mod_peerwork
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the peerwork's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function peerwork_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {

        return false;
    }

    require_login();

    if ($filearea != 'submission' && $filearea != 'feedback_files') {

        return false;
    }

    $peerwork = $DB->get_record('peerwork', array('id' => $cm->instance), '*', MUST_EXIST);
    $groupingid = $peerwork->pwgroupingid;
    $itemid = (int)array_shift($args);
    $mygroup = peerwork_get_mygroup($course->id, $USER->id, $groupingid, false);

    // You need to be a teacher in the course
    // or belong to the group same as $itemid.
    if (!has_capability('mod/peerwork:grade', $context)) {
        if ($itemid != $mygroup) {

            return false;
        }
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file($context->id, 'mod_peerwork', $filearea, $itemid, $filepath, $filename);
    if (!$file) {

        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // Download MUST be forced - security!
}

/**
 * Reset user data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array Status array.
 */
function peerwork_reset_userdata($data) {
    return [];
}

/**
 * Extends the global navigation tree by adding peerwork nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the peerwork module instance
 * @param stdClass $course The course.
 * @param stdClass $module The module.
 * @param cm_info $cm The CM.
 * @return void
 */
function peerwork_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the peerwork settings.
 *
 * This function is called when the context for the page is a peerwork module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav The navigation.
 * @param navigation_node $peerworknode The navigation.
 */
function peerwork_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $peerworknode = null) {
}

/**
 * Inplace editable callback.
 *
 * @param string $rawitemtype The item type.
 * @param int $itemid The item ID.
 * @param mixed $newvalue The new value.
 * @return void
 */
function mod_peerwork_inplace_editable($rawitemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    $peerworkid = 0;
    $itemtype = $rawitemtype;
    if (strpos($rawitemtype, '_') > 0) {
        list($itemtype, $peerworkid) = explode('_', $itemtype, 2);
        $peerworkid = (int) $peerworkid;
    }

    $value = null;
    $displayvalue = '';

    switch ($itemtype) {
        case 'groupgrade':
            $groupid = $itemid;
            $peerwork = $DB->get_record('peerwork', ['id' => $peerworkid], '*', MUST_EXIST);

            // We must validate context, permissions, login, etc.
            list($course, $cm) = get_course_and_cm_from_instance($peerworkid, 'peerwork');
            $context = context_module::instance($cm->id);
            $PAGE->set_context($context);
            require_login($course, false, $cm);
            require_capability('mod/peerwork:grade', $context);

            $grader = new mod_peerwork\group_grader($peerwork, $groupid);
            $wasgraded = $grader->was_graded();
            $grade = clean_param($newvalue, PARAM_FLOAT);

            // The user did not really want to grade this.
            if (!$wasgraded && !$grade && ($newvalue === '' || $newvalue === '-')) {
                $displayvalue = '-';
                $value = $grader->get_grade();
                break;
            }

            // From this moment, we must assign a grade.
            $grader->set_grade($grade);
            $grader->commit();
            $value = $displayvalue = $grader->get_grade();
            break;

        default:
            throw new coding_exception('Invalid inplace editable');
    }

    return new core\output\inplace_editable('mod_peerwork', $rawitemtype, $itemid, true, $displayvalue, $value);
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_peerwork_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completiongradedpeers':
                if (!empty($val)) {
                    $descriptions[] = get_string('completiongradedpeers', 'peerwork');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Add a get_coursemodule_info function in case any peerwork type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function peerwork_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = array('id'=>$coursemodule->instance);
    $fields = 'id, name, intro, introformat, completiongradedpeers,
        duedate, fromdate';
    if (! $peerwork = $DB->get_record('peerwork', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $peerwork->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('peerwork', $peerwork, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completiongradedpeers'] = $peerwork->completiongradedpeers;
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($peerwork->duedate) {
        $result->customdata['duedate'] = $peerwork->duedate;
    }
    if ($peerwork->fromdate) {
        $result->customdata['fromdate'] = $peerwork->fromdate;
    }

    return $result;
}