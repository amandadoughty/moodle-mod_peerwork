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
 * Summary.
 *
 * @package    mod_peerwork
 * @copyright  2020 City, University of London
 * @author     Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Summary.
 *
 * @package    mod_peerwork
 * @copyright  2020 City, University of London
 * @author     Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerwork_detail_summary implements templatable, renderable {

    /** @var object The criterion. */
    public $criterion;
    /** @var object The grades. */
    public $grades;
    /** @var array The justifications. */
    public $justifications;
    /** @var object[] The members. */
    public $members;
    /** @var object[] The locked graders. */
    public $lockedgraders;
    /** @var object The peerwork. */
    public $peerwork;
    /** @var bool Whether the peer grades can be unlocked. */
    public $canunlock;
    /** @var bool Whether justification is enabled. */
    public $justenabledcrit;
    /** @var int Course module id. */
    public $cmid;
    /** @var int Group id. */
    public $groupid;

    /**
     * Constructor.
     *
     * @param object[] $criterion The criterion.
     * @param object $grades The grades.
     * @param array $justifications The justifications.
     * @param object[] $members The members.
     * @param array $lockedgraders The locked graders.
     * @param object $peerwork The peerwork.
     * @param bool $canunlock Whether the peer grades can be unlocked.
     * @param bool $justenabledcrit Whether justification is enabled.
     */
    public function __construct(
        $criterion,
        $grades,
        $justifications,
        $members,
        $lockedgraders,
        $peerwork,
        $canunlock,
        $justenabledcrit,
        $cmid,
        $groupid
    ) {
        $this->criterion = $criterion;
        $this->grades = $grades;
        $this->justifications = $justifications;
        $this->members = $members;
        $this->lockedgraders = $lockedgraders;
        $this->peerwork = $peerwork;
        $this->canunlock = $canunlock;
        $this->justenabledcrit = $justenabledcrit;
        $this->cmid = $cmid;
        $this->groupid = $groupid;
    }

    /**
     * Export this class data as a flat list for rendering in a template.
     *
     * @param renderer_base $output The current page renderer.
     * @return stdClass - Flat list of exported data.
     */
    public function export_for_template(renderer_base $output) {
        global $COURSE;

        $criterion = $this->criterion;
        $grades = $this->grades;
        $justifications = $this->justifications;
        $members = $this->members;
        $lockedgraders = $this->lockedgraders;
        $peerwork = $this->peerwork;
        $canunlock = $this->canunlock;
        $justenabledcrit = $this->justenabledcrit;
        $cmid = $this->cmid;
        $groupid = $this->groupid;
        $data = [];
        $feedbackrendered = [];
        $memberdropdown = [];

        foreach ($criterion as $criteria) {
            $table = [];
            $critid = $criteria->id;
            $extraclasses = $justenabledcrit ? 'crit' : '';
            $table['attributes']['id'] = "mod_peerwork_peergrades";
            $table['attributes']['class'] = "table-striped $extraclasses";
            $table['caption'] = $criteria->description;

            foreach ($members as $member) {
                $gradedby = [];
                $gradefor = [];
                $gradefor = ['name' => fullname($member)];
                $label = fullname($member);

                if ($canunlock && in_array($member->id, $lockedgraders)) {
                    $label .= $output->action_icon('#',
                        new \pix_icon('t/locked', get_string('editinglocked', 'mod_peerwork'), 'core'),
                        null,
                        [
                            'data-peerworkid' => $peerwork->id,
                            'data-graderid' => $member->id,
                            'data-graderfullname' => fullname($member),
                            'data-graderunlock' => 'true',
                        ]
                    );
                }

                $gradedby = ['name' => $label];

                $memberdropdown[$member->id] = [
                    'id' => $cmid,
                    'peerworkid' => $peerwork->id,
                    'groupid' => $groupid,
                    'gradedby' => $member->id,
                    'name' => fullname($member)
                ];

                foreach ($members as $peer) {
                    if (
                        !isset($grades->grades[$critid]) ||
                        !isset($grades->grades[$critid][$member->id]) ||
                        !isset($grades->grades[$critid][$member->id][$peer->id])
                    ) {
                        $gradedby['gradefor'][] = [
                            'name' => fullname($peer),
                            'grade' => '-'
                        ];
                    } else {
                        $feedbacktext = '';
                        $override = '';
                        $grade = $grades->grades[$critid][$member->id][$peer->id];

                        // Display help tip if original peer grade has been
                        // overridden.
                        if (
                            // The peergrade may be null.
                            array_key_exists($critid, $grades->overrides) &&
                            array_key_exists($member->id, $grades->overrides[$critid]) &&
                            array_key_exists($peer->id, $grades->overrides[$critid][$member->id])
                        ) {
                            $peergrade = $grades->overrides[$critid][$member->id][$peer->id];

                            if ($peergrade != $grade) {
                                $peergrade = $peergrade == null ? '-' : $peergrade;
                                $title = get_string('gradeoverridden', 'mod_peerwork', $peergrade);
                                $pixicon = new \pix_icon('help', '', 'moodle', ['title' => $title]);
                                $override = $output->render($pixicon);
                            }
                        }

                        if (
                            $justenabledcrit &&
                            isset($justifications[$member->id]) &&
                            isset($justifications[$member->id][$critid]) &&
                            isset($justifications[$member->id][$critid][$peer->id])
                        ) {
                            if (
                                isset($feedbackrendered["$member->id-$critid-$peer->id"])
                            ) {
                                // We do not want to call print_collapsible_region twice using the same id.
                                $feedbacktext = $feedbackrendered["$member->id-$critid-$peer->id"];
                            } else {
                                $feedbacktext = print_collapsible_region(
                                        $justifications[$member->id][$critid][$peer->id]->justification,
                                        'peerwork-feedback',
                                        'peerwork-feedback-' .
                                        $member->id .
                                        '-' .
                                        $critid .
                                        '-' .
                                        $peer->id,
                                        shorten_text(
                                            $justifications[$member->id][$critid][$peer->id]->justification,
                                            20
                                        ),
                                        '',
                                        true,
                                        true
                                    );

                                $feedbackrendered["$member->id-$critid-$peer->id"] = $feedbacktext;
                            }
                        }

                        $gradedby['gradefor'][] = [
                            'name' => fullname($peer),
                            'grade' => $grade . $override . $feedbacktext
                        ];
                    }

                    $feedbacktext = '';

                    if (!isset($grades->grades[$critid]) || !isset($grades->grades[$critid][$peer->id])
                            || !isset($grades->grades[$critid][$peer->id][$member->id])) {
                        $gradefor['gradedby'][] = [
                            'name' => $label,
                            'grade' => '-'
                        ];
                    } else {
                        $feedbacktext = '';

                        if (
                            $justenabledcrit &&
                            isset($justifications[$peer->id]) &&
                            isset($justifications[$peer->id][$critid]) &&
                            isset($justifications[$peer->id][$critid][$member->id])
                        ) {
                            if (
                                isset($feedbackrendered["$peer->id-$critid-$member->id"])
                            ) {
                                // We do not want to call print_collapsible_region twice using the same id.
                                $feedbacktext = $feedbackrendered["$peer->id-$critid-$member->id"];
                            } else {
                                $feedbacktext = print_collapsible_region(
                                        $justifications[$peer->id][$critid][$member->id]->justification,
                                        'peerwork-feedback',
                                        'peerwork-feedback-' .
                                        $peer->id .
                                        '-' .
                                        $critid .
                                        '-' .
                                        $member->id,
                                        shorten_text(
                                            $justifications[$peer->id][$critid][$member->id]->justification,
                                            20
                                        ),
                                        '',
                                        true,
                                        true
                                    );

                                $feedbackrendered["$peer->id-$critid-$member->id"] = $feedbacktext;
                            }
                        }

                        $gradefor['gradedby'][] = [
                            'name' => fullname($peer),
                            'grade' => $grades->grades[$critid][$peer->id][$member->id] . $feedbacktext
                        ];
                    }
                }

                // Only peergradedby is used in the default template.
                // Including peergradefor to allow more flexibility
                // when overriding template.
                $table['peergradedby'][] = $gradedby;
                $table['peergradefor'][] = $gradefor;
            }

            $data['criteria'][] = $table;
        }

        // If overriding peer grades is enabled, render a dropdown menu.
        $data['overridepeergrades'] = get_config('peerwork', 'overridepeergrades');
        $data['memberdropdown'] = array_values($memberdropdown);

        return $data;
    }
}
