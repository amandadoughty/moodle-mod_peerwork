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

defined('MOODLE_INTERNAL') || die();

/**
 * Execute peerassessment upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_peerassessment_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014072101) {

        $table = new xmldb_table('peerassessment');
        $field = new xmldb_field('treat0asgrade', XMLDB_TYPE_INTEGER, '2', null,
            XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014072101, 'peerassessment');
    }

    if ($oldversion < 2017030604) {

        $table = new xmldb_table('peerassessment');
        $field = new xmldb_field('calculationtype', XMLDB_TYPE_CHAR, '10', null,
            XMLDB_NOTNULL, null, 'simple');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('standard_deviation', XMLDB_TYPE_FLOAT, '5,2', null,
            XMLDB_NOTNULL, null, '1.15');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('moderation', XMLDB_TYPE_FLOAT, '5,2', null,
            XMLDB_NOTNULL, null, '2');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('multiplyby', XMLDB_TYPE_INTEGER, '2', null,
            XMLDB_NOTNULL, null, '4');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017030604, 'peerassessment');
    }

    if ($oldversion < 2017030605) {

        $table = new xmldb_table('peerassessment');
        $field = new xmldb_field('submissiongroupingid', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017030605, 'peerassessment');

    }

    if ($oldversion < 2017030608) {

        $table = new xmldb_table('peerassessment_submission');
        $field = new xmldb_field('groupaverage', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('individualaverage', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('finalgrade', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017030608, 'peerassessment');

    }


    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
