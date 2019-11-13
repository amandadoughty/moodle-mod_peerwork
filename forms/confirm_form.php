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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mod_peerwork_confirm_form extends moodleform {

    // Define the form.
    protected function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $userid = $USER->id;
        $strrequired = get_string('required');

        $data = $this->_customdata;

        // var_dump($data); die('ok');
        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'submission', $data->submission);
        $mform->setType('submission', PARAM_INT);

        foreach ($data->grade as $k => $grade) {
            $mform->addElement('hidden', "grade[$k]", $grade);
            $mform->setType("grade[$k]", PARAM_INT);
        }

        foreach ($data->feedback as $k => $feedback) {
            $mform->addElement('hidden', "feedback[$k]", $feedback);
            $mform->setType("feedback[$k]", PARAM_TEXT);
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        foreach ($data['grade'] as $k => $v) {
            if ($v < 0 || $v > 5) {
                $errors["grade"][$v] = 'Peer grade should be between 0 and 5';
            }
        }
        return $errors;
    }
}
