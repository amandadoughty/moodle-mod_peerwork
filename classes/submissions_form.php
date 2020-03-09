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
 * Submissions form.
 *
 * @package mod_peerwork
 * @copyright 2013 LEARNING TECHNOLOGY SERVICES
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/grade/grade_scale.php');

/**
 * This form is the layout for a student grading their peers.
 *
 * Contains a file submission area where files can be submitted on behalf of the group
 * and space to enter marks and feedback to peers in your group.
 *
 * Each criteria is presented and for each one a space for grading peers is provided.
 */
class mod_peerwork_submissions_form extends moodleform {

    /** @var object[] The criteria. */
    protected $criteria;
    /** @var array Cache of locked peers. */
    protected $lockedpeers;
    /** @var grade_scale[] The scales. */
    protected $scales;

    /**
     * Definition.
     *
     * @return void
     */
    protected function definition() {
        global $USER, $CFG, $COURSE, $PAGE;

        $mform = $this->_form;
        $userid = $USER->id;
        $peers = $this->_customdata['peers'];
        $peerworkid = $this->_customdata['peerworkid'];
        $peerwork = $this->_customdata['peerwork'];
        $files = $this->_customdata['files'];
        $strrequired = get_string('required');
        $submissionlocked = !empty($this->_customdata['submission']) && $this->_customdata['submission']->locked;

        $lockableitems = 0;
        $lockeditems = 0;

        // The CM id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $this->_customdata['id']);

        $mform->addElement('hidden', 'files');
        $mform->setType('files', PARAM_INT);
        if (isset($this->_customdata['filecount'])) {
            $mform->setDefault('files', $this->_customdata['filecount']);
        }

        if ($this->_customdata['fileupload']) {
            $mform->addElement('header', 'peerssubmission', get_string('assignment', 'peerwork'));
            $mform->setExpanded('peerssubmission', true);

            $lockableitems++;
            if (!$submissionlocked) {
                $mform->addElement('filemanager', 'submission', get_string('assignment', 'peerwork'),
                    null, $this->_customdata['fileoptions']);
                $mform->addHelpButton('submission', 'submission', 'peerwork');
            } else {
                $lockeditems++;
                $mform->addElement('static', '', get_string('assignment', 'mod_peerwork'),
                    html_writer::tag('ul', '<li>' . implode('</li><li>', $files) . '</li>')
                );
            }
        }

        // Create a hidden field for each possible rating, this is so that we can construct the radio
        // button ourselves while still use the form validation.
        $pac = new mod_peerwork_criteria($peerworkid);
        foreach ($pac->get_criteria() as $criteria) {
            foreach ($peers as $peer) {
                $uniqueid = 'grade_idx_' . $criteria->id . '[' . $peer->id . ']';
                $mform->addElement('hidden', $uniqueid, -1);
                $mform->setType($uniqueid, PARAM_INT);
                $mform->setDefault($uniqueid, -1);

                $lockableitems++;
                if ($this->is_peer_locked($peer->id)) {
                    $lockeditems++;
                }
            }
        }

        // When locking is enabled, and there are things that the user can change, we
        // warm them that they won't be allowed to make further changes afterwards.
        if ($peerwork->lockediting && $lockableitems != $lockeditems) {
            $PAGE->requires->js_call_amd('mod_peerwork/confirm-lock-aware', 'init', ['#' . $mform->getAttribute('id')]);
        }
    }

