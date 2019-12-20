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
 * File containing tests for utils_test.
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// For installation and usage of PHPUnit within Moodle please read:
// https://docs.moodle.org/dev/PHPUnit
//
// Documentation for writing PHPUnit tests for Moodle can be found here:
// https://docs.moodle.org/dev/PHPUnit_integration
// https://docs.moodle.org/dev/Writing_PHPUnit_tests
//
// The official PHPUnit homepage is at:
// https://phpunit.de

include_once('lib.php');

use local_competvetsuivi\utils;

/**
 * The utils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils_tests extends advanced_testcase {
    protected $user, $cohort1, $cohort2;

    /**
     * Load the model data
     *
     * @throws coding_exception
     */
    public function setup() {
        global $CFG;
        $this->user = static::getDataGenerator()->create_user();
        $this->cohort1 = static::getDataGenerator()->create_cohort(array('idnumber' => 'COHORT1'));
        $this->cohort2 = static::getDataGenerator()->create_cohort(array('idnumber' => 'COHORT2'));
        load_data_from_json_fixtures($CFG->dirroot . '/local/competvetsuivi/tests/fixtures/basic');
    }

    public function test_get_matrixid_for_user() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = \local_competvetsuivi\utils::get_matrixid_for_user($this->user->id);
        $this->assertFalse($matrixid);

        cohort_add_member($this->cohort1->id, $this->user->id);
        cohort_add_member($this->cohort2->id, $this->user->id);
        $matrix1id = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrixid = utils::get_matrixid_for_user($this->user->id);
        $this->assertEquals($matrix1id, $matrixid);
    }

    public function test_assign_matrix_cohort() {
        global $DB;
        $this->resetAfterTest();
        $matrix1id = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $cohortid = $DB->get_field('cohort', 'id', array('idnumber' => 'COHORT1'));
        utils::assign_matrix_cohort($matrix1id, $cohortid);
        $this->assertCount(1, $DB->get_records('cvs_matrix_cohorts',
                array('matrixid' => $matrix1id, 'cohortid' => $cohortid)));
    }

    public function test_get_possible_vs_actual_values() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $comp = $DB->get_record('cvs_matrix_comp', array('shortname' => 'COPREV.1.1'));
        $userdata = local_competvetsuivi\userdata::get_user_data("Etudiant-145@ecole.fr");
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $comp, $userdata);
        $uc55vals = [];
        foreach ($possiblevsactual as $type => $vals) {
            $uc55vals[$type] = array_values(array_filter($vals, function($u) {
                return $u->ue == 'UC55';
            }))[0];
        }

        $this->assertNotEmpty($uc55vals);
        foreach ($uc55vals as $type => $val) {
            switch ($type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(2, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(20, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(100, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(2000, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
            }
        }
    }
}
