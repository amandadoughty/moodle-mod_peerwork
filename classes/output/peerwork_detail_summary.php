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
     * @param stdClass[] $criterion The criterion.
     * @param stdClass $grades The grades.
     * @param array $justifications The justifications.
     * @param stdClass[] $members The members.
     * @param array $lockedgraders The locked graders.
     * @param stdClass $peerwork The peerwork.
     * @param bool $canunlock Whether the peer grades can be unlocked.
     * @param bool $justenabledcrit Whether justification is enabled.
     * @param int $cmid The course module id.
     * @param int $groupid The group id.
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
                $url = new \moodle_url('/mod/peerwork/override.php', [
                    'id' => $cmid,
                    'pid' => $peerwork->id,
                    'gid' => $groupid,
                    'uid' => $member->id,
                ]);

                $memberdropdown[$member->id] = [
                    'id' => $cmid,
                    'peerworkid' => $peerwork->id,
                    'groupid' => $groupid,
                    'gradedby' => $member->id,
                    'name' => fullname($member),
                    'href' => $url->out()
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
                        $peergrade = null;

                        // Display tool tip if original peer grade has been
                        // overridden.
                        if (
                            // The peergrade may be null.
                            isset($grades->overrides[$critid]) &&
                            isset($grades->overrides[$critid][$member->id]) &&
                            isset($grades->overrides[$critid][$member->id][$peer->id])
                        ) {
                            $peergrade = $grades->overrides[$critid][$member->id][$peer->id];
                        }

                        if (
                            array_key_exists($critid, $grades->comments) &&
                            array_key_exists($member->id, $grades->comments[$critid]) &&
                            array_key_exists($peer->id, $grades->comments[$critid][$member->id])
                        ) {
                            $comments = $grades->comments[$critid][$member->id][$peer->id];
                        }

                        if ($peergrade != $grade) {
                            $peergrade = $peergrade == null ? get_string('none', 'mod_peerwork') : $peergrade;
                            $comments = $comments == null ? get_string('none', 'mod_peerwork') : $comments;
                            $title = get_string('gradeoverridden', 'mod_peerwork', $peergrade);
                            $title .= ' ' . get_string('comment', 'mod_peerwork') . $comments;
                            $attributes = ['title' => $title, 'aria-hidden' => true];
                            $pixicon = new \pix_icon('docs', '', 'moodle', $attributes);
                            $override = $output->render($pixicon);
                            $override .= \html_writer::tag('span', $title, ['class' => 'sr-only']);
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
                        $override = '';
                        $grade = $grades->grades[$critid][$peer->id][$member->id];

                        // Display tool tip if original peer grade has been
                        // overridden.
                        if (
                            // The peergrade may be null.
                            array_key_exists($critid, $grades->overrides) &&
                            array_key_exists($peer->id, $grades->overrides[$critid]) &&
                            array_key_exists($member->id, $grades->overrides[$critid][$peer->id])
                        ) {
                            $peergrade = $grades->overrides[$critid][$peer->id][$member->id];

                            if (
                                array_key_exists($critid, $grades->comments) &&
                                array_key_exists($peer->id, $grades->comments[$critid]) &&
                                array_key_exists($member->id, $grades->comments[$critid][$peer->id])
                            ) {
                                $comments = $grades->comments[$critid][$peer->id][$member->id];
                            }

                            if ($peergrade != $grade) {
                                $peergrade = $peergrade == null ? '-' : $peergrade;
                                $comments = $comments == null ? get_string('none') : $comments;
                                $title = get_string('gradeoverridden', 'mod_peerwork', $peergrade);
                                $title .= ' ' . get_string('comment', 'mod_peerwork') . $comments;
                                $attributes = ['title' => $title, 'aria-hidden' => true];
                                $pixicon = new \pix_icon('docs', '', 'moodle', $attributes);
                                $override = $output->render($pixicon);
                                $override .= \html_writer::tag('span', $title, ['class' => 'sr-only']);
                            }
                        }

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
                            'grade' => $grade . $override . $feedbacktext
                        ];
                    }
                }

                // Including extra detail in peergradefor to allow more flexibility
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
