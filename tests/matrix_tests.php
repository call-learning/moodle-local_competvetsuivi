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
 * File containing tests for matrix_test.
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\matrix\matrix_exception;

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

/**
 * The matrix_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix_tests extends competvetsuivi_tests {
    public function test_get_values_for_ue_and_competency() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $matrixues = $matrix->get_matrix_ues();
        $uc51 = reset($matrixues);
        $values = $matrix->get_values_for_ue_and_competency($uc51->id, $comp->id, false);

        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(3, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(30, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(300, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(3000, $val->value);
                    break;
            }
        }
    }

    public function test_get_matrix_comp_by_criteria() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $this->assertEquals($comp->shortname, 'COPREV.2');
        $comp = $matrix->get_matrix_comp_by_criteria('id', $comp->id);
        $this->assertEquals($comp->shortname, 'COPREV.2');
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $this->assertEquals($comp->shortname, 'COPREV.1.1');
        $this->expectException(matrix_exception::class);
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2ZDQSD');
    }

    public function test_get_values_for_ue_and_competency_competencies() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $matrixues = $matrix->get_matrix_ues();
        $uc55 = array_values(array_filter($matrixues, function($u) {
            return $u->shortname == 'UC55';
        }))[0];
        $values = $matrix->get_values_for_ue_and_competency($uc55->id, $comp->id, true);

        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(2, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(10, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(100, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(1000, $val->value);
                    break;
            }
        }
    }

    public function test_has_children() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $coprev2 = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $coprev23 = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2.3');
        $this->assertTrue($matrix->has_children($coprev2));
        $this->assertFalse($matrix->has_children($coprev23));
    }

    public function test_get_matrix_ue_by_criteria() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $uc51 = $matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $this->assertEquals('UC51', $uc51->shortname);
        $uc51 = $matrix->get_matrix_ue_by_criteria('shortname', 'UE51');
        $this->assertEquals('UC51', $uc51->shortname);
    }

    public function test_normalize_uc_name() {
        global $DB;
        $this->resetAfterTest();
        $this->assertEquals('UC51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UC51'));
        $this->assertEquals('UC51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UE51'));
        $this->assertEquals('UCUV51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UV51'));
    }
}
