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
 * File containing common function for tests
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Load data from a series of JSON representing the table data
 * @param $fixturepath
 * @throws coding_exception
 */
function load_data_from_json_fixtures($fixturepath) {
    $generator = testing_util::get_data_generator()->get_plugin_generator('local_competvetsuivi');
    $tables =  array('matrix','matrix_cohorts','matrix_ue','matrix_comp', 'matrix_comp_ue', 'userdata');
    foreach($tables as $tablename) {
        $filename = $fixturepath . '/' . $tablename.'.json';
        if (file_exists($filename)) {
            $generatorfn = "create_$tablename";
            $records = json_decode(file_get_contents($filename), true);
            if ($tablename == 'matrix_comp_ue') {
                $generator->create_matrix_comp_ue_bulk($records); // Create the entities in bulk mode
            } else {
                foreach ($records as $r) {
                    $generator->$generatorfn($r); // Create the entity
                }
            }
        }
    }
}


/*
 * Generate the comp_ue table:
 * SELECT ue.shortname AS ue, comp.shortname AS comp, cue.type as type, cue.value as value
 * FROM mdl_cvs_matrix_comp_ue AS cue
 * LEFT JOIN mdl_cvs_matrix_ue ue ON cue.ueid = ue.id
 * LEFT JOIN mdl_cvs_matrix_comp comp ON cue.compid = comp.id
 * WHERE comp.matrixid = 4 AND ue.matrixid = 4 AND comp.shortname like '%COPREV%'
 *
 *
 *
 */