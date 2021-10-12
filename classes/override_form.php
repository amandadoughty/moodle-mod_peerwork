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
 * Override form.
 *
 * @package mod_peerwork
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/grade/grade_scale.php');

/**
 * This form is for teachers to override the grades given by peers.
 */
class mod_peerwork_override_form extends moodleform {

    /** @var object[] The criteria. */
    protected $criteria;

    /**
     * Definition.
     *
     * @return void
     */
    protected function definition() {
        global $USER, $CFG, $COURSE, $PAGE;

        $mform = $this->_form;

        $mform->addElement('hidden', 'peerworkid');
        $mform->setType('peerworkid', PARAM_INT);
        $mform->setConstant('peerworkid', $this->_customdata['peerworkid']);

        $mform->addElement('hidden', 'gradedby');
        $mform->setType('gradedby', PARAM_INT);
        $mform->setConstant('gradedby', $this->_customdata['gradedby']->id);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);
        $mform->setConstant('groupid', $this->_customdata['groupid']);

        $peers = $this->_customdata['peers'];
        $criteria = $this->get_criteria();
        $gradedby = fullname($peers[$this->_customdata['gradedby']->id]);
        $grades = $this->_customdata['grades']->grade;
        $scales = get_scales_menu($COURSE->id);
        $i = 1;

        foreach ($criteria as $criterion) {
            // Get the scale.
            $scaleid = abs($criterion->grade);
            $scale = isset($scales[$scaleid]) ? $scales[$scaleid] : null;

            if (!$scale) {
                throw new moodle_exception('Unknown scale ' . $scaleid);
            }

            $scale = grade_scale::fetch(['id' => $scaleid]);
            $scaleitems = $scale->load_items();

            // Add crit description.
            $mform->addElement('header', 'criterionnum', get_string('criterianum', 'mod_peerwork', $i));
            $mform->addElement('html', $criterion->description);
            $i++;

            foreach ($peers as $peer) {
                if (!$this->_customdata['selfgrading']) {
                    if ($peer->id == $this->_customdata['gradedby']->id) {
                        continue;
                    }
                }

                $overridearray = [];
                $fullname = fullname($peer);
                $grade = null;
                $peergrade = null;
                $comments = null;

                // Get the original 'peergrade' and the final 'grade' and any 'comments'.
                if (
                    isset($grades[$peer->id]) &&
                    isset($grades[$peer->id][$criterion->id]) &&
                    isset($grades[$peer->id][$criterion->id]['peergrade'])
                ) {
                    $peergrade = $grades[$peer->id][$criterion->id]['peergrade'];
                }

                if (
                    isset($grades[$peer->id]) &&
                    isset($grades[$peer->id][$criterion->id]) &&
                    isset($grades[$peer->id][$criterion->id]['grade'])
                ) {
                    $grade = $grades[$peer->id][$criterion->id]['grade'];
                }

                if (
                    isset($grades[$peer->id]) &&
                    isset($grades[$peer->id][$criterion->id]) &&
                    isset($grades[$peer->id][$criterion->id]['comments'])
                ) {
                    $comments = $grades[$peer->id][$criterion->id]['comments'];
                }

                if ($grade == $peergrade) {
                    $originalgrade = $peergrade;
                    $overiddengrade = null;
                } else {
                    $originalgrade = $peergrade;
                    $overiddengrade = $grade;
                }

                $mform->addElement('static', 'name', get_string('gradegivento', 'mod_peerwork'), "<strong>$fullname</strong>");

                if (isset($scaleitems[$originalgrade])) {
                    $value = $scaleitems[$originalgrade];
                } else {
                    $value = null;
                }

                $mform->addElement('static', 'grade', get_string('grade', 'mod_peerwork'), $value);
                $uniqueid = 'idx_' . $criterion->id . '[' . $peer->id . ']';

                $mform->addElement('checkbox', 'overridden_' . $uniqueid, get_string('overridden', 'mod_peerwork'));

                $mform->addElement(
                    'select',
                    'gradeoverride_' . $uniqueid,
                    get_string('gradeoverride', 'mod_peerwork'),
                    $scaleitems
                );
                $mform->setType('gradeoverride_'  . $uniqueid, PARAM_INT);

                $selected = $overiddengrade ? $overiddengrade : $grade;
                $mform->getElement('gradeoverride_'  . $uniqueid)->setSelected($selected);

                $mform->addElement(
                    'textarea',
                    'comments_' . $uniqueid,
                    get_string('comments', 'mod_peerwork'),
                    'wrap="virtual" rows="1" cols="50"'
                );
                $mform->setDefault('comments_' . $uniqueid, $comments);
                $mform->addHelpButton('comments_' . $uniqueid, 'comments', 'peerwork');

                $mform->disabledIf('gradeoverride_' . $uniqueid, 'overridden_' . $uniqueid);
                $mform->disabledIf('comments_' . $uniqueid, 'overridden_' . $uniqueid);
            }
        }

        $this->add_action_buttons();
    }

    /**
     * Perform validation on the override form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $peers = $this->_customdata['peers'];
        $criteria = $this->get_criteria();
        $errortxt = get_string('pleaseexplainoverride', 'mod_peerwork');

        foreach ($criteria as $criterion) {
            foreach ($peers as $peer) {
                $uniqueid = 'idx_' . $criterion->id;

                if (isset($data['overridden_' . $uniqueid][$peer->id]) && $data['overridden_' . $uniqueid][$peer->id]) {
                    if (!$data['comments_' . $uniqueid][$peer->id]) {
                        $errors['comments_' . $uniqueid. '[' . $peer->id . ']'] = $errortxt;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get data.
     *
     * @return object
     */
    public function get_data() {
        $data = parent::get_data();

        if (!$data) {
            return $data;
        }

        // Group the grades and data.
        $data = (array) $data;
        $data['overridden'] = [];

        foreach ($data as $key => $value) {
            if (preg_match('/^overridden_idx_([0-9]+)$/', $key, $matches)) {
                foreach ($value as $gradefor => $grade) {
                    $data['overridden'][$key] = $value;
                }
            }

            if (preg_match('/^gradeoverride_idx_([0-9]+)$/', $key, $matches)) {
                foreach ($value as $gradefor => $grade) {
                    $data['gradeoverrides'][$key] = $value;
                }
            }

            if (preg_match('/^comments_idx_([0-9]+)$/', $key, $matches)) {
                foreach ($value as $gradefor => $grade) {
                    $data['comments'][$key] = $value;
                }
            }
        }

        return (object) $data;
    }

    /**
     * Get the criteria.
     *
     * @return void
     */
    public function get_criteria() {
        if (!$this->criteria) {
            $pac = new mod_peerwork_criteria($this->_customdata['peerworkid']);
            $this->criteria = $pac->get_criteria();
        }
        return $this->criteria;
    }
}
