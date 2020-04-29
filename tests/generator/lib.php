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
 * Data generator for the competvetsuivi elements
 *
 * @package     local_competvetsuivi
 * @category    generator
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Compet vet suivi generator
 *
 * @package     local_competvetsuivi
 * @category    generator
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_competvetsuivi_generator extends testing_module_generator {

    protected function create_and_insert_data($record, $defaultvalue, $tablename) {
        global $DB;
        $record = (array) $record;
        if (!empty($record['ue'])) {
            $record['ueid'] = $DB->get_field('cvs_matrix_ue', 'id', array('shortname' => $record['ue']));
        }
        if (!empty($record['matrix'])) {
            $record['matrixid'] = $DB->get_field('cvs_matrix', 'id', array('shortname' => $record['matrix']));
        }
        if (!empty($record['comp'])) {
            $record['compid'] = $DB->get_field('cvs_matrix_comp', 'id', array('shortname' => $record['comp']));
        }
        if (!empty($record['cohort'])) {
            $record['cohortid'] = $DB->get_field('cohort', 'id', array('idnumber' => $record['cohort']));
        }
        $record = array_merge(
            $defaultvalue,
            $record
        );
        $returnedid = $DB->insert_record($tablename, $record, true, true);
        if ($tablename == 'cvs_matrix_comp') {
            // We need to update the path.
            $allpathitem = explode('/', $record['path']);
            if ($allpathitem) {
                array_shift($allpathitem); // Remove the first empty entry.
            }
            list($sqlwherein, $paramsin) = $DB->get_in_or_equal($allpathitem);
            $martrixmatches =
                $DB->get_records_sql_menu('SELECT id, shortname FROM {cvs_matrix_comp} WHERE shortname ' . $sqlwherein,
                    $paramsin);
            $matchedcompids = array_combine($allpathitem, array_flip($martrixmatches));
            $record['path'] = '/' . join('/', $matchedcompids);
            $record['id'] = $returnedid;
            $DB->update_record($tablename, $record);
        }
        return $returnedid;
    }

    public function create_matrix($record = null, array $options = null) {
        $defaultsettings = array(
            'fullname' => "",
            'shortname' => "",
            'timemodified' => time(),
            'hash' => sha1(random_string(255)),
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_matrix');
    }

    public function create_matrix_ue($record = null, array $options = null) {
        $defaultsettings = array(
            'fullname' => "",
            'shortname' => "",
            'matrixid' => 0,
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_matrix_ue');
    }

    public function create_matrix_comp($record = null, array $options = null) {
        $defaultsettings = array(
            'fullname' => "",
            'shortname' => "",
            'description' => "",
            'descriptionformat' => FORMAT_HTML,
            'path' => "",
            'matrixid' => 0,
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_matrix_comp');
    }

    public function create_matrix_comp_ue($record = null, array $options = null) {
        global $DB;
        $defaultsettings = array(
            'ueid' => 0,
            'compid' => 0,
            'type' => local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE,
            'value' => 0,
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_matrix_comp_ue');
    }

    /**
     * Specific function here to optimise import time for tests
     * We use bulk insert here whilst keeping on modifying the data appropriately
     */

    public function create_matrix_comp_ue_bulk($records = null, array $options = null) {
        global $DB;
        $defaultsettings = array(
            'ueid' => 0,
            'compid' => 0,
            'type' => local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE,
            'value' => 0,
        );

        $uematcher = [];
        $compmatcher = [];

        $ueidnames = array_map(
            function($r) {
                return key_exists('ue', $r) ? $r['ue'] : "";
            },
            $records
        );
        $compidnames = array_map(
            function($r) {
                return key_exists('comp', $r) ? $r['comp'] : "";
            },
            $records
        );
        $compidnames = array_filter(array_unique($compidnames), function($r) {
            return $r;
        });
        $ueidnames = array_filter(array_unique($ueidnames), function($r) {
            return $r;
        });

        list($sqlin, $paramin) = $DB->get_in_or_equal($compidnames);
        $compmatcher = $DB->get_records_select_menu("cvs_matrix_comp", "shortname " . $sqlin, $paramin, '', "id, shortname");
        $compmatcher = array_flip($compmatcher);
        list($sqlin, $paramin) = $DB->get_in_or_equal($ueidnames);
        $uematcher = $DB->get_records_select_menu("cvs_matrix_ue", "shortname " . $sqlin, $paramin, '', "id, shortname");
        $uematcher = array_flip($uematcher);

        foreach ($records as &$record) { // Warning: Modifying the content.
            if (!empty($record['ue'])) {
                $record['ueid'] = $uematcher[$record['ue']];
            }
            if (!empty($record['comp'])) {
                $record['compid'] = $compmatcher[$record['comp']];
            }
        }
        $DB->insert_records('cvs_matrix_comp_ue', $records, true, true);
    }

    public function create_userdata($record = null, array $options = null) {
        global $DB;
        $defaultsettings = array(
            'useremail' => "",
            'userdata' => "",
            'lastseenunit' => ""
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_userdata');
    }

    public function create_matrix_cohorts($record = null, array $options = null) {
        $defaultsettings = array(
            'matrixid' => 0,
            'cohortid' => 0,
        );
        return $this->create_and_insert_data($record, $defaultsettings, 'cvs_matrix_cohorts');
    }
}
