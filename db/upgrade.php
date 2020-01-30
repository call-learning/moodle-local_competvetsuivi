<?php
// This file is part of Moodle - http://moodle.org/
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
 * Plugin upgrade steps are defined here.
 *
 * @package     local_competvetsuivi
 * @category    upgrade
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Execute local_competvetsuivi upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_competvetsuivi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor

    if ($oldversion < 2019080109) {

        // Define table cvs_matrix_cohorts to be created.
        $table = new xmldb_table('cvs_matrix_cohorts');

        // Adding fields to table cvs_matrix_cohorts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('matrixid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table cvs_matrix_cohorts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('matrixid_fk', XMLDB_KEY_FOREIGN, ['matrixid'], 'cvs_matrix', ['id']);
        $table->add_key('cohortid_fk', XMLDB_KEY_FOREIGN, ['cohortid'], 'cohort', ['id']);
        $table->add_key('matrix_cohort_ux', XMLDB_KEY_UNIQUE, ['matrixid', 'cohortid']);

        // Conditionally launch create table for cvs_matrix_cohorts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Competvetsuivi savepoint reached.
        upgrade_plugin_savepoint(true, 2019080109, 'local', 'competvetsuivi');
    }

    if ($oldversion < 2019080110) {
        $table = new xmldb_table('cvs_userdata');
        $field = new xmldb_field('lastseenunit',
                XMLDB_TYPE_TEXT,
                '255',
                null,
                null,
                null,
                null,
                'userdata');

        // Conditionally launch add field currentueid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019080110, 'local', 'competvetsuivi');
    }

    if ($oldversion < 2019080214) {
        set_config('cvsquestionbankdefaultcategoryname','Auto-evaluation_competences','local_competvetsuivi');
        upgrade_plugin_savepoint(true, 2019080214, 'local', 'competvetsuivi');
    }
    return true;
}
