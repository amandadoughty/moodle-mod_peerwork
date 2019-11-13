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


    return true;
}
