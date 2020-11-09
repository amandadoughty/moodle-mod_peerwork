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
 * Notify students task.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Notify students task.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_students extends \core\task\scheduled_task {

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasknodifystudents', 'mod_peerwork');
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute() {
        global $DB;
        $origlang = current_language();
        $userfrom = \core_user::get_noreply_user();
        $uadditionalfields = ['lang', 'auth', 'suspended', 'deleted', 'emailstop'];

        $sql = "SELECT u.*, p.id AS peerworkid, p.course AS courseid, s.id AS submissionid
                  FROM {peerwork_grades} g
                  JOIN {peerwork_submission} s
                    ON g.submissionid = s.id
                  JOIN {peerwork} p
                    ON g.peerworkid = p.id
                  JOIN {user} u
                    ON g.userid = u.id
                 WHERE s.released > 0 AND s.releasednotified = 0
              ORDER BY p.id";

        $submissionids = [];
        $peerworkid = null;
        $records = $DB->get_recordset_sql($sql, []);
        foreach ($records as $record) {

            // Acquire the cm_info object if we've changed object. We could do without this
            // and get the cmid from the query itself, but as modinfo is cached, this should
            // be fast enough.
            if ($record->peerworkid !== $peerworkid) {
                $peerworkid = $record->peerworkid;
                $course = get_fast_modinfo($record->courseid);
                $cms = $course->get_instances_of('peerwork');
                $cm = $cms[$peerworkid];

                // Mark the submissions as sent. We do this every time we get to another module
                // to avoid having a ton of submissions to update at once.
                $this->mark_submission_notifications_sent(array_keys($submissionids));
                $submissionids = [];
            }

            $url = new \moodle_url('/mod/peerwork/view.php', ['id' => $cm->id]);
            $userto = clone($record);
            unset($userto->courseid);
            unset($userto->submissionid);
            unset($userto->peerworkid);

            $this->set_language_from_user($userto);
            $name = $cm->get_formatted_name();
            $subject = get_string('notifygradesreleasedsmall', 'mod_peerwork', $name);
            $message = get_string('notifygradesreleasedtext', 'mod_peerwork', [
                'name' => $name,
                'url' => $url->out()
            ]);
            $messagehtml = get_string('notifygradesreleasedhtml', 'mod_peerwork', [
                'name' => $name,
                'url' => $url->out()
            ]);

            $eventdata = new \core\message\message();
            $eventdata->courseid         = $record->courseid;
            $eventdata->modulename       = 'peerwork';
            $eventdata->userfrom         = $userfrom;
            $eventdata->userto           = $userto;
            $eventdata->subject          = $subject;
            $eventdata->fullmessage      = $message;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml  = $messagehtml;
            $eventdata->smallmessage     = $subject;

            $eventdata->name            = 'grade_released';
            $eventdata->component       = 'mod_peerwork';
            $eventdata->notification    = 1;
            $eventdata->contexturl      = $url;
            $eventdata->contexturlname  = $name;

            message_send($eventdata);

            // Record that we processed (maybe not all of it yet though) this submission ID.
            $submissionids[$record->submissionid] = true;

        }
        $records->close();

        // Commit the last submissions.
        $this->mark_submission_notifications_sent(array_keys($submissionids));

        // Restore the language.
        force_current_language($origlang);
    }

    /**
     * Mark the submissions as sent.
     *
     * @param int[] $submissionids The IDs.
     * @return void
     */
    protected function mark_submission_notifications_sent($submissionids) {
        global $DB;
        if (empty($submissionids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED);
        $sql = "UPDATE {peerwork_submission}
                   SET releasednotified = 1
                 WHERE id $insql";
        $DB->execute($sql, $inparams);
    }

    /**
     * Set language from user.
     *
     * @param stdClass $user A user.
     */
    protected function set_language_from_user($user) {
        global $CFG;
        if (!empty($user->lang)) {
            $lang = $user->lang;
        } else if (isset($CFG->lang)) {
            $lang = $CFG->lang;
        } else {
            $lang = 'en';
        }
        force_current_language($lang);
    }

}
