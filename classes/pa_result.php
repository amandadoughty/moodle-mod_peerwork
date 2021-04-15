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
 * PA result.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork;
defined('MOODLE_INTERNAL') || die();

/**
 * PA result.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pa_result {

    /** @var array The reduced scores. */
    protected $redscores;
    /** @var array The PA scores. */
    protected $pascores;
    /** @var array The preliminary grades. */
    protected $prelimgrades;
    /** @var array The final grades. */
    protected $grades;
    /** @var array The penalities. */
    protected $noncomplpenalties;

    /**
     * Constructor.
     *
     * All parameters are indexed by member ID.
     * The reduced scores contains an array indexed by peer IDs.
     *
     * @param array $redscores The reduced scores.
     * @param array $pascores The PA scores.
     * @param array $prelimgrades The preliminary grades.
     * @param array $grades The final grades.
     * @param array $noncomplpenalties The non-completion penalties.
     */
    public function __construct($redscores, $pascores, $prelimgrades, $grades, $noncomplpenalties) {
        $this->redscores = $redscores;
        $this->pascores = $pascores;
        $this->prelimgrades = $prelimgrades;
        $this->grades = $grades;
        $this->noncomplpenalties = $noncomplpenalties;
    }

    /**
     * Get reduced scores given by this member.
     *
     * @param int|string $memberid The member ID.
     * @return array Indexed by member ID.
     */
    public function get_reduced_scores($memberid) {
        if (isset($this->redscores[$memberid])) {
            return $this->redscores[$memberid];
        }
    }

    /**
     * Get the final grade.
     *
     * @param int|string $memberid The member ID.
     * @return float Between 0 and 100.
     */
    public function get_grade($memberid) {
        if (isset($this->grades[$memberid])) {
            return $this->grades[$memberid];
        }
    }

    /**
     * Get the final grades.
     *
     * @return float[] Between 0 and 100.
     */
    public function get_grades() {
        return $this->grades;
    }

    /**
     * Get the member IDs.
     *
     * @return int|string[] The member IDs.
     */
    public function get_member_ids() {
        return array_values($this->redscores);
    }

    /**
     * Get the non-completion penalty.
     *
     * @param int|string $memberid The member ID.
     * @return float Between 0 and 1.
     */
    public function get_non_completion_penalty($memberid) {
        return $this->noncomplpenalties[$memberid];
    }

    /**
     * Get the preliminary grade.
     *
     * This is prior to applying penalties.
     *
     * @param int|string $memberid The member ID.
     * @return int Between 0 and 100.
     */
    public function get_preliminary_grade($memberid) {
        return $this->prelimgrades[$memberid];
    }

    /**
     * Get the PA score.
     *
     * @param int|string $memberid The member ID.
     * @return float The score.
     */
    public function get_score($memberid) {
        return $this->pascores[$memberid];
    }

    /**
     * Get the PA scores.
     *
     * @return float[] The scores.
     */
    public function get_scores() {
        return $this->pascores;
    }

    /**
     * Get the member has submitted marks.
     *
     * @param int|string $memberid The member ID.
     * @return bool Whether the user has submitted marks.
     */
    public function has_submitted($memberid) {
        return !empty($this->redscores[$memberid]);
    }
}
