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
 * @package    mod
 * @subpackage peerassessment
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
function peerassessment_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the peerassessment definition into the database. Called automagically when submitting the mod_form form.
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $peerassessment An object from the form in mod_form.php
 * @param mod_peerassessment_mod_form $mform
 * @return int The id of the newly inserted peerassessment record
 */
function peerassessment_add_instance(stdClass $peerassessment, mod_peerassessment_mod_form $mform = null) {
    global $DB;

    $peerassessment->timecreated = time();
    $peerassessment->id = $DB->insert_record('peerassessment', $peerassessment);
    
    // Now save all the criteria.
    $pac = new peerassessment_criteria( $peerassessment->id );
    $pac ->update_instance($peerassessment);
    
    peerassessment_grade_item_update($peerassessment);

    return $peerassessment->id;
}

/**
 * Settings
 * Called automatically when saving peerassessment setttings.
 * Updates an instance of the peerassessment details in the database, 
 * the criteria settings are added to a separate table (peerassessment_criteria)
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $peerassessment An object from the form in mod_form.php
 * @param mod_peerassessment_mod_form $mform
 * @return boolean Success/Fail
 * 
 */
function peerassessment_update_instance(stdClass $peerassessment, mod_peerassessment_mod_form $mform = null) {
    global $DB;

    $peerassessment->timemodified = time();
    $peerassessment->id = $peerassessment->instance;
    $return1 = $DB->update_record('peerassessment', $peerassessment);
    
    // Now save all the criteria.
    $pac = new peerassessment_criteria( $peerassessment->id );
    $return2 = $pac ->update_instance($peerassessment);
    
    peerassessment_update_grades($peerassessment);

    return $return1 && $return2;
}

/**
 * Removes an instance of the peerassessment from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function peerassessment_delete_instance($id) {
    global $DB;

    if (!$peerassessment = $DB->get_record('peerassessment', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('peerassessment', array('id' => $peerassessment->id));
    
    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function peerassessment_user_outline($course, $user, $mod, $peerassessment) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $peerassessment the module instance record
 * @return void, is supposed to echp directly
 */
function peerassessment_user_complete($course, $user, $mod, $peerassessment) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in peerassessment activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function peerassessment_print_recent_activity($course, $viewfullnames, $timestart) {
    // True if anything was printed, otherwise false.
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link peerassessment_print_recent_mod_activity()}.
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
function peerassessment_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {

}

/**
 * Prints single activity item prepared by {@see peerassessment_get_recent_mod_activity()}
 * @return void
 */
function peerassessment_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {

}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function peerassessment_cron() {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function peerassessment_get_extra_capabilities() {
    return array();
}

/**
 * Is a given scale used by the instance of peerassessment?
 *
 * This function returns if a scale is being used by one peerassessment
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $peerassessmentid ID of an instance of this module
 * @return bool true if the scale is used by the given peerassessment instance
 */
function peerassessment_scale_used($peerassessmentid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of peerassessment.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any peerassessment instance
 */
function peerassessment_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Creates or updates grade item for the give peerassessment instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $peerassessment instance object with extra cmidnumber and modname property
 * @return void
 */
function peerassessment_grade_item_update(stdClass $peerassessment, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($peerassessment->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax'] = 100;
    $item['grademin'] = 0;

    return grade_update('mod/peerassessment', $peerassessment->course, 'mod',
        'peerassessment', $peerassessment->id, 0, $grades, $item);
}

/**
 * Update peerassessment grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $peerassessment instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 * 
 * TODO should this even be called if peers havent yet added submissions and grades??
 */
function peerassessment_update_grades(stdClass $peerassessment, $userid = 0, $nullifnone = true) {
    // Will be called for each user id from a group, upon grading.
    global $CFG, $DB;

    require_once($CFG->libdir . '/gradelib.php');

    $groupingid = $peerassessment->submissiongroupingid;
    $courseid = $peerassessment->course;
    $error = array();

    if ($userid == 0) {
        // Get all users in a course.
        // Maybe we should take all roles with archetype student.
        $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerassessment', $peerassessment->id, $peerassessment->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $users = get_role_users($role->id, $context, true);
    } else {
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $users = array($user);
    }

    $grades = array();

    foreach ($users as $user) {
        $groups = groups_get_all_groups($courseid, $user->id, $groupingid);

        if (count($groups) > 1) {
            $error[] = "{$user->firstname} {$user->lastname}";
            continue;
        }

        $group = array_shift($groups);
        $submission = null;
        $grade = peerassessment_get_grade($peerassessment, $group, $user);

        if ($group) {
            $submission = $DB->get_record('peerassessment_submission', array('assignment' => $peerassessment->id,
                'groupid' => $group->id));
        }

        if ($grade == '-') {
            $grade = null;
        }

        $grades[$user->id]['rawgrade'] = $grade;
        $grades[$user->id]['userid'] = $user->id;

        if ($submission) {
            $grades[$user->id]['feedback'] = $submission->feedbacktext;
            $grades[$user->id]['feedbackformat'] = $submission->feedbackformat;
        }

        if (!isset($grades[$user->id]['feedbackformat'])) {
            $grades[$user->id]['feedbackformat'] = FORMAT_HTML;
        }
    }

    peerassessment_grade_item_update($peerassessment, $grades);

    if ($error) {
        $names = join(', ', $error);
        $returnurl = new moodle_url('/mod/peerassessment/view.php', array('id' => $cm->id));
        print_error('multiplegroups', 'mod_peerassessment', $returnurl, $names);
    }
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function peerassessment_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for peerassessment file areas
 *
 * @package mod_peerassessment
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
function peerassessment_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the peerassessment file areas
 *
 * @package mod_peerassessment
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the peerassessment's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function peerassessment_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {

        return false;
    }

    require_login();

    if ($filearea != 'submission' && $filearea != 'feedback_files') {

        return false;
    }

    $peerassessment = $DB->get_record('peerassessment', array('id' => $cm->instance), '*', MUST_EXIST);
    $groupingid = $peerassessment->submissiongroupingid;
    $itemid = (int)array_shift($args);
    $mygroup = peerassessment_get_mygroup($course->id, $USER->id, $groupingid, false);

    // You need to be a teacher in the course
    // or belong to the group same as $itemid.
    if (!has_capability('mod/peerassessment:grade', $context)) {
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

    $file = $fs->get_file($context->id, 'mod_peerassessment', $filearea, $itemid, $filepath, $filename);
    if (!$file) {

        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}

/**
 * Extends the global navigation tree by adding peerassessment nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the peerassessment module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function peerassessment_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {

}

/**
 * Extends the settings navigation with the peerassessment settings
 *
 * This function is called when the context for the page is a peerassessment module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $peerassessmentnode {@link navigation_node}
 */
function peerassessment_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $peerassessmentnode = null) {

}