    /**
     * Definition after data.
     *
     * We define the criteria here in order to be able to get the current rated values and
     * apply them ourselves to the radio buttons.
     *
     * @return void
     */
    public function definition_after_data() {
        global $PAGE, $USER;
        $mform = $this->_form;
        $peerworkid = $this->_customdata['peerworkid'];
        $peerwork = $this->_customdata['peerwork'];

        // Create a section with all the criteria.
        $mform->addElement('header', 'peerstobegraded', get_string('peers', 'peerwork'));
        $mform->setExpanded('peerstobegraded', true);
        $peers = $this->_customdata['peers'];

        $scales = $this->get_scales();
        foreach ($this->get_criteria() as $criteria) {

            // Get the scale.
            $scaleid = abs($criteria->grade);
            $scale = isset($scales[$scaleid]) ? $scales[$scaleid] : null;
            if (!$scale) {
                throw new moodle_exception('Unknown scale ' . $scaleid);
            }
            $scaleitems = $scale->load_items();

            $html = '';
            $html .= html_writer::start_div('mod_peerwork_criteria', ['data-criterionid' => $criteria->id]);
            $html .= html_writer::div($criteria->description, 'mod_peerwork_criteriaheader');
            $html .= html_writer::div(
                get_string('pleaseproviderating', 'mod_peerwork'),
                'mod_peerwork_criteriaerror text-danger',
                [
                    'role' => 'alert'
                ]
            );

            $html .= html_writer::start_div('mod_peerwork_criteriarating');
            $html .= html_writer::div(implode('', array_map(function($item) {
                return html_writer::div($item);
            }, $scaleitems)), 'mod_peerwork_scaleheaders');

            $html .= html_writer::div(implode('', array_map(function($peer) use ($criteria, $scaleitems, $mform, $USER) {
                $uniqueid = 'grade_idx_' . $criteria->id . '[' . $peer->id . ']';
                $currentvalue = $mform->exportValue($uniqueid);
                $fullname = fullname($peer);
                $namedisplay = $peer->id == $USER->id ? get_string('peernameisyou', 'mod_peerwork', $fullname) : $fullname;

                $o = '';
                $o .= html_writer::start_div('mod_peerwork_peer', ['data-peerid' => $peer->id]);
                $o .= html_writer::div($namedisplay, 'mod_peerwork_peername');
                $o .= html_writer::start_div('mod_peerwork_ratings');
                $o .= implode('', array_map(function($item, $key) use ($peer, $uniqueid, $currentvalue, $fullname) {
                    $label = get_string('ratingnforuser', 'mod_peerwork', [
                        'rating' => $item,
                        'user' => $fullname,
                    ]);
                    $attrs = [
                        'type' => 'radio',
                        'name' => $uniqueid,
                        'value' => $key,
                        'title' => $label
                    ];
                    if ($currentvalue == $key) {
                        $attrs['checked'] = 'checked';
                    }
                    if ($this->is_peer_locked($peer->id)) {
                        $attrs['disabled'] = 'disabled';
                    }
                    return html_writer::div(
                        html_writer::tag('label', html_writer::empty_tag('input', $attrs) .
                        html_writer::tag('span', $label, ['class' => 'sr-only'])
                    ), 'mod_peerwork_rating');
                }, $scaleitems, array_keys($scaleitems)));
                $o .= html_writer::end_div();
                $o .= html_writer::end_div();
                return $o;
            }, $peers)), 'mod_peerwork_peers');

            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
            $mform->addElement('html', $html);
        }

        if ($peerwork->justification != MOD_PEERWORK_JUSTIFICATION_DISABLED) {
            $mform->addElement('header', 'justificationhdr', get_string('justification', 'mod_peerwork'));
            $mform->setExpanded('justificationhdr', true);

            $notestr = 'justificationnoteshidden';
            if ($peerwork->justification == MOD_PEERWORK_JUSTIFICATION_VISIBLE_ANON) {
                $notestr = 'justificationnotesvisibleanon';
            } else if ($peerwork->justification == MOD_PEERWORK_JUSTIFICATION_VISIBLE_USER) {
                $notestr = 'justificationnotesvisibleuser';
            }
            $mform->addElement('static', '', '', get_string('justificationintro', 'mod_peerwork') .
                html_writer::empty_tag('br') .
                html_writer::tag('strong', get_string($notestr, 'mod_peerwork')));

            // Don't set the maxlength property because it does not work well with UTF-8 characters.
            $textareaattrs = ['rows' => 2, 'style' => 'width: 100%'];
            foreach ($peers as $peer) {
                $fullname = fullname($peer);
                $namedisplay = $peer->id == $USER->id ? get_string('peernameisyou', 'mod_peerwork', $fullname) : $fullname;
                $mform->addElement('textarea', 'justifications[' . $peer->id . ']', $namedisplay, $textareaattrs);
                if ($this->is_peer_locked($peer->id)) {
                    $mform->hardFreeze('justifications[' . $peer->id . ']');
                }
            }

            if ($peerwork->justificationmaxlength) {
                $PAGE->requires->js_call_amd('mod_peerwork/justification-character-limit', 'init',
                    ['textarea[id^=id_justifications_]', $peerwork->justificationmaxlength]);
            }
        }

        $this->add_action_buttons(false);
    }

