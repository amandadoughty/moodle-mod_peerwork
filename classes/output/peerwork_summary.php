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
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork\output;
defined('MOODLE_INTERNAL') || die();

/**
 * Summary.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerwork_summary implements \renderable {

    /** @var object The group. */
    public $group;
    /** @var object The data. */
    public $data;
    /** @var object[] The members gradeable. */
    public $membersgradeable;
    /** @var object The peerwork. */
    public $peerwork;
    /** @var object The status. */
    public $status;

    /**
     * Constructor.
     *
     * @param stdClass $group The group.
     * @param stdClass $data The data.
     * @param stdClass[] $membersgradeable The members gradeable.
     * @param stdClass $peerwork The peerwork.
     * @param stdClass|null $status The status.
     */
    public function __construct($group, $data, $membersgradeable, $peerwork, $status = null) {
        $this->group = $group;
        $this->data = $data;
        $this->membersgradeable = $membersgradeable;
        $this->peerwork = $peerwork;
        $this->status = $status ?? get_string('draftnotsubmitted', 'mod_peerwork');
    }
}
