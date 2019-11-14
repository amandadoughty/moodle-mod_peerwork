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

/**
 * Execute peerwork upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_peerwork_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019111301) {

        // Define field completiongradedpeers to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('completiongradedpeers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'submissiongroupingid');

        // Conditionally launch add field completiongradedpeers.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111301, 'peerwork');
    }

    if ($oldversion < 2019111302) {

        // Define field submissiongroupingid to be dropped from peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('submissiongroupingid');

        // Conditionally launch drop field submissiongroupingid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111302, 'peerwork');
    }

    if ($oldversion < 2019111303) {

        // Define field calculationtype to be dropped from peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('calculationtype');

        // Conditionally launch drop field calculationtype.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111303, 'peerwork');
    }

    if ($oldversion < 2019111304) {

        // Define table peerwork_presets to be dropped.
        $table = new xmldb_table('peerwork_presets');

        // Conditionally launch drop table for peerwork_presets.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111304, 'peerwork');
    }

    if ($oldversion < 2019111400) {

        // Define field sort to be dropped from peerwork_criteria.
        $table = new xmldb_table('peerwork_criteria');
        $field = new xmldb_field('sort');

        // Conditionally launch drop field sort.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111400, 'peerwork');
    }

    if ($oldversion < 2019111401) {

        // Define field sortorder to be added to peerwork_criteria.
        $table = new xmldb_table('peerwork_criteria');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'weight');

        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111401, 'peerwork');
    }

    if ($oldversion < 2019111402) {

        // Define field justification to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('justification', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'standard_deviation');

        // Conditionally launch add field justification.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111402, 'peerwork');
    }

    if ($oldversion < 2019111403) {

        // Rename field sort on table peerwork_peers to criteriaid.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('sort', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'peerwork');

        // Launch rename field sort.
        $dbman->rename_field($table, $field, 'criteriaid');

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111403, 'peerwork');
    }

    if ($oldversion < 2019111404) {

        // Define index grade (unique) to be dropped form peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $index = new xmldb_index('grade', XMLDB_INDEX_UNIQUE, ['peerwork', 'gradedby', 'gradefor']);

        // Conditionally launch drop index grade.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111404, 'peerwork');
    }

    if ($oldversion < 2019111405) {

        // Define index grade (unique) to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $index = new xmldb_index('grade', XMLDB_INDEX_UNIQUE, ['peerwork', 'criteriaid', 'gradedby', 'gradefor']);

        // Conditionally launch add index grade.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019111405, 'peerwork');
    }


    return true;
}
