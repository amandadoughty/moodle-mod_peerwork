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
 * Renderer.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_peerwork\output\peerwork_summary;

/**
 * Renderer class.
 *
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

        if (isset($data['peergrades']) && peerwork_can_students_view_peer_grades($peerwork)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('peergrades', 'mod_peerwork'));

            $displayscalelabel = false;
            $scales = grade_scale::fetch_all_global();
            $isanon = $peerwork->peergradesvisibility != MOD_PEERWORK_PEER_GRADES_VISIBLE_USER;
            $displaytotals = !empty($peerwork->displaypeergradestotals);
            $members = (array) (object) $membersgradeable;
            if ($isanon) {
                shuffle($members);
            }

            $parts = array_map(function($criteriaid, $criteria) use ($data, $displaytotals, $displayscalelabel,
                    $isanon, $members, $scales) {
                $gradeinfo = $data['peergrades'][$criteriaid] ?? [];
                $html = html_writer::start_div();
                $html .= html_writer::div($criteria->description);

                $scaleid = abs($criteria->grade);
                $scale = isset($scales[$scaleid]) ? $scales[$scaleid] : null;
                if ($scale) {
                    $scaleitems = $scale->load_items();
                }

                $ratings = [];
                $totalscore = 0;
                $totalmax = 0;
                foreach ($members as $member) {
                    $grade = $gradeinfo[$member->id] ?? null;
                    $scalevalue = '-';

                    if (!$grade && $isanon) {
                        continue;
                    } else if ($grade && $scale) {
                        if ($displayscalelabel) {
                            $scalevalue = $scaleitems[$grade->grade];
                        } else {
                            $scalevalue = ($grade->grade + 1) . ' / ' . count($scaleitems);
                        }

                        $totalscore += ($grade->grade + 1);
                        $totalmax += count($scaleitems);
                    }

                    if ($isanon) {
                        $ratings[] = $scalevalue;
                    } else {
                        $ratings[] = get_string('peerratedyou', 'mod_peerwork', [
                            'name' => fullname($member),
                            'grade' => $scalevalue
                        ]);
                    }
                }

                if (empty($ratings)) {
                    $html .= html_writer::div(html_writer::tag('em', get_string('nonereceived', 'mod_peerwork')));
                } else {
                    $html .= html_writer::tag('ul', implode('', array_map(function($rating) {
                        return html_writer::tag('li', $rating);
                    }, $ratings)));
                }

                if ($displaytotals) {
                    $html .= html_writer::tag('p', get_string('peergradetotal', 'mod_peerwork',
                        $totalmax > 0 ? format_float($totalscore / $totalmax * 100, 2). '%' : '-'));
                }

                $html .= html_writer::end_div();
                return $html;

            }, array_keys($data['criteria']), $data['criteria']);

            $cell2 = new html_table_cell(implode('<hr>', $parts));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        if (isset($data['justifications']) && peerwork_can_students_view_peer_justification($peerwork)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('justifications', 'mod_peerwork'));

            $isanon = $peerwork->justification != MOD_PEERWORK_JUSTIFICATION_VISIBLE_USER;
            $members = (array) (object) $membersgradeable;
            if ($isanon) {
                shuffle($members);
            }

            $html = '';
            foreach ($members as $member) {
                $justification = $data['justifications'][$member->id] ?? null;
                if ($isanon) {
                    if (empty($justification)) {
                        continue;
                    }
                    $html .= html_writer::tag('blockquote', s($justification->justification));
                } else {
                    $content = '';
                    if (empty($justification)) {
                        $content = html_writer::tag('p', html_writer::tag('em', get_string('nonegiven', 'mod_peerwork')));
                    } else {
                        $content = html_writer::tag('blockquote', s($justification->justification));
                    }
                    $html .= html_writer::tag('div', get_string('peersaid', 'mod_peerwork', fullname($member)) . $content);
                }
            }

            $cell2 = new html_table_cell($html);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        return html_writer::table($t);
    }
}
