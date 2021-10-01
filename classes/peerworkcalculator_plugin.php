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
 * Calculator base class.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     2020 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork;

defined('MOODLE_INTERNAL') || die();

/**
 * Calculator.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     2020 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerworkcalculator_plugin extends peerwork_plugin {
    /**
     * Get the name of the simple calculator plugin
     * @return string
     */
    public function get_name() {
        return get_string('base', 'peerworkcalculator_plugin');
    }

    /**
     * return subtype name of the plugin
     *
     * @return string
     */
    final public function get_subtype() {
        return 'peerworkcalculator';
    }

    /**
     * Calculate.
     *
     * Each member of the group must have an associated key in the $grades,
     * under which an array of the grades they gave to other members indexed
     * by member ID.
     *
     * In the example below, Alice rated Bob 4, and Elaine did not submit any marks..
     *
     * $grades = [
     *     'alice' => [
     *         'alice' => 4,
     *         'bob' => 4,
     *         'claire' => 3,
     *         'david' => 2,
     *         'elaine' => 1
     *     ],
     *     'bob' => [
     *         'alice' => 3,
     *         'bob' => 5,
     *         'claire' => 3,
     *         'david' => 2,
     *         'elaine' => 0
     *     ],
     *     'claire' => [
     *         'alice' => 4,
     *         'bob' => 4,
     *         'claire' => 4,
     *         'david' => 4,
     *         'elaine' => 4
     *     ],
     *     'david' => [
     *         'alice' => 3,
     *         'bob' => 5,
     *         'claire' => 4,
     *         'david' => 3,
     *         'elaine' => 1
     *     ],
     *     'elaine' => []
     * ];
     *
     * @param array $grades The list of marks given.
     * @param int $groupmark The mark given to the group.
     * @param int $noncompletionpenalty The penalty to be applied.
     * @param int $paweighting The weighting to be applied.
     * @param bool $selfgrade If self grading is enabled.
     * @return mod_peerwork\pa_result.
     */
    public function calculate($grades, $groupmark, $noncompletionpenalty = 0, $paweighting = 1, $selfgrade = false) {
        $memberids = array_keys($grades);
        $sumscores = [];

        foreach ($memberids as $memberid) {
            foreach ($grades as $graderid => $gradesgiven) {
                if (!isset($gradesgiven[$memberid])) {
                    $gradesgiven[$graderid] = [];
                    continue;
                }

                $sum = array_reduce($gradesgiven[$memberid], function($carry, $item) {
                    $carry += $item;
                    return $carry;
                });

                $sumscores[$graderid][$memberid] = $sum;
            }
        }

        // Initialise everyone's score at 0.
        $pascores = array_reduce($memberids, function($carry, $memberid) {
            $carry[$memberid] = 0;
            return $carry;
        }, []);

        // Calculate the students' preliminary grade (excludes weighting and penalties).
        $prelimgrades = array_map(function($score) use ($groupmark) {
            // Give everyone the groupmark.
            return $groupmark;
        }, $pascores);

        // Calculate penalties.
        $noncompletionpenalties = array_reduce($memberids, function($carry, $memberid) use ($grades, $noncompletionpenalty) {
            $ispenalised = empty($grades[$memberid]);
            $carry[$memberid] = $ispenalised ? $noncompletionpenalty : 0;
            return $carry;
        });

        // Calculate the grades again, but with weighting and penalties.
        $grades = array_reduce(
            $memberids,
            function($carry, $memberid) use ($noncompletionpenalties, $groupmark) {
                $grade = $groupmark;
                $penaltyamount = $noncompletionpenalties[$memberid];
                if ($penaltyamount > 0) {
                    $grade *= (1 - $penaltyamount);
                }

                $carry[$memberid] = $grade;
                return $carry;
            },
        []);

        return new \mod_peerwork\pa_result($sumscores, $pascores, $prelimgrades, $grades, $noncompletionpenalties);
    }

    /**
     * Function to return if calculation uses paweighting.
     *
     * @return bool
     */
    public static function usespaweighting() {
        return false;
    }

    /**
     * Function to return the scales that can be used.
     *
     * @param int $courseid The course id or 0 for just sitewide scales.
     *
     * @return array/bool false if no resriction on scales.
     */
    public static function get_scales_menu($courseid = 0) {
        return false;
    }

    /**
     * Function to translate scale into score.
     *
     * @param array $grades The list of marks given.
     * @return array $grades.
     */
    public function translate_scales_to_scores($grades) {
        return $grades;
    }

    /**
     * Get the settings for calculator plugin.
     *
     * @param MoodleQuickForm $mform The form to add the elements to
     * @return $array
     */
    public function get_settings(\MoodleQuickForm $mform) {
        if ($this->usespaweighting()) {
            if (!$this->peerwork) {
                $paw = get_config('peerwork', 'paweighting');
            } else {
                $paw = $this->peerwork->paweighting;
            }

            $steps = range(0, 100, 1);
            $zerotohundredpcopts = array_combine($steps, array_map(function($i) {
                return $i . '%';
            }, $steps));

            $paweighting = $mform->createElement(
                'select',
                'paweighting',
                get_string('paweighting', 'peerwork'),
                $zerotohundredpcopts
            );

            $mform->insertElementBefore($paweighting, 'calculatorsettings');
            $mform->setDefault('paweighting', $paw);
            $mform->addHelpButton('paweighting', 'paweighting', 'peerwork');
        }
    }
}
