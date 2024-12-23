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
 * Upgrade paths.
 *
 * @package    mod_peerwork
 * @copyright  2013 LEARNING TECHNOLOGY SERVICES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute peerwork upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_peerwork_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019112800) {

        // Define field peergradesvisibility to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field(
            'peergradesvisibility',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'completiongradedpeers'
        );

        // Conditionally launch add field peergradesvisibility.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019112800, 'peerwork');
    }

    if ($oldversion < 2019113000) {

        // Define field justificationmaxlength to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field(
            'justificationmaxlength',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'justification'
        );

        // Conditionally launch add field justificationmaxlength.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019113000, 'peerwork');
    }

    if ($oldversion < 2019121900) {

        // Define field displaypeergradestotals to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field(
            'displaypeergradestotals',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'peergradesvisibility'
        );

        // Conditionally launch add field displaypeergradestotals.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019121900, 'peerwork');
    }

    if ($oldversion < 2019121902) {

        // Define field prelimgrade to be added to peerwork_grades.
        $table = new xmldb_table('peerwork_grades');
        $field = new xmldb_field(
            'prelimgrade',
            XMLDB_TYPE_NUMBER,
            '10, 5',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'userid'
        );

        // Conditionally launch add field prelimgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019121902, 'peerwork');
    }

    if ($oldversion < 2019122600) {

        // Define field releasednotified to be added to peerwork_submission.
        $table = new xmldb_table('peerwork_submission');
        $field = new xmldb_field(
            'releasednotified',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'releasedby'
        );

        // Conditionally launch add field releasednotified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019122600, 'peerwork');
    }

    if ($oldversion < 2020012400) {

        // Define field score to be added to peerwork_grades.
        $table = new xmldb_table('peerwork_grades');
        $field = new xmldb_field('score', XMLDB_TYPE_NUMBER, '10, 8', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field score.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020012400, 'peerwork');
    }

    if ($oldversion < 2020030900) {

        // Define field timemodified to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020030900, 'peerwork');
    }

    if ($oldversion < 2020030901) {

        // Define field lockediting to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('lockediting', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'displaypeergradestotals');

        // Conditionally launch add field lockediting.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020030901, 'peerwork');
    }

    if ($oldversion < 2020030902) {

        // Define field locked to be added to peerwork_submission.
        $table = new xmldb_table('peerwork_submission');
        $field = new xmldb_field('locked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'releasednotified');

        // Conditionally launch add field locked.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020030902, 'peerwork');
    }

    if ($oldversion < 2020030903) {

        // Define field locked to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('locked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'feedback');

        // Conditionally launch add field locked.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020030903, 'peerwork');
    }

    if ($oldversion < 2020051300) {

        // Define field criteriaid to be added to peerwork_justification.
        $table = new xmldb_table('peerwork_justification');
        $field = new xmldb_field(
            'criteriaid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'gradefor'
        );

        // Conditionally launch add field prelimgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add criteriaid to index.
        $oldindex = new xmldb_index(
            'justif',
            XMLDB_KEY_UNIQUE,
            ['peerworkid', 'gradedby', 'gradefor']
        );
        $index = new xmldb_index(
            'justif',
            XMLDB_KEY_UNIQUE,
            ['peerworkid', 'gradedby', 'gradefor', 'criteriaid']
        );

        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }

        if (!$dbman->index_exists($table, $oldindex)) {
            $dbman->add_index($table, $index);
        }

        // Define field justificationtype to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field(
            'justificationtype',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'justification'
        );

        // Conditionally launch add field prelimgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020051300, 'peerwork');
    }

    if ($oldversion < 2020052500) {
        // Define field calculator to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('calculator', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, 'webpa', 'lockediting');

        // Conditionally launch add field calculator.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define new peerwork_plugin_config table.
        $table = new xmldb_table('peerwork_plugin_config');
        $table->add_field(
            'id',
            XMLDB_TYPE_INTEGER,
            10, null,
            XMLDB_NOTNULL,
            XMLDB_SEQUENCE,
            null
        );
        $table->add_field(
            'peerwork',
            XMLDB_TYPE_INTEGER,
            10,
            null,
            XMLDB_NOTNULL,
            null,
            null
        );
        $table->add_field(
            'plugin',
            XMLDB_TYPE_CHAR,
            28,
            null,
            XMLDB_NOTNULL,
            null,
            null
        );
        $table->add_field(
            'subtype',
            XMLDB_TYPE_CHAR,
            28,
            null,
            XMLDB_NOTNULL,
            null,
            null
        );
        $table->add_field(
            'name',
            XMLDB_TYPE_CHAR,
            28,
            null,
            XMLDB_NOTNULL,
            null,
            null
        );
        $table->add_field(
            'value',
            XMLDB_TYPE_TEXT,
            null,
            null,
            XMLDB_NOTNULL,
            null,
            null
        );

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('peerwork', XMLDB_KEY_FOREIGN, ['peerwork'], 'peerwork', ['id']);
        $table->add_index('plugin', XMLDB_INDEX_NOTUNIQUE, ['plugin']);
        $table->add_index('subtype', XMLDB_INDEX_NOTUNIQUE, ['subtype']);
        $table->add_index('name', XMLDB_INDEX_NOTUNIQUE, ['name']);

        // Conditionally launch create table for peerwork_plugin_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020052500, 'peerwork');
    }

    if ($oldversion < 2020090701) {

        // Define field peergrade to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('peergrade', XMLDB_TYPE_INTEGER, '5', null, null, null, '0', 'timemodified');

        // Conditionally launch add field peergrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Copy existing 'grade' to 'peergrade'.
        $peerworkpeers = $DB->get_records('peerwork_peers');
        $newfield = 'peergrade';

        foreach ($peerworkpeers as $id => $peerworkpeer) {
            $newvalue = $peerworkpeer->grade;
            $DB->set_field('peerwork_peers', $newfield, $newvalue, ['id' => $id]);
        }

        // Define field overriddenby to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('overriddenby', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'peergrade');

        // Conditionally launch add field overriddenby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field comments to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null, 'overriddenby');

        // Conditionally launch add field comments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timeoverridden to be added to peerwork_peers.
        $table = new xmldb_table('peerwork_peers');
        $field = new xmldb_field('timeoverridden', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'comments');

        // Conditionally launch add field timeoverridden.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2020090701, 'peerwork');
    }

    if ($oldversion < 2021052402) {

        // Allow grades with decimal places.
        $table = new xmldb_table('peerwork_submission');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'userid');
        $dbman->change_field_type($table, $field);

        // Add back grouping setting.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('pwgroupingid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Now add existing groupingid from course_modules.
        $sql = "SELECT cm.instance, cm.groupingid
                FROM {course_modules} cm
                INNER JOIN {modules} m
                ON m.id = cm.module
                AND m.name = 'peerwork'";
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $object = new stdClass();
            $object->id = $record->instance;
            $object->pwgroupingid = $record->groupingid;
            $DB->update_record('peerwork', $object);
        }

        $recordset->close();

        // Allow null calculator.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('calculator', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'lockediting');
        $dbman->change_field_type($table, $field);

        upgrade_mod_savepoint(true, 2021052402, 'peerwork');
    }

    return true;
}
