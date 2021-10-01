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
 * WebPA calculator.
 *
 * @package    peerworkcalculator_webpa
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace peerworkcalculator_webpa;

defined('MOODLE_INTERNAL') || die();

/**
 * WebPA calculator.
 *
 * @package    peerworkcalculator_webpa
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calculator extends \mod_peerwork\peerworkcalculator_plugin {

    /**
     * Get the name of the webPA calculator plugin
     * @return string
     */
    public function get_name() {
        return get_string('webpa', 'peerworkcalculator_webpa');
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
     * [
     *  'alice' => [
     *       'alice' => [2,2],
     *       'bob' => [1,3],
     *       'claire' => [3, 0],
     *       'david' => [1,1],
     *       'elaine' => [1,0]
     *   ],
     *   'bob' => [
     *       'alice' => [2,1],
     *       'bob' => [2,3],
     *       'claire' => [2,1],
     *       'david' => [1,1],
     *       'elaine' => [0,0]
     *   ],
     *   'claire' => [
     *       'alice' => [2,2],
     *       'bob' => [2,2],
     *       'claire' => [2,2],
     *       'david' => [2,2],
     *       'elaine' => [2,2]
     *   ],
     *   'david' => [
     *       'alice' => [2,1],
     *       'bob' => [3,2],
     *       'claire' => [2,2],
     *       'david' => [1,2],
     *       'elaine' => [1,0]
     *   ],
     *   'elaine' => []
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
        $totalscores = [];
        $fracscores = [];
        $numsubmitted = 0;

        // Calculate the total scores.
        foreach ($memberids as $memberid) {
            foreach ($grades as $graderid => $gradesgiven) {
                if (!isset($totalscores[$graderid])) {
                    $totalscores[$graderid] = [];
                }

                if (isset($gradesgiven[$memberid])) {
                    $sum = array_reduce($gradesgiven[$memberid], function($carry, $item) {
                        $carry += $item;
                        return $carry;
                    });

                    $totalscores[$graderid][$memberid] = $sum;
                }
            }
        }

        // Calculate the fractional scores, and record whether scores were submitted.
        foreach ($memberids as $memberid) {
            $gradesgiven = $totalscores[$memberid];
            $total = array_sum($gradesgiven);

            $fracscores[$memberid] = array_reduce(array_keys($gradesgiven), function($carry, $peerid) use ($total, $gradesgiven) {
                $grade = $gradesgiven[$peerid];
                $carry[$peerid] = $total > 0 ? $grade / $total : 0;
                return $carry;
            }, []);

            $numsubmitted += !empty($fracscores[$memberid]) ? 1 : 0;
        }

        // Initialise everyone's score at 0.
        $webpascores = array_reduce($memberids, function($carry, $memberid) {
            $carry[$memberid] = 0;
            return $carry;
        }, []);

        // Walk through the individual scores given, and sum them up.
        foreach ($fracscores as $gradesgiven) {
            foreach ($gradesgiven as $memberid => $fraction) {
                $webpascores[$memberid] += $fraction;
            }
        }

        // Apply the fudge factor to all scores received.
        $nummembers = count($memberids);
        $fudgefactor = $numsubmitted > 0 ? $nummembers / $numsubmitted : 1;
        $webpascores = array_map(function($grade) use ($fudgefactor) {
            return $grade * $fudgefactor;
        }, $webpascores);

        // Calculate the students' preliminary grade (excludes weighting and penalties).
        $prelimgrades = array_map(function($score) use ($groupmark) {
            return max(0, min(100, $score * $groupmark));
        }, $webpascores);

        // Calculate penalties.
        $noncompletionpenalties = array_reduce($memberids, function($carry, $memberid) use ($fracscores, $noncompletionpenalty) {
            $ispenalised = empty($fracscores[$memberid]);
            $carry[$memberid] = $ispenalised ? $noncompletionpenalty : 0;
            return $carry;
        });

        // Calculate the grades again, but with weighting and penalties.
        $grades = array_reduce(
            $memberids,
            function($carry, $memberid) use ($webpascores, $noncompletionpenalties, $groupmark, $paweighting) {
                $score = $webpascores[$memberid];

                $adjustedgroupmark = $groupmark * $paweighting;
                $automaticgrade = $groupmark - $adjustedgroupmark;
                $grade = max(0, min(100, $automaticgrade + ($score * $adjustedgroupmark)));

                $penaltyamount = $noncompletionpenalties[$memberid];
                if ($penaltyamount > 0) {
                    $grade *= (1 - $penaltyamount);
                }

                $carry[$memberid] = $grade;
                return $carry;
            },
        []);

        return new \mod_peerwork\pa_result($fracscores, $webpascores, $prelimgrades, $grades, $noncompletionpenalties);
    }

    /**
     * Function to return if calculation uses paweighting.
     *
     * @return bool
     */
    public static function usespaweighting() {
        return true;
    }
}
