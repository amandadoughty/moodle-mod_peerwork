<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/.
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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once( __DIR__ . '/locallib.php');
require_once( __DIR__ . '/classes/peerwork_criteria.php');
require_once($CFG->libdir . '/gradelib.php' );

/**
 * Module instance settings form. This is the form that allows the teacher to
 * configure the peerwork settings.
 * It has to be located in the modules' root directory.
 */
class mod_peerwork_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('peerworkname', 'peerwork'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'peerworkname', 'peerwork');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();
        if ($this->current->id) {
            $peerwork = $DB->get_record('peerwork', array('id' => $this->current->id), '*', MUST_EXIST);
        }

        // Adding the rest of peerwork settings, spreading all them into this fieldset,
        // or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'peerworkfieldset', get_string('peerworkfieldset', 'peerwork'));
        $mform->addElement('advcheckbox', 'selfgrading', get_string('selfgrading', 'peerwork'));
        $mform->setType('selfgrading', PARAM_BOOL);
        $mform->addHelpButton('selfgrading', 'selfgrading', 'peerwork');
        // $mform->disabledIf('selfgrading', 'value1', 'eq|noteq', 'value2');
        // $mform->addRule('selfgrading', $strrequired, 'required', null, 'client');
        // $mform->setAdvanced('selfgrading');

        $mform->addElement('date_time_selector', 'fromdate', get_string('fromdate', 'peerwork'), array('optional' => true));
        $mform->setDefault('fromdate', time());
        $mform->addHelpButton('fromdate', 'fromdate', 'peerwork');

        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'peerwork'), array('optional' => true));
        $mform->setDefault('duedate', time() + DAYSECS);
        $mform->addHelpButton('duedate', 'duedate', 'peerwork');

        $mform->addElement('selectyesno', 'allowlatesubmissions', get_string('allowlatesubmissions', 'peerwork'));
        $mform->setType('allowlatesubmissions', PARAM_BOOL);
        $mform->addHelpButton('allowlatesubmissions', 'allowlatesubmissions', 'peerwork');

        // How many submission files to be allowed. Zero means dont offer a file upload at all.
        $choices = array(0 =>0, 1, 2, 3, 4, 5);
        $mform->addElement('select', 'maxfiles', get_string('setup.maxfiles', 'peerwork'), $choices);
        $mform->setType('maxfiles', PARAM_INT);
        $mform->addHelpButton('maxfiles', 'setup.maxfiles', 'peerwork');

        //
        // The allowed calculation types. Allow for future variations but for now lock to webPA algorithm.
        $calculations = array(peerwork_WEBPA => peerwork_WEBPA);
        $mform->addElement('select', 'setup.calculationtype', get_string('setup.calculationtype', 'peerwork'), $calculations);
        $mform->setType('setup.calculationtype', PARAM_TEXT);
        $mform->setDefault('setup.calculationtype', peerwork_WEBPA);
        $mform->addHelpButton('setup.calculationtype', 'setup.calculationtype', 'peerwork');



        // Cant change the formula once a grade has been awarded. Why?
// TODO temp disable for dev purposes.
//         if (($this->current->id) && has_been_graded($peerwork)) {
//             $mform->freeze('calculationtype');
//         }

        // $mform->addElement('text', 'multiplyby', get_string('multiplyby', 'peerwork'), array('size' => '10'));
        // $mform->setType('multiplyby', PARAM_INT);
        // // $mform->addRule('multiplyby', null, 'required', null, 'client');
        // $mform->addRule('multiplyby', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // $mform->addHelpButton('multiplyby', 'multiplyby', 'peerwork');

        $mform->addElement('selectyesno', 'notifylatesubmissions', get_string('notifylatesubmissions', 'peerwork'));
        $mform->setType('notifylatesubmissions', PARAM_BOOL);
        $mform->addHelpButton('notifylatesubmissions', 'notifylatesubmissions', 'peerwork');

        $mform->addElement('selectyesno', 'treat0asgrade', get_string('treat0asgrade', 'peerwork'));
        $mform->setType('treat0asgrade', PARAM_BOOL);
        $mform->setDefault('treat0asgrade', true);
        $mform->addHelpButton('treat0asgrade', 'treat0asgrade', 'peerwork');

        // KM add in the fields to specify assessment criteria, using a separate class to isolate change.
        $pac = new peerwork_criteria( $this->current->id );
        $pac ->definition($mform);

// TODO Why is this been done twice??
//         if (($this->current->id) && has_been_graded($peerwork)) {
//             $mform->freeze('calculationtype');
//         }

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // NW - DO I NEED TO ADD THIS??  Add admin defaults.
        $this->apply_admin_defaults();

        // Goes to lib.php/peerwork_add_instance() etal
        $this->add_action_buttons();
    }

    /**
     * Add custom completion rules.
     *
     * @return array Of element names.
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completiongradedpeers', get_string('completiongradedpeers', 'mod_peerwork'),
            get_string('completiongradedpeers_desc', 'mod_peerwork'));
        $mform->addHelpButton('completiongradedpeers', 'completiongradedpeers', 'mod_peerwork');

        return ['completiongradedpeers'];
    }


    /**
     * Whether any custom completion rule is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completiongradedpeers']);
    }

    /**
     * Modify the data from get_data.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        // We can only change the values while completion is 'unlocked'.
        if (!empty($data->completionunlocked)) {
            $data->completiongradedpeers = (int) !empty($data->completiongradedpeers);
        }
    }

    /**
     * Collect criteria data from the DB to initialise the form and add into $data, then pass $data on to the parent class to complete.
     * @param unknown $data incoming data is stdClass Object populated with fields => DB data
     * @return unknown
     */
    public function set_data($data) {

        // Collect the criteria data for this peerwork and add into $data.
        $pac = new peerwork_criteria( $data->id );
        $pac ->set_data($data);

        // error_log("set_data with ". print_r($data,true) );
        return parent::set_data($data);
    }


}
