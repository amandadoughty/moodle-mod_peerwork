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
 * Privacy provider test.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_peerwork;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_peerwork\privacy\provider;

/**
 * Privacy provider testcase.
 *
 * @package    mod_peerwork
 * @copyright  2019 Coventry University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends \advanced_testcase {

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * @return void
     */
    public function test_get_metadata(): void {
        $data = provider::get_metadata(new collection('mod_peerwork'));
        $this->assertCount(5, $data->get_collection());
    }

    /**
     * @return void
     */
    public function test_get_contexts_for_userid(): void {
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p2 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p3 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p1id = $p1->id;
        $p2id = $p2->id;
        $p3id = $p3->id;
        $p1ctx = \context_module::instance($p1->cmid);
        $p2ctx = \context_module::instance($p2->cmid);
        $p3ctx = \context_module::instance($p3->cmid);

        // Validate all empty.
        $this->assertEmpty(provider::get_contexts_for_userid($u1->id)->get_contextids());
        $this->assertEmpty(provider::get_contexts_for_userid($u2->id)->get_contextids());
        $this->assertEmpty(provider::get_contexts_for_userid($u3->id)->get_contextids());
        $this->assertEmpty(provider::get_contexts_for_userid($u4->id)->get_contextids());

        $p1s1 = $pg->create_submission(['peerworkid' => $p1id, 'groupid' => $g1->id, 'userid' => $u1->id]);
        $p2s1 = $pg->create_submission(['peerworkid' => $p2id, 'groupid' => $g1->id, 'gradedby' => $u2->id,
            'releasedby' => $u3->id]);

        // Validate that context is returned for submission related fields.
        $ctxu1 = provider::get_contexts_for_userid($u1->id)->get_contextids();
        $this->assertCount(1, $ctxu1);
        $this->assertContains((string)$p1ctx->id, $ctxu1);
        $ctxu2 = provider::get_contexts_for_userid($u2->id)->get_contextids();
        $this->assertCount(1, $ctxu2);
        $this->assertContains((string)$p2ctx->id, $ctxu2);
        $ctxu3 = provider::get_contexts_for_userid($u3->id)->get_contextids();
        $this->assertCount(1, $ctxu3);
        $this->assertContains((string)$p2ctx->id, $ctxu3);
        $ctxu4 = provider::get_contexts_for_userid($u4->id)->get_contextids();
        $this->assertEmpty($ctxu4);

        // Validate that receiving or giving a peer grade, and justification is found.
        $p3s1 = $pg->create_submission(['peerworkid' => $p3id, 'groupid' => $g1->id]);
        $crit = $pg->create_criterion(['peerworkid' => $p3id, 'scale' => $scale1]);
        $pg->create_peer_grade(['peerworkid' => $p3id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
            'gradefor' => $u1->id, 'gradedby' => $u4->id]);
        $pg->create_justification(['peerworkid' => $p3id, 'groupid' => $g1->id,
            'gradefor' => $u1->id, 'gradedby' => $u4->id]);

        $ctxu1 = provider::get_contexts_for_userid($u1->id)->get_contextids();
        $this->assertCount(2, $ctxu1);
        $this->assertContains((string)$p1ctx->id, $ctxu1);
        $this->assertContains((string)$p3ctx->id, $ctxu1);
        $ctxu2 = provider::get_contexts_for_userid($u2->id)->get_contextids();
        $this->assertCount(1, $ctxu2);
        $this->assertContains((string)$p2ctx->id, $ctxu2);
        $ctxu3 = provider::get_contexts_for_userid($u3->id)->get_contextids();
        $this->assertCount(1, $ctxu3);
        $this->assertContains((string)$p2ctx->id, $ctxu3);
        $ctxu4 = provider::get_contexts_for_userid($u4->id)->get_contextids();
        $this->assertCount(1, $ctxu4);
        $this->assertContains((string)$p3ctx->id, $ctxu4);

        // Validate that receiving a final grade is found.
        $pg->create_grade(['peerworkid' => $p1id, 'userid' => $u2->id, 'submissionid' => $p2s1->id]);

        $ctxu1 = provider::get_contexts_for_userid($u1->id)->get_contextids();
        $this->assertCount(2, $ctxu1);
        $this->assertContains((string)$p1ctx->id, $ctxu1);
        $this->assertContains((string)$p3ctx->id, $ctxu1);
        $ctxu2 = provider::get_contexts_for_userid($u2->id)->get_contextids();
        $this->assertCount(2, $ctxu2);
        $this->assertContains((string)$p1ctx->id, $ctxu2);
        $this->assertContains((string)$p2ctx->id, $ctxu2);
        $ctxu3 = provider::get_contexts_for_userid($u3->id)->get_contextids();
        $this->assertCount(1, $ctxu3);
        $this->assertContains((string)$p2ctx->id, $ctxu3);
        $ctxu4 = provider::get_contexts_for_userid($u4->id)->get_contextids();
        $this->assertCount(1, $ctxu4);
        $this->assertContains((string)$p3ctx->id, $ctxu4);
    }

    public function test_get_users_in_context() {
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $u5 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p2 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p3 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p1id = $p1->id;
        $p2id = $p2->id;
        $p3id = $p3->id;
        $p1ctx = \context_module::instance($p1->cmid);
        $p2ctx = \context_module::instance($p2->cmid);
        $p3ctx = \context_module::instance($p3->cmid);

        // Validate all empty.
        $userlist = new userlist($p1ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
        $userlist = new userlist($p2ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
        $userlist = new userlist($p3ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());

        $p1s1 = $pg->create_submission(['peerworkid' => $p1id, 'groupid' => $g1->id, 'userid' => $u1->id]);
        $p2s1 = $pg->create_submission(['peerworkid' => $p2id, 'groupid' => $g1->id, 'gradedby' => $u2->id,
            'releasedby' => $u3->id]);

        // Validate that users are returned for submission related fields.
        $userlist = new userlist($p1ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist->get_userids());
        $a = $userlist->get_userids();
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $userlist = new userlist($p2ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist->get_userids());
        $this->assertContains((int)$u2->id, $userlist->get_userids());
        $this->assertContains((int)$u3->id, $userlist->get_userids());
        $userlist = new userlist($p3ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist->get_userids());

        // Validate that receiving or giving a peer grade, and justification is found.
        $p3s1 = $pg->create_submission(['peerworkid' => $p3id, 'groupid' => $g1->id]);
        $crit = $pg->create_criterion(['peerworkid' => $p3id, 'scale' => $scale1]);
        $pg->create_peer_grade(['peerworkid' => $p3id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
            'gradefor' => $u1->id, 'gradedby' => $u2->id]);
        $pg->create_justification(['peerworkid' => $p3id, 'groupid' => $g1->id,
            'gradefor' => $u3->id, 'gradedby' => $u4->id]);

        $userlist = new userlist($p1ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist->get_userids());
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $userlist = new userlist($p2ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist->get_userids());
        $this->assertContains((int)$u2->id, $userlist->get_userids());
        $this->assertContains((int)$u3->id, $userlist->get_userids());
        $userlist = new userlist($p3ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(4, $userlist->get_userids());
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $this->assertContains((int)$u2->id, $userlist->get_userids());
        $this->assertContains((int)$u3->id, $userlist->get_userids());
        $this->assertContains((int)$u4->id, $userlist->get_userids());

        // Validate that receiving a final grade is found.
        $pg->create_grade(['peerworkid' => $p2id, 'userid' => $u1->id, 'submissionid' => $p2s1->id]);

        $userlist = new userlist($p1ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist->get_userids());
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $userlist = new userlist($p2ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $this->assertContains((int)$u2->id, $userlist->get_userids());
        $this->assertContains((int)$u3->id, $userlist->get_userids());
        $userlist = new userlist($p3ctx, 'mod_peerwork');
        provider::get_users_in_context($userlist);
        $this->assertCount(4, $userlist->get_userids());
        $this->assertContains((int)$u1->id, $userlist->get_userids());
        $this->assertContains((int)$u2->id, $userlist->get_userids());
        $this->assertContains((int)$u3->id, $userlist->get_userids());
        $this->assertContains((int)$u4->id, $userlist->get_userids());
    }

    /**
     * @return void
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p2 = $dg->create_module('peerwork', (object) ['course' => $c2]);

        // Create the same set of data in two modules.
        foreach ([$p1, $p2] as $p) {
            $sub = $pg->create_submission(['peerworkid' => $p->id, 'groupid' => $g1->id, 'userid' => $u1->id,
                'gradedby' => $u2->id, 'releasedby' => $u3->id]);
            $crit = $pg->create_criterion(['peerworkid' => $p->id, 'scale' => $scale1]);

            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u1->id]);

            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u1->id]);

            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u1->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u2->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u3->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u4->id, 'submissionid' => $sub->id]);
        }

        // Confirm data.
        foreach ([$p1, $p2] as $p) {
            $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
        }

        provider::delete_data_for_all_users_in_context(\context_module::instance($p1->cmid));

        // Confirm deletion.
        $p = $p1;
        $this->assertTrue($DB->record_exists('peerwork', ['id' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_criteria', ['peerworkid' => $p->id]));
        $this->assertFalse($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));

        // Confirm the other module was not affected.
        $p = $p2;
        $this->assertTrue($DB->record_exists('peerwork', ['id' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_criteria', ['peerworkid' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
    }

    /**
     * @return void
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p2 = $dg->create_module('peerwork', (object) ['course' => $c2]);

        // Create the same set of data in two modules.
        foreach ([$p1, $p2] as $p) {
            $sub = $pg->create_submission(['peerworkid' => $p->id, 'groupid' => $g1->id, 'userid' => $u1->id,
                'gradedby' => $u2->id, 'releasedby' => $u3->id]);
            $crit = $pg->create_criterion(['peerworkid' => $p->id, 'scale' => $scale1]);

            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u2->id]);

            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u2->id]);

            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u1->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u2->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u3->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u4->id, 'submissionid' => $sub->id]);
        }

        // Confirm data.
        foreach ([$p1, $p2] as $p) {
            $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
        }

        $contextlist = new approved_contextlist($u1, 'mod_peerwork', [
            \context_module::instance($p1->cmid)->id
        ]);
        provider::delete_data_for_user($contextlist);

        // Confirm deletion, and not deletion.
        $p = $p1;
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));

        // Those remain untouched.
        $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));

        // Confirm the other module was not affected.
        $p = $p2;
        $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
    }

    /**
     * @return void
     */
    public function test_delete_data_for_users(): void {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);
        $p2 = $dg->create_module('peerwork', (object) ['course' => $c2]);

        // Create the same set of data in two modules.
        foreach ([$p1, $p2] as $p) {
            $sub = $pg->create_submission(['peerworkid' => $p->id, 'groupid' => $g1->id, 'userid' => $u1->id,
                'gradedby' => $u2->id, 'releasedby' => $u3->id]);
            $crit = $pg->create_criterion(['peerworkid' => $p->id, 'scale' => $scale1]);

            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u2->id]);
            $pg->create_peer_grade(['peerworkid' => $p->id, 'criteriaid' => $crit->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u4->id]);

            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u4->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u4->id, 'gradedby' => $u1->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u1->id, 'gradedby' => $u2->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u2->id]);
            $pg->create_justification(['peerworkid' => $p->id, 'groupid' => $g1->id,
                'gradefor' => $u3->id, 'gradedby' => $u4->id]);

            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u1->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u2->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u3->id, 'submissionid' => $sub->id]);
            $pg->create_grade(['peerworkid' => $p->id, 'userid' => $u4->id, 'submissionid' => $sub->id]);
        }

        // Confirm data.
        foreach ([$p1, $p2] as $p) {
            $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
            $this->assertEquals(2, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
            $this->assertEquals(2, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
            $this->assertEquals(2, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
            $this->assertEquals(2, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
            $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
        }

        $contextlist = new approved_userlist(\context_module::instance($p1->cmid), 'mod_peerwork', [$u1->id, $u2->id]);
        provider::delete_data_for_users($contextlist);

        // Confirm deletion, and not deletion.
        $p = $p1;
        $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertEquals(1, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertEquals(1, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertFalse($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertEquals(1, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertEquals(1, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertFalse($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
        $this->assertFalse($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));

        // Confirm the other module was not affected.
        $p = $p2;
        $this->assertTrue($DB->record_exists('peerwork_submission', ['peerworkid' => $p->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u2->id]));
        $this->assertEquals(2, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u1->id]));
        $this->assertEquals(2, $DB->count_records('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_peers', ['peerwork' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u2->id]));
        $this->assertEquals(2, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradedby' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u1->id]));
        $this->assertEquals(2, $DB->count_records('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_justification', ['peerworkid' => $p->id, 'gradefor' => $u4->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u3->id]));
        $this->assertTrue($DB->record_exists('peerwork_grades', ['peerworkid' => $p->id, 'userid' => $u4->id]));
    }

    /**
     * @return void
     */
    public function test_export_data_for_user(): void {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_peerwork');

        $c1 = $dg->create_course();
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $g2 = $dg->create_group(['courseid' => $c1->id]);
        $scale1 = $dg->create_scale();
        $scale2 = $dg->create_scale(['scale' => 'Bad,Good']);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u9 = $dg->create_user();

        $p1 = $dg->create_module('peerwork', (object) ['course' => $c1]);

        $sub = $pg->create_submission(['peerworkid' => $p1->id, 'groupid' => $g1->id, 'userid' => $u1->id,
                'gradedby' => $u9->id, 'releasedby' => $u9->id, 'timegraded' => 123456789,
                'released' => 223456789, 'timecreated' => 88872733, 'timemodified' => 188872733,
                'feedbacktext' => 'You rock', 'feedbackformat' => FORMAT_PLAIN, 'grade' => 88]);
        $sub2 = $pg->create_submission(['peerworkid' => $p1->id, 'groupid' => $g2->id, 'userid' => $u3->id,
                'gradedby' => $u9->id, 'timegraded' => 111111, 'timecreated' => 222222, 'timemodified' => 333333,
                'feedbacktext' => 'You rule', 'feedbackformat' => FORMAT_PLAIN, 'grade' => 77]);
        $crit1 = $pg->create_criterion(['peerworkid' => $p1->id, 'scale' => $scale1, 'description' => 'X?']);
        $crit2 = $pg->create_criterion(['peerworkid' => $p1->id, 'scale' => $scale2, 'description' => 'Y?']);

        $g1 = $pg->create_peer_grade([
                'peerworkid' => $p1->id,
                'criteriaid' => $crit1->id,
                'groupid' => $g1->id,
                'gradefor' => $u1->id,
                'gradedby' => $u2->id,
                'grade' => 1,
                'timecreated' => 1234986,
                'peergrade' => 2,
                'overriddenby' => $u9->id,
                'comments' => 'some words',
                'timeoverridden' => 1234986
        ]);
        $g2 = $pg->create_peer_grade(['peerworkid' => $p1->id, 'criteriaid' => $crit1->id, 'groupid' => $g1->id,
            'gradefor' => $u2->id, 'gradedby' => $u1->id, 'grade' => 4, 'timecreated' => 2234986]);
        $g3 = $pg->create_peer_grade(['peerworkid' => $p1->id, 'criteriaid' => $crit2->id, 'groupid' => $g1->id,
            'gradefor' => $u1->id, 'gradedby' => $u2->id, 'grade' => 1, 'timecreated' => 3234986]);
        $g4 = $pg->create_peer_grade(['peerworkid' => $p1->id, 'criteriaid' => $crit2->id, 'groupid' => $g1->id,
            'gradefor' => $u2->id, 'gradedby' => $u1->id, 'grade' => 0, 'timecreated' => 4234986]);

        $pg->create_justification(['peerworkid' => $p1->id, 'groupid' => $g1->id,
            'gradefor' => $u1->id, 'gradedby' => $u2->id, 'justification' => 'Abc']);
        $pg->create_justification(['peerworkid' => $p1->id, 'groupid' => $g1->id,
            'gradefor' => $u2->id, 'gradedby' => $u1->id, 'justification' => 'Def']);

        $pg->create_grade(['peerworkid' => $p1->id, 'userid' => $u1->id, 'submissionid' => $sub->id, 'grade' => 54.32,
            'revisedgrade' => 60, 'prelimgrade' => 1.34]);
        $pg->create_grade(['peerworkid' => $p1->id, 'userid' => $u2->id, 'submissionid' => $sub->id, 'grade' => 12.30]);
        $pg->create_grade(['peerworkid' => $p1->id, 'userid' => $u3->id, 'submissionid' => $sub2->id, 'grade' => 13.37]);

        $ctx = \context_module::instance($p1->cmid);
        $contextlist = new approved_contextlist($u1, 'mod_peerwork', [$ctx->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($ctx);
        $grade = $writer->get_data([get_string('privacy:path:grade', 'mod_peerwork')]);
        $submissions = $writer->get_data([get_string('privacy:path:submission', 'mod_peerwork')]);
        $peergrades = $writer->get_data([get_string('privacy:path:peergrades', 'mod_peerwork')]);

        $this->assertEquals(88, $grade->group_grade);
        $this->assertTrue(strpos($grade->group_feedback, $sub->feedbacktext) > -1);
        $this->assertEquals(transform::datetime($sub->timecreated), $grade->group_submission_created_on);
        $this->assertEquals(transform::datetime($sub->timemodified), $grade->group_submission_updated_on);
        $this->assertEquals(1.34, $grade->your_calculated_grade);
        $this->assertEquals(54.32, $grade->your_grade);
        $this->assertEquals(60, $grade->your_revised_grade);
        $this->assertEquals(transform::datetime($sub->timegraded), $grade->time_graded);

        $this->assertCount(1, $submissions->submissions);
        $submission = array_pop($submissions->submissions);
        $this->assertEquals(transform::yesno(true), $submission->submitted_or_updated_by_you);
        $this->assertEquals(transform::yesno(false), $submission->graded_by_you);
        $this->assertEquals(transform::yesno(false), $submission->grade_released_by_you);
        $this->assertEquals(transform::datetime($sub->timecreated), $submission->submitted_on);
        $this->assertEquals(transform::datetime($sub->timemodified), $submission->updated_on);
        $this->assertEquals(transform::datetime($sub->timegraded), $submission->graded_on);
        $this->assertEquals(transform::datetime($sub->released), $submission->released_on);

        $sc1 = \grade_scale::fetch(['id' => $scale1->id]);
        $sc1->load_items();
        $sc2 = \grade_scale::fetch(['id' => $scale2->id]);
        $sc2->load_items();

        // The order matters.
        $this->assertCount(4, $peergrades->grades);

        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals(transform::yesno(true), $peergrade->peer_graded_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_grading_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_override_is_you);
        $this->assertEquals($sc1->scale_items[1], $peergrade->grade_given);
        $this->assertEquals('B', $peergrade->grade_given);
        $this->assertEquals(null, $peergrade->feedback_given);
        $this->assertEquals('Abc', $peergrade->justification_given);
        $this->assertEquals(transform::datetime($g1->timecreated), $peergrade->time_graded);
        $this->assertEquals('X?', strip_tags($peergrade->criterion));
        $this->assertEquals($sc1->scale_items[2], $peergrade->peergrade_given);
        $this->assertEquals('C', $peergrade->peergrade_given);
        $this->assertEquals('some words', $peergrade->comments_given);
        $this->assertEquals(transform::datetime($g1->timeoverridden), $peergrade->time_grade_overridden);

        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_graded_is_you);
        $this->assertEquals(transform::yesno(true), $peergrade->peer_grading_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_override_is_you);
        $this->assertEquals($sc1->scale_items[4], $peergrade->grade_given);
        $this->assertEquals('F', $peergrade->grade_given);
        $this->assertEquals(null, $peergrade->feedback_given);
        $this->assertEquals('Def', $peergrade->justification_given);
        $this->assertEquals(transform::datetime($g2->timecreated), $peergrade->time_graded);
        $this->assertEquals('X?', strip_tags($peergrade->criterion));

        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals(transform::yesno(true), $peergrade->peer_graded_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_grading_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_override_is_you);
        $this->assertEquals($sc2->scale_items[1], $peergrade->grade_given);
        $this->assertEquals('Good', $peergrade->grade_given);
        $this->assertEquals(null, $peergrade->feedback_given);
        $this->assertEquals('Abc', $peergrade->justification_given);
        $this->assertEquals(transform::datetime($g3->timecreated), $peergrade->time_graded);
        $this->assertEquals('Y?', strip_tags($peergrade->criterion));

        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_graded_is_you);
        $this->assertEquals(transform::yesno(true), $peergrade->peer_grading_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_override_is_you);
        $this->assertEquals($sc2->scale_items[0], $peergrade->grade_given);
        $this->assertEquals('Bad', $peergrade->grade_given);
        $this->assertEquals(null, $peergrade->feedback_given);
        $this->assertEquals('Def', $peergrade->justification_given);
        $this->assertEquals(transform::datetime($g4->timecreated), $peergrade->time_graded);
        $this->assertEquals('Y?', strip_tags($peergrade->criterion));

        // Now test again with the justification specifically hidden.
        writer::reset();
        $p1->justification = MOD_PEERWORK_JUSTIFICATION_HIDDEN;
        $DB->update_record('peerwork', $p1);

        $ctx = \context_module::instance($p1->cmid);
        $contextlist = new approved_contextlist($u1, 'mod_peerwork', [$ctx->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($ctx);
        $peergrades = $writer->get_data([get_string('privacy:path:peergrades', 'mod_peerwork')]);
        $this->assertCount(4, $peergrades->grades);
        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals('', $peergrade->justification_given);
        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals('', $peergrade->justification_given);
        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals('', $peergrade->justification_given);
        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals('', $peergrade->justification_given);

        // Now test again with the teacher.
        writer::reset();
        $ctx = \context_module::instance($p1->cmid);
        $contextlist = new approved_contextlist($u9, 'mod_peerwork', [$ctx->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($ctx);
        $grade = $writer->get_data([get_string('privacy:path:grade', 'mod_peerwork')]);
        $submissions = $writer->get_data([get_string('privacy:path:submission', 'mod_peerwork')]);
        $peergrades = $writer->get_data([get_string('privacy:path:peergrades', 'mod_peerwork')]);

        $this->assertEmpty($grade);

        $this->assertCount(2, $submissions->submissions);
        $submission = array_shift($submissions->submissions);
        $this->assertEquals(transform::yesno(false), $submission->submitted_or_updated_by_you);
        $this->assertEquals(transform::yesno(true), $submission->graded_by_you);
        $this->assertEquals(transform::yesno(true), $submission->grade_released_by_you);
        $this->assertEquals(transform::datetime($sub->timecreated), $submission->submitted_on);
        $this->assertEquals(transform::datetime($sub->timemodified), $submission->updated_on);
        $this->assertEquals(transform::datetime($sub->timegraded), $submission->graded_on);
        $this->assertEquals(transform::datetime($sub->released), $submission->released_on);
        $submission = array_shift($submissions->submissions);
        $this->assertEquals(transform::yesno(false), $submission->submitted_or_updated_by_you);
        $this->assertEquals(transform::yesno(true), $submission->graded_by_you);
        $this->assertEquals(transform::yesno(false), $submission->grade_released_by_you);
        $this->assertEquals(transform::datetime($sub2->timecreated), $submission->submitted_on);
        $this->assertEquals(transform::datetime($sub2->timemodified), $submission->updated_on);
        $this->assertEquals(transform::datetime($sub2->timegraded), $submission->graded_on);
        $this->assertEquals('-', $submission->released_on);

        $peergrade = array_shift($peergrades->grades);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_graded_is_you);
        $this->assertEquals(transform::yesno(false), $peergrade->peer_grading_is_you);
        $this->assertEquals(transform::yesno(true), $peergrade->peer_override_is_you);
        $this->assertEquals($sc1->scale_items[2], $peergrade->peergrade_given);
        $this->assertEquals('C', $peergrade->peergrade_given);
        $this->assertEquals('some words', $peergrade->comments_given);
        $this->assertEquals(transform::datetime($g1->timeoverridden), $peergrade->time_grade_overridden);
    }
}
