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
 * Group grader.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork;
defined('MOODLE_INTERNAL') || die();

/**
 * Group grader.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_grader {

    /** @var object The peerwork instance. */
    protected $peerwork;
    /** @var context_module The context of the module. */
    protected $context;
    /** @var int The group ID. */
    protected $groupid;
    /** @var false|object False is the return value from database API, else we have an instance. */
    protected $submission;
    /** @var int The feedback draftitemid, prior to committing the grades. */
    protected $feedbackdraftitemid;
    /** @var array An array of revised grades, or null. */
    protected $revisedgrades;

    /**
     * Constructor.
     *
     * @param stdClass $peerwork The instance.
     * @param int $groupid The group ID.
     * @param null|false|object $submission The existing submission record, if known. False means non-existant.
     */
    public function __construct($peerwork, $groupid, $submission = null) {
        global $DB;

        $this->peerwork = $peerwork;
        $this->groupid = $groupid;

        // Fetch the context.
        $modinfo = get_fast_modinfo($peerwork->course);
        $cm = $modinfo->get_instances_of('peerwork');
        if (!isset($cm[$peerwork->id])) {
            throw new coding_exception('Could not find the peerwork instance in its course.');
        }
        $cminfo = $cm[$peerwork->id];
        $this->context = $cminfo->context;

        // Find the submission, if any.
        if ($submission === null) {
            $submission = $DB->get_record('peerwork_submission', ['peerworkid' => $peerwork->id, 'groupid' => $groupid]);
        }
        $this->submission = $submission;
    }

    /**
     * Commit the grade.
     *
     * @return void
     */
    public function commit() {
        global $DB;

        if (!$this->was_graded()) {
            throw new coding_exception('The group has not yet been graded.');
        }

        // Save the submission.
        $submission = $this->submission;
        if (isset($submission->id)) {
            $DB->update_record('peerwork_submission', $submission);
        } else {
            // Insert then fetch, so we have the full record.
            $submission->id = $DB->insert_record('peerwork_submission', $submission);
            $submission = $DB->get_record('peerwork_submission', ['id' => $submission->id], '*', MUST_EXIST);
            $this->submission = $submission;
        }

        // Save the feedback files.
        if ($this->feedbackdraftitemid) {
            file_save_draft_area_files($this->feedbackdraftitemid, $this->context->id, 'mod_peerwork', 'feedback_files',
                $this->groupid, \mod_peerwork_details_form::$fileoptions);
            unset($this->feedbackdraftitemid);
        }

        // Save the individual grades.
        $members = groups_get_members($this->groupid);
        $group = $DB->get_record('groups', ['id' => $this->groupid], '*', MUST_EXIST);
        peerwork_update_local_grades($this->peerwork, $group, $this->submission, array_keys($members), $this->revisedgrades);

        // Finally, trigger the event.
        $params = array(
            'objectid' => $submission->id,
            'context' => $this->context,
            'other' => [
                'groupid' => $group->id,
                'groupname' => $group->name,
                'grade' => $submission->grade
            ]
        );
        $event = \mod_peerwork\event\submission_graded::create($params);
        $event->add_record_snapshot('peerwork_submission', $submission);
        $event->trigger();
    }

    /**
     * Get the grade.
     *
     * @return null|grade
     */
    public function get_grade() {
        return $this->was_graded() ? $this->submission->grade : null;
    }

    /**
     * Get the submission record.
     *
     * This always returns a record, whether the submission exists or not.
     * But this must only be used when assigning values, not when reading them.
     *
     * @return object
     */
    protected function get_submission_record() {
        $record = $this->submission;
        if (!$record) {
            $record = (object) [
                'peerworkid' => $this->peerwork->id,
                'groupid' => $this->groupid
            ];
        }
        return $record;
    }

    /**
     * Set the feedback.
     *
     * @param string $text The text.
     * @param int $format The format.
     * @param int $draftitemid The draft item ID.
     */
    public function set_feedback($text, $format, $draftitemid) {
        $record = $this->get_submission_record();
        $record->feedbacktext = $text;
        $record->feedbackformat = $format;
        $this->submission = $record;
        $this->feedbackdraftitemid = $draftitemid;
    }

    /**
     * Set the grade.
     *
     * @param int $grade The grade.
     * @param int $paweighting The PA weighting, value between 0-100.
     */
    public function set_grade($grade, $paweighting = null) {
        global $USER;

        if ($paweighting === null) {
            if ($this->was_graded()) {
                $paweighting = $this->submission->paweighting;
            } else {
                $paweighting = $this->peerwork->paweighting;
            }
        }
        $paweighting = min(100, max(0, (int) $paweighting));

        $record = $this->get_submission_record();
        $record->grade = min(100, max(0, (float) $grade));
        $record->paweighting = $paweighting;
        $record->gradedby = $USER->id;
        $record->timegraded = time();

        $this->submission = $record;
    }

    /**
     * Set the revised grades.
     *
     * Passing null means that revised grades do not change and remain
     * what they were the last time the group was graded. When passing
     * any value, each key must be a user ID, and any missing key
     * signifies that the particular user no longer has a revised grade.
     *
     * @param null|array $allgrades Indexed by member ID.
     */
    public function set_revised_grades($allgrades = null) {
        $this->revisedgrades = $allgrades;
    }

    /**
     * Whether the group was graded.
     *
     * @return bool
     */
    public function was_graded() {
        return $this->submission && !empty($this->submission->timegraded);
    }

}
