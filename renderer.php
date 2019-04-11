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
 * @package    mod
 * @subpackage peerassessment
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_peerassessment_renderer extends plugin_renderer_base {
    public function render_peerassessment_summary(peerassessment_summary $summary) {
        $group = $summary->group;
        $data = $summary->data;
        $membersgradeable = $summary->membersgradeable;
        $peerassessment = $summary->peerassessment;
        $isopen = peerassessment_is_open($peerassessment, $group->id);
        $status = $summary->status;
        $files = $data['files'];        
        if (isset($data['outstanding'])) {
            $outstanding = $data['outstanding'];
        } else {
            $outstanding = array();
        }
        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell('Group');
        $cell2 = new html_table_cell($group->name);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $row = new html_table_row();
        $cell1 = new html_table_cell('Submission status');

        $users = '';
        foreach ($outstanding as $member) {
            $users .= fullname($member) . ',';
        }
        $text = "<p>$status</p>";
        $users = rtrim($users, ',');
        if ($users) {
            if ($isopen->code) {
                $text .= "<p>". get_string('userswhodidnotsubmitbefore', 'peerassessment', $users) . "</p>";
            } else {
                $text .= "<p>". get_string('userswhodidnotsubmitafter', 'peerassessment', $users) . "</p>";
            }
        } else {
            $text .= get_string('allmemberssubmitted', 'peerassessment');
        }

        $cell2 = new html_table_cell($text);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        if ($peerassessment->duedate) {
            $row = new html_table_row();
            $cell1 = new html_table_cell('Due date');
            $cell2 = new html_table_cell(userdate($peerassessment->duedate));

            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            $row = new html_table_row();
            $cell1 = new html_table_cell('Time remaining');
            if ($peerassessment->duedate > time()) {
                $cell2 = new html_table_cell(format_time($peerassessment->duedate - time()));
            } else {
                $cell2 = new html_table_cell('(over due by ' . format_time($peerassessment->duedate - time()) .')');
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if( $data['maxfiles'] > 0 ) { 
        	$fcontent = implode('<br />', $files);
        	if( count($files) == 0 ) {
        		$fcontent = get_string('nothingsubmitted', 'peerassessment' );
        	}
        	
	        $row = new html_table_row();
	        $cell1 = new html_table_cell('File submission');
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
            $cell1 = new html_table_cell('My final grade');
            $cell2 = new html_table_cell($data['mygrade']);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['feedback'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell('Feedback');
            $cell2 = new html_table_cell($data['feedback']);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['feedback_files'])) {
            $row = new html_table_row();
            $cell1 = new html_table_cell('Feedback file');
            $cell2 = new html_table_cell(implode(',', $data['feedback_files']));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        return html_writer::table($t);
    }
}

class peerassessment_summary implements renderable {
    public function __construct($group, $data, $membersgradeable, $peerassessment, $status = 'Draft (not submitted).') {
        $this->group = $group;
        $this->data = $data;
        $this->membersgradeable = $membersgradeable;
        $this->peerassessment = $peerassessment;
        $this->status = $status;
    }
}