    /**
     * Display.
     *
     * Hijack to use some JavaScript to display the errors in our custom grading form.
     *
     * @return string
     */
    public function display() {
        global $PAGE;
        parent::display();

        $gradeerrors = [];
        foreach ($this->_form->_errors as $key => $error) {
            $matches = [];
            if (preg_match('/^grade_idx_([0-9]+)\[([0-9]+)\]$/', $key, $matches)) {
                $criterionid = $matches[1];
                $peerid = $matches[2];
                $gradeerrors[] = [$criterionid, $peerid];
            }
        }

        $gradeerrorsencoded = json_encode($gradeerrors);
        $js = "
            require(['jquery'], function($) {
                var gradeErrors = $gradeerrorsencoded;
                $.each(gradeErrors, function(i, v) {
                    var critid = v[0];
                    var peerid = v[1];
                    var critNode = $('.mod_peerwork_criteria[data-criterionid=' + critid + ']');
                    var peerNode = critNode.find('.mod_peerwork_peer[data-peerid=' + peerid + ']');
                    critNode.addClass('has-error');
                    peerNode.addClass('has-error');
                    peerNode.addClass('text-danger');
                });
            });";
        echo $PAGE->requires->js_amd_inline($js);
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

        // Remove the locked grades.
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (preg_match('/^grade_idx_([0-9]+)$/', $key, $matches)) {
                foreach ($value as $userid => $grade) {
                    if ($this->is_peer_locked($userid)) {
                        unset($data[$key][$userid]);
                    }
                }
            }
        }

        // Remove the locked justifications.
        foreach ($data['justifications'] as $userid => $value) {
            if ($this->is_peer_locked($userid)) {
                unset($data['justifications'][$userid]);
            }
        }

        return (object) $data;
    }

    /**
     * Massages the data.
     *
     * @param stdClass $data
     * @return void
     */
    public function set_data($data) {
        global $DB, $USER;

        $peerworkid = $this->_customdata['peerworkid'];
        $myassessments = $this->_customdata['myassessments'];

        foreach ($myassessments as $grade) {
            $data->{'grade_idx_' . $grade->criteriaid . '[' . $grade->gradefor . ']'} = $grade->grade;
        }

        $justifications = $DB->get_records('peerwork_justification', [
            'peerworkid' => $peerworkid,
            'gradedby' => $USER->id
        ]);
        foreach ($justifications as $j) {
            $data->{'justifications[' . $j->gradefor . ']'} = $j->justification;
        }

        return parent::set_data($data);
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
        $peerwork = $this->_customdata['peerwork'];
        $peers = $this->_customdata['peers'];

        if ($peerwork->justification != MOD_PEERWORK_JUSTIFICATION_DISABLED) {
            foreach ($peers as $peer) {
                if ($this->is_peer_locked($peer->id)) {
                    continue;
                }
                $justification = trim(isset($data['justifications'][$peer->id]) ? $data['justifications'][$peer->id] : '');
                $length = core_text::strlen($justification);
                if (!$length) {
                    $errors['justifications[' . $peer->id . ']'] = get_string('provideajustification', 'mod_peerwork');
                } else if ($peerwork->justificationmaxlength && $length > $peerwork->justificationmaxlength) {
                    $errors['justifications[' . $peer->id . ']'] = get_string('err_maxlength', 'core_form',
                        ['format' => $peerwork->justificationmaxlength]);
                }
            }
        }

        $foundgradererror = false;
        $criteria = $this->get_criteria();
        $scales = $this->get_scales();

        foreach ($data as $key => $value) {
            $matches = [];

            // Validate the grades.
            if (preg_match('/^grade_idx_([0-9]+)$/', $key, $matches)) {
                $criterion = $criteria[$matches[1]];
                $scale = $scales[abs($criterion->grade)];
                $scaleitems = $scale->load_items();
                $maxgrade = count($scaleitems) - 1;
                foreach ($value as $userid => $grade) {
                    if ($this->is_peer_locked($userid)) {
                        continue;
                    }
                    if ($grade < 0 || $grade > $maxgrade) {
                        $errors[$key . "[$userid]"] = get_string('invaliddata', 'error');
                        $foundgradererror = true;
                    }
                }
            }

        }

        if ($foundgradererror) {
            $errors['peerstobegraded'] = 'error';
        }

        return $errors;
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

    /**
     * Get the scales.
     *
     * @return void
     */
    public function get_scales() {
        if (!$this->scales) {
            $this->scales = grade_scale::fetch_all_global();
        }
        return $this->scales;
    }

    /**
     * Check if peer is locked.
     *
     * @param int $peerid Peer ID.
     * @return bool
     */
    public function is_peer_locked($peerid) {
        global $USER;
        if (!isset($this->lockedpeers)) {
            $this->lockedpeers = mod_peerwork_get_locked_peers($this->_customdata['peerwork'], $USER->id);
        }
        return in_array($peerid, $this->lockedpeers);
    }
}
