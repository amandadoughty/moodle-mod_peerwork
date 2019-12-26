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

    if ($oldversion < 2019112800) {

        // Define field peergradesvisibility to be added to peerwork.
        $table = new xmldb_table('peerwork');
        $field = new xmldb_field('peergradesvisibility', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completiongradedpeers');

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
        $field = new xmldb_field('justificationmaxlength', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'justification');

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
        $field = new xmldb_field('displaypeergradestotals', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'peergradesvisibility');

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
        $field = new xmldb_field('prelimgrade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'userid');

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
        $field = new xmldb_field('releasednotified', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'releasedby');

        // Conditionally launch add field releasednotified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerwork savepoint reached.
        upgrade_mod_savepoint(true, 2019122600, 'peerwork');
    }

    return true;
}
