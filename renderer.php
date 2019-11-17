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
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_peerwork\output\peerwork_summary;

/**
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerwork_renderer extends plugin_renderer_base {

    /**
     * Render summary.
     *
     * @param peerwork_summary $summary The summary.
     * @return string
     */
    public function render_peerwork_summary(peerwork_summary $summary) {
        $group = $summary->group;
        $data = $summary->data;
        $membersgradeable = $summary->membersgradeable;
        $peerwork = $summary->peerwork;
        $isopen = peerwork_is_open($peerwork, $group->id);
        $status = $summary->status;
        $files = $data['files'];
        $outstanding = $data['outstanding'] ?? [];

        $t = new html_table();
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('group'));
        $cell2 = new html_table_cell($group->name);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $row = new html_table_row();
        $cell1 = new html_table_cell('Submission status');

        $text = "<p>$status</p>";
        if (!empty($outstanding)) {
            $userslist = implode(', ', array_map('fullname', $outstanding));
            if ($isopen->code) {
                $text .= "<p>". get_string('userswhodidnotsubmitbefore', 'peerwork', $userslist) . "</p>";
            } else {
                $text .= "<p>". get_string('userswhodidnotsubmitafter', 'peerwork', $userslist) . "</p>";
            }
        } else {
            $text .= get_string('allmemberssubmitted', 'peerwork');
        }

        $cell2 = new html_table_cell($text);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        if ($peerwork->duedate) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'mod_peerwork'));
            $cell2 = new html_table_cell(userdate($peerwork->duedate));

            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'mod_peerwork'));
            if ($peerwork->duedate > time()) {
                $cell2 = new html_table_cell(format_time($peerwork->duedate - time()));
            } else {
                $cell2 = new html_table_cell(get_string('noteoverdueby', 'mod_peerwork',
                    format_time($peerwork->duedate - time())));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if ($peerwork->maxfiles > 0 ) {
            $fcontent = implode('<br />', $files);
            if (count($files) == 0) {
                $fcontent = get_string('nothingsubmitted', 'peerwork' );
            }

            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submission', 'mod_peerwork'));
            $cell2 = new html_table_cell($fcontent);

            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['igraded'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell('Total grades awarded');

            $users = '';
            foreach ($membersgradeable as $member) {
                $users .= '<p>' . fullname($member) . ': ' . $data['igraded']->grade[$member->id] . '</p>';
            }

            $cell2 = new html_table_cell($users);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['gradedme'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell('Graded me');

            $users = '';
            foreach ($membersgradeable as $member) {
                $users .= '<p>' . fullname($member) . ': ' . $data['gradedme']->grade[$member->id] .
                ' (' . $data['gradedme']->feedback[$member->id] . ')</p>';
            }

            $cell2 = new html_table_cell($users);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['mygrade'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('myfinalgrade', 'mod_peerwork'));
            $cell2 = new html_table_cell(format_float($data['mygrade'], 2));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['feedback'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('feedback', 'mod_peerwork'));
            $cell2 = new html_table_cell($data['feedback']);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['feedback_files'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('feedbackfiles', 'mod_peerwork'));
            $cell2 = new html_table_cell(implode(', ', $data['feedback_files']));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        return html_writer::table($t);
    }
}
