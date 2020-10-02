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
 * WebPA testcase.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * WebPA testcase.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerworkcalculator_webpa_calculator_testcase extends basic_testcase {

    /**
     * Test the WebPA result with no weighting or penalties.
     */
    public function test_webpa_result_basic() {
        $peerwork = new \stdClass();
        $peerwork->id = 1;
        $grades = $this->get_sample();
        $calculator = new peerworkcalculator_webpa\calculator($peerwork, 'webpa');
        $result = $calculator->calculate($grades, 80);

        $fracs = $result->get_reduced_scores('alice');
        $this->assertEquals(1, array_sum($fracs));
        $this->assertEquals([
            'alice' => 0.29,
            'bob' => 0.29,
            'claire' => 0.21,
            'david' => 0.14,
            'elaine' => 0.07
        ], array_map(function($a) {
            return round($a, 2);  // We must round because the data we were given is rounded.
        }, $fracs));

        $fracs = $result->get_reduced_scores('bob');
        $this->assertEquals(0.23, round($fracs['alice'], 2));

        $fracs = $result->get_reduced_scores('claire');
        $this->assertEquals(0.20, round($fracs['alice'], 2));

        $fracs = $result->get_reduced_scores('david');
        $this->assertEquals(0.19, round($fracs['alice'], 2));

        $this->assertTrue($result->has_submitted('alice'));
        $this->assertTrue($result->has_submitted('bob'));
        $this->assertTrue($result->has_submitted('claire'));
        $this->assertTrue($result->has_submitted('david'));
        $this->assertFalse($result->has_submitted('elaine'));

        // Values are stlightly different from the source because of rounding issues.
        $this->assertEquals(5, array_sum($result->get_scores()));
        $this->assertEquals(1.13, round($result->get_score('alice'), 2));
        $this->assertEquals(1.48, round($result->get_score('bob'), 2));
        $this->assertEquals(1.12, round($result->get_score('claire'), 2));
        $this->assertEquals(0.86, round($result->get_score('david'), 2));
        $this->assertEquals(0.42, round($result->get_score('elaine'), 2));

        $this->assertEquals(90.4, round($result->get_grade('alice'), 2));
        $this->assertEquals(100, round($result->get_grade('bob'), 2));
        $this->assertEquals(89.51, round($result->get_grade('claire'), 2));
        $this->assertEquals(68.42, round($result->get_grade('david'), 2));
        $this->assertEquals(33.39, round($result->get_grade('elaine'), 2));
    }

    /**
     * Test the WebPA result with weighting.
     */
    public function test_webpa_result_with_weighting() {
        $peerwork = new \stdClass();
        $peerwork->id = 1;
        $grades = $this->get_sample();
        $calculator = new peerworkcalculator_webpa\calculator($peerwork, 'webpa');
        $result = $calculator->calculate($grades, 80, 0, .5);

        // This does not affect the scores.
        $this->assertEquals(5, array_sum($result->get_scores()));
        $this->assertEquals(1.13, round($result->get_score('alice'), 2));
        $this->assertEquals(1.48, round($result->get_score('bob'), 2));
        $this->assertEquals(1.12, round($result->get_score('claire'), 2));
        $this->assertEquals(0.86, round($result->get_score('david'), 2));
        $this->assertEquals(0.42, round($result->get_score('elaine'), 2));

        // Values are stlightly different from the source because of rounding issues.
        $this->assertEquals(85.2, round($result->get_grade('alice'), 2));
        $this->assertEquals(99.14, round($result->get_grade('bob'), 2));
        $this->assertEquals(84.75, round($result->get_grade('claire'), 2));
        $this->assertEquals(74.21, round($result->get_grade('david'), 2));
        $this->assertEquals(56.7, round($result->get_grade('elaine'), 2));
    }

    /**
     * Test the WebPA result with weighting and penalty.
     */
    public function test_webpa_result_with_weighting_and_penalty() {
        $peerwork = new \stdClass();
        $peerwork->id = 1;
        $grades = $this->get_sample();
        $calculator = new peerworkcalculator_webpa\calculator($peerwork, 'webpa');
        $result = $calculator->calculate($grades, 80, .1, .5);

        // This does not affect the scores.
        $this->assertEquals(5, array_sum($result->get_scores()));
        $this->assertEquals(1.13, round($result->get_score('alice'), 2));
        $this->assertEquals(1.48, round($result->get_score('bob'), 2));
        $this->assertEquals(1.12, round($result->get_score('claire'), 2));
        $this->assertEquals(0.86, round($result->get_score('david'), 2));
        $this->assertEquals(0.42, round($result->get_score('elaine'), 2));

        // Values are stlightly different from the source because of rounding issues.
        $this->assertEquals(5, array_sum($result->get_scores()));
        $this->assertEquals(85.2, round($result->get_grade('alice'), 2));
        $this->assertEquals(99.14, round($result->get_grade('bob'), 2));
        $this->assertEquals(84.75, round($result->get_grade('claire'), 2));
        $this->assertEquals(74.21, round($result->get_grade('david'), 2));
        $this->assertEquals(51.03, round($result->get_grade('elaine'), 2));
    }

    /**
     * Data sample.
     *
     * Adapted from https://webpaproject.lboro.ac.uk/academic-guidance/a-worked-example-of-the-scoring-algorithm
     *
     * @return array
     */
    protected function get_sample() {
        return [
            'alice' => [
                'alice' => [2, 2],
                'bob' => [1, 3],
                'claire' => [3, 0],
                'david' => [1, 1],
                'elaine' => [1, 0]
            ],
            'bob' => [
                'alice' => [2, 1],
                'bob' => [2, 3],
                'claire' => [2, 1],
                'david' => [1, 1],
                'elaine' => [0, 0]
            ],
            'claire' => [
                'alice' => [2, 2],
                'bob' => [2, 2],
                'claire' => [2, 2],
                'david' => [2, 2],
                'elaine' => [2, 2]
            ],
            'david' => [
                'alice' => [2, 1],
                'bob' => [3, 2],
                'claire' => [2, 2],
                'david' => [1, 2],
                'elaine' => [1, 0]
            ],
            'elaine' => []
        ];
    }

}
