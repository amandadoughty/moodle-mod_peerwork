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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mod_peerassessment_add_submission_form extends moodleform
{

    // Public static $fileoptions = array('mainfile' => '', 'subdirs' => 0, 'maxbytes' => -1,
    // 'maxfiles' => 1, 'accepted_types' => '*', 'return_types' => null);

    // Define the form.
    protected function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $userid = $USER->id;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('hidden', 'files');
        $mform->setType('files', PARAM_INT);
        if (isset($this->_customdata['files'])) {
            $mform->setDefault('files', $this->_customdata['files']);
        }

        if ($this->_customdata['fileupload']) {
            $mform->addElement('header', 'peers', get_string('assignment', 'peerassessment'));
            $mform->addElement('filemanager', 'submission', get_string('assignment', 'peerassessment'),
                null, $this->_customdata['fileoptions']);
            $mform->addHelpButton('submission', 'submission', 'peerassessment');
            // $mform->disabledIf('submission', 'value1', 'eq|noteq', 'value2');
            // $mform->addRule('submission', $strrequired, 'required', null, 'client');
            // $mform->setAdvanced('submission');
        }

        $mform->addElement('header', 'peers', get_string('peers', 'peerassessment'));
        $grades = range(0, 5);
        $default = 0;

        $peers = $this->_customdata['peers'];

        foreach ($peers as $peer) {
            $mform->addElement('static', 'label2', fullname($peer));

            $id = '[' . $peer->id . ']';

            $mform->addElement('select', "grade$id", get_string('grade', 'peerassessment'), $grades);
            // $mform->setDefault("grade$id", $default);
            // $mform->setType("grade$id", PARAM_ALPHA);
            // $mform->addHelpButton('grade', 'langkey_help', 'peerassessment');
            // $mform->disabledIf('grade', 'value1', 'eq|noteq', 'value2');
            $mform->addRule("grade$id", $strrequired, 'required', null, 'client');
            // $mform->setAdvanced('grade');

            $mform->addElement('textarea', "feedback$id", get_string('feedback', 'peerassessment'),
                array('rows' => 6, 'cols' => 40));
            // $mform->setType('feedback', PARAM_RAW);
            // $mform->setDefault('feedback', 'defult string value for the textarea');
            // $mform->addHelpButton('feedback', 'langkey_help', 'peerassessment');
            // $mform->disabledIf('feedback', 'value1', 'eq|noteq', 'value2');
            $mform->addRule("feedback$id", $strrequired, 'required', null, 'client');
            // $mform->setAdvanced('feedback');
        }

        $this->add_action_buttons(false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['grade'])) {
            foreach ($data['grade'] as $k => $v) {
                if ($v < 0 || $v > 5) {
                    $errors["grade"][$v] = 'Peer grade should be between 0 and 5';
                }
            }
        }

        return $errors;
    }
}