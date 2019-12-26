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
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork;
defined('MOODLE_INTERNAL') || die();

/**
 * WebPA calculator.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webpa_calculator {

    /** @var float The non completion penalty. */
    protected $noncompletionpenalty;
    /** @var float The PA weighting. */
    protected $paweighting;

    /**
     * Constructor.
     *
     * @param float $paweighting The PA weighting.
     * @param float $noncompletionpenalty The completion penalty.
     */
    public function __construct($paweighting = 1, $noncompletionpenalty = 0) {
        $this->paweighting = $paweighting;
        $this->noncompletionpenalty = $noncompletionpenalty;
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
     */
    public function calculate($grades, $groupmark) {
        $memberids = array_keys($grades);

        // Calculate the factional scores, and record whether scores were submitted.
        $fracscores = [];
        $numsubmitted = 0;
        foreach ($memberids as $memberid) {
            $gradesgiven = $grades[$memberid];
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
        $noncompletionpenalties = array_reduce($memberids, function($carry, $memberid) use ($fracscores) {
            $ispenalised = empty($fracscores[$memberid]);
            $carry[$memberid] = $ispenalised ? $this->noncompletionpenalty : 0;
            return $carry;
        });

        // Calculate the grades again, but with weighting and penalties.
        $grades = array_reduce($memberids, function($carry, $memberid) use ($webpascores, $noncompletionpenalties, $groupmark) {
            $score = $webpascores[$memberid];

            $adjustedgroupmark = $groupmark * $this->paweighting;
            $automaticgrade = $groupmark - $adjustedgroupmark;
            $grade = max(0, min(100, $automaticgrade + ($score * $adjustedgroupmark)));

            $penaltyamount = $noncompletionpenalties[$memberid];
            if ($penaltyamount > 0) {
                $grade *= (1 - $penaltyamount);
            }

            $carry[$memberid] = $grade;
            return $carry;
        }, []);

        return new webpa_result($fracscores, $webpascores, $prelimgrades, $grades, $noncompletionpenalties);
    }
}
