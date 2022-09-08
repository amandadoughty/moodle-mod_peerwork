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
 * Module form.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once( __DIR__ . '/locallib.php');
require_once($CFG->libdir . '/gradelib.php' );

/**
 * Module instance settings form.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerwork_mod_form extends moodleform_mod {

    /** @var peerwork_criteria The peerwork criteria class. */
    protected $pac;

    /**
     * Defines forms elements.
     */
    public function definition() {
        global $CFG, $DB, $COURSE, $PAGE;

        $mform = $this->_form;
        $peerwork = null;

        $PAGE->requires->js_call_amd('mod_peerwork/update_calculator', 'init', ['formid' => $mform->getAttribute('id')]);

        if ($this->current && $this->current->id) {
            $peerwork = $DB->get_record('peerwork', ['id' => $this->current->id], '*', MUST_EXIST);
        }

        $this->pac = new mod_peerwork_criteria($this->current->id);
        $steps = range(0, 100, 1);
        $zerotohundredpcopts = array_combine($steps, array_map(function($i) {
            return $i . '%';
        }, $steps));
        $hassubmissions = $this->has_submissions();

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

        // Grade settings.
        $this->standard_grading_coursemodule_elements();
        $mform->removeElement('grade');

        // Adding the rest of peerwork settings, spreading all them into this fieldset,
        // or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'peerworkfieldset', get_string('peerworkfieldset', 'peerwork'));

        $mform->addElement('date_time_selector', 'fromdate', get_string('fromdate', 'peerwork'), array('optional' => true));
        $mform->setDefault('fromdate', time());
        $mform->addHelpButton('fromdate', 'fromdate', 'peerwork');

        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'peerwork'), array('optional' => true));
        $mform->setDefault('duedate', time() + DAYSECS);
        $mform->addHelpButton('duedate', 'duedate', 'peerwork');

        $mform->addElement('selectyesno', 'allowlatesubmissions', get_string('allowlatesubmissions', 'peerwork'));
        $mform->setType('allowlatesubmissions', PARAM_BOOL);
        $mform->addHelpButton('allowlatesubmissions', 'allowlatesubmissions', 'peerwork');

        $mform->addElement('selectyesno', 'lockediting', get_string('lockediting', 'peerwork'));
        $mform->addHelpButton('lockediting', 'lockediting', 'peerwork');

        // How many submission files to be allowed. Zero means dont offer a file upload at all.
        $choices = [0 => 0, 1, 2, 3, 4, 5];
        $mform->addElement('select', 'maxfiles', get_string('setup.maxfiles', 'peerwork'), $choices);
        $mform->setType('maxfiles', PARAM_INT);
        $mform->addHelpButton('maxfiles', 'setup.maxfiles', 'peerwork');

        $mform->addElement('selectyesno', 'selfgrading', get_string('selfgrading', 'peerwork'));
        $mform->setType('selfgrading', PARAM_BOOL);
        $mform->addHelpButton('selfgrading', 'selfgrading', 'peerwork');
        if ($hassubmissions) {
            $mform->freeze('selfgrading');
        }

        // Create the calculator field:
        // Hidden field if only one enabled calculator plugin.
        // Select field if more than one enabled calculator plugin.
        add_all_calculator_plugins($mform, $peerwork);

        $mform->addElement('select', 'noncompletionpenalty', get_string('noncompletionpenalty', 'peerwork'), $zerotohundredpcopts);
        $mform->addHelpButton('noncompletionpenalty', 'noncompletionpenalty', 'peerwork');

        $this->add_assessment_criteria();

        // Groupings selector - used to select grouping for groups in activity.
        $mform->addElement('header', 'groupsubmissionsettings', get_string('groupsubmissionsettings', 'peerwork'));

        $options = [];
        $options[0] = get_string('none');
        $groupings = groups_get_all_groupings($COURSE->id);

        foreach ($groupings as $grouping) {
            $options[$grouping->id] = format_string($grouping->name);
        }

        $mform->addElement('select', 'pwgroupingid', get_string('grouping', 'group'), $options);
        $mform->addHelpButton('pwgroupingid', 'grouping', 'group');

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Apply default values from admin settings.
        $this->apply_admin_defaults();
        // Calculators can be disabled or uninstalled after they have been set as
        // the site default. We need a fallback to ensure available scales are
        // correct.
        $defaultcalculator = get_config('peerwork', 'calculator');
        $plugin = 'peerworkcalculator_' . $defaultcalculator;
        $classname = '\\' . $plugin . '\calculator';
        $disabled = get_config($plugin, 'disabled');

        if ($disabled) {
            $plugins = core_component::get_plugin_list('peerworkcalculator');

            foreach ($plugins as $name => $path) {
                $disabled = get_config('peerworkcalculator' . '_' . $name, 'disabled');

                if (!$disabled) {
                    $mform->setDefault('calculator', $name);
                    break;
                }
            }
        }

        // Add actions.
        $this->add_action_buttons();
    }

    /**
     * Add assessment criteria.
     *
     * @return void
     */
    protected function add_assessment_criteria() {
        global $COURSE;

        $mform = $this->_form;
        $ctx = null;
        $hassubmissions = $this->has_submissions();
        $criteria = $this->pac->get_criteria();

        $mform->addElement('header', 'assessmentcriteriasettings', get_string('assessmentcriteria:header', 'peerwork'));

        $options = [
            MOD_PEERWORK_PEER_GRADES_HIDDEN => get_string('peergradeshiddenfromstudents', 'mod_peerwork'),
            MOD_PEERWORK_PEER_GRADES_VISIBLE_ANON => get_string('peergradesvisibleanon', 'mod_peerwork'),
            MOD_PEERWORK_PEER_GRADES_VISIBLE_USER => get_string('peergradesvisibleuser', 'mod_peerwork'),
        ];
        $mform->addElement('select', 'peergradesvisibility', get_string('peergradesvisibility', 'mod_peerwork'), $options);
        $mform->addHelpButton('peergradesvisibility', 'peergradesvisibility', 'peerwork');
        if ($hassubmissions) {
            $mform->freeze('peergradesvisibility');
        }

        $mform->addElement('selectyesno', 'displaypeergradestotals', get_string('displaypeergradestotals', 'mod_peerwork'));
        $mform->addHelpButton('displaypeergradestotals', 'displaypeergradestotals', 'peerwork');
        $mform->hideIf('displaypeergradestotals', 'peergradesvisibility', 'eq', MOD_PEERWORK_PEER_GRADES_HIDDEN);

        $options = [
            MOD_PEERWORK_JUSTIFICATION_DISABLED => get_string('justificationdisabled', 'mod_peerwork'),
            MOD_PEERWORK_JUSTIFICATION_HIDDEN => get_string('justificationhiddenfromstudents', 'mod_peerwork'),
            MOD_PEERWORK_JUSTIFICATION_VISIBLE_ANON => get_string('justificationvisibleanon', 'mod_peerwork'),
            MOD_PEERWORK_JUSTIFICATION_VISIBLE_USER => get_string('justificationvisibleuser', 'mod_peerwork'),
        ];
        $mform->addElement('select', 'justification', get_string('requirejustification', 'mod_peerwork'), $options);
        $mform->addHelpButton('justification', 'requirejustification', 'peerwork');
        if ($hassubmissions) {
            $mform->freeze('justification');
        }

        $options = [
            MOD_PEERWORK_JUSTIFICATION_SUMMARY => get_string('justificationtype0', 'mod_peerwork'),
            MOD_PEERWORK_JUSTIFICATION_CRITERIA => get_string('justificationtype1', 'mod_peerwork')
        ];
        $mform->addElement('select', 'justificationtype', get_string('justificationtype', 'mod_peerwork'), $options);
        $mform->addHelpButton('justificationtype', 'justificationtype', 'peerwork');
        $mform->hideIf('justificationtype', 'justification', 'eq', MOD_PEERWORK_JUSTIFICATION_DISABLED);
        if ($hassubmissions) {
            $mform->freeze('justificationtype');
        }

        $mform->addElement('text', 'justificationmaxlength', get_string('justificationmaxlength', 'mod_peerwork'));
        $mform->addHelpButton('justificationmaxlength', 'justificationmaxlength', 'mod_peerwork');
        $mform->setType('justificationmaxlength', PARAM_INT);
        $mform->hideIf('justificationmaxlength', 'justification', 'eq', MOD_PEERWORK_JUSTIFICATION_DISABLED);
        if ($hassubmissions) {
            $mform->freeze('justificationmaxlength');
        }

        // Preparing repeated element.
        $elements = [];
        $repeatopts = [];
        $initialrepeat = count($criteria) ? count($criteria) : (int) get_config('peerwork', 'numcrit');
        $repeatsteps = max(1, (int) get_config('peerwork', 'addmorecriteriastep'));

        // Editor.
        $editor = $mform->createElement('editor', 'critdesc', get_string('assessmentcriteria:description', 'mod_peerwork'),
            ['rows' => 4]);
        $repeatopts['critdesc'] = [
            'helpbutton' => ['assessmentcriteria:description', 'mod_peerwork']
        ];

        // Scale.
        $scale = $mform->createElement('select', 'critscale',
            get_string('assessmentcriteria:scoretype', 'mod_peerwork'), get_scales_menu($COURSE->id));
        $repeatopts['critscale'] = [
            'helpbutton' => ['assessmentcriteria:scoretype', 'mod_peerwork']
        ];

        // Repeat stuff.
        $repeatels = $this->repeat_elements([$editor, $scale], $initialrepeat, $repeatopts, 'assessmentcriteria_count',
            'assessmentcriteria_add', $repeatsteps, get_string('addmorecriteria', 'mod_peerwork'), true);

        // If this is an 'add' form use site defaults.
        if ($this->current  && !$this->current->coursemodule) {
            $config = get_config('peerwork');
            // Add the default values.
            for ($i = 0; $i < $repeatels; $i++) {
                // The max number of default criteria is 5 and we may
                // have default text and a scale for each one.
                if ($i < 5) {
                    $text = $config->{'defaultcrit' . $i};
                    $selected = $config->{'defaultscale' . $i};

                    // If there is no scale set for this criteria
                    // use the default for all.
                    if ($selected === 0) {
                        $selected = $config->critscale;
                    }

                    $mform->setDefault(
                            'critdesc[' . $i . ']',
                            [
                                'text' => $text,
                                'format' => FORMAT_HTML
                            ]
                        );

                    $mform->getElement('critscale[' . $i . ']')->setSelected($selected);
                }
            }
        }
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
     * Preprocessing.
     *
     * @param array $defaultvalues Passed by reference.
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['critdesc'] = empty($defaultvalues['critdesc']) ? [] : $defaultvalues['critdesc'];
        $defaultvalues['scale'] = empty($defaultvalues['scale']) ? [] : $defaultvalues['scale'];

        $crits = array_values($this->pac->get_criteria());   // Drop the keys.

        foreach ($crits as $i => $crit) {
            $defaultvalues['critdesc'][$i] = [
                'text' => $crit->description,
                'format' => $crit->descriptionformat
            ];

            // Scales are saved as negative integers.
            $defaultvalues["critscale[$i]"] = -$crit->grade;
        }
    }

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     */
    public function definition_after_data() {
        global $CFG, $COURSE, $DB;

        $mform =& $this->_form;
        $hassubmissions = $this->has_submissions();
        $criteria = $this->pac->get_criteria();
        $i = 0;
        $calculators = core_component::get_plugin_list('peerworkcalculator');
        $gradesreleased = $this->is_grades_released();
        $peerwork = null;

        if ($this->current && $this->current->id) {
            $peerwork = $DB->get_record('peerwork', ['id' => $this->current->id], '*', MUST_EXIST);
        }

        if ($hassubmissions) {
            for ($i; $i < count($criteria); $i++) {
                // Cannot currently freeze editor elements MDL-29421.
                $elname = 'critscale[' . $i . ']';

                if ($mform->elementExists($elname)) {
                    $mform->freeze($elname); // Prevent removing of existing scales.
                }
            }

            $elname = 'assessmentcriteria_add';

            if ($mform->elementExists($elname)) {
                $mform->freeze($elname); // Prevent adding more criteria.
            }
        }

        // Calculators can restrict the choice of available scales. If the
        // selected calculator changes then the available scales are updated.
        // By default $calculatorclass::get_scales_menu returns false and
        // all site and course scales are available.
        if ($mform->elementExists('calculator') && $mform->elementExists('assessmentcriteria_count')) {
            $selected = $mform->getElementValue('calculator');

            // Behat tests fail without this if.
            if ($selected) {
                if (is_array($selected)) {
                    $name = array_pop($selected);
                } else {
                    $name = $selected;
                }

                $calculatorclass = '\peerworkcalculator_' . $name . '\calculator';
                $count = $mform->getElementValue('assessmentcriteria_count');
                $availablescales = $calculatorclass::get_scales_menu($COURSE->id);

                if ($availablescales) {
                    for ($i; $i < $count; $i++) {
                        $elname = 'critscale[' . $i . ']';

                        if ($mform->elementExists($elname)) {
                            $el = $mform->getElement($elname);
                            $el->removeOptions();
                            $el->loadArray($availablescales);
                        }
                    }
                }

                add_plugin_settings($mform, $peerwork, $name);
            }
        }

        // Remove elements if the grades have not been released.
        if (!$gradesreleased) {
            if ($mform->elementExists('gradesexistmsg')) {
                $mform->removeElement('gradesexistmsg');
            }
            if ($mform->elementExists('recalculategrades')) {
                $mform->removeElement('recalculategrades');
            }
        }

        parent::definition_after_data();
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

        $data->assessmentcriteria = $this->normalise_criteria_from_data($data);
        unset($data->critdesc);
        unset($data->critscale);
    }

    /**
     * Normalise the criteria from data.
     *
     * @param array|object $data The raw data.
     * @return object
     */
    protected function normalise_criteria_from_data($data) {
        $data = (object) $data;
        $count = 0;
        $assessmentcriteria = [];

        foreach ($data->critdesc as $i => $value) {
            if (empty(trim(strip_tags($value['text'])))) {
                continue;
            }
            $grade = isset($data->critscale[$i]) ? -abs($data->critscale[$i]) : null;// Scales are saved as negative integers.
            $assessmentcriteria[$i] = (object) [
                'description' => $value['text'],
                'descriptionformat' => $value['format'],
                'grade' => $grade,
                'sortorder' => $count,
                'weight' => 1,
            ];
            $count++;
        }

        return $assessmentcriteria;
    }

    /**
     * Validation.
     *
     * @param array $data The data.
     * @param array $files The files.
     * @return array|void
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $crits = $this->normalise_criteria_from_data($data);
        if (empty($crits)) {
            $errors['critdesc[0]'] = get_string('provideminimumonecriterion', 'mod_peerwork');
        }

        $invalidscales = array_diff_key($data['critdesc'], $data['critscale']);

        foreach ($invalidscales as $key => $value) {
            $errors["critscale[$key]"] = get_string('invalidscale', 'mod_peerwork');
        }

        return $errors;
    }

    /**
     * Check if the activity has submissions.
     *
     * @return bool $hassubmissions
     */
    public function has_submissions() {
        $hassubmissions = false;

        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('peerwork', $this->current->id, 0, false, MUST_EXIST);
            $hassubmissions = peerwork_has_submissions($cm);
        }

        return $hassubmissions;
    }

    /**
     * Check if the grades have been released.
     *
     * @return bool $gradesreleased
     */
    public function is_grades_released() {
        $gradesreleased = false;

        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('peerwork', $this->current->id, 0, false, MUST_EXIST);
            $gradesreleased = peerwork_has_released_grades($cm);
        }

        return $gradesreleased;
    }
}
