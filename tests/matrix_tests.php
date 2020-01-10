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

use local_competvetsuivi\matrix\matrix;
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

    public function test_get_real_value_from_strand() {
        global $DB;
        $this->resetAfterTest();
        $strandvalues = array(
                array(
                        'result' => 0,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 0,
                                matrix::MATRIX_COMP_TYPE_ABILITY => 0,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => 0,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => 0
                        ]
                ),
                array(
                        'result' => 1,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 1,
                                matrix::MATRIX_COMP_TYPE_ABILITY => 10,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => 100,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => 1000
                        ]
                ),
                array(
                        'result' => 0.5,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 2,
                                matrix::MATRIX_COMP_TYPE_ABILITY => 20,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => 200,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => 2000
                        ]
                ),
                array(
                        'result' => 0,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 3,
                                matrix::MATRIX_COMP_TYPE_ABILITY => 30,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => 300,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => 3000
                        ]
                ),
                array(
                        'result' => 0,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 4,
                                matrix::MATRIX_COMP_TYPE_ABILITY => 40,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => 400,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => 4000
                        ]
                ),
                array(
                        'result' => 0,
                        'values' => [
                                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => -4,
                                matrix::MATRIX_COMP_TYPE_ABILITY => -40,
                                matrix::MATRIX_COMP_TYPE_OBJECTIVES => -400,
                                matrix::MATRIX_COMP_TYPE_EVALUATION => -4000
                        ]
                )
        );
        foreach ($strandvalues as $sv) {
            foreach ($sv['values'] as $type => $value) {
                $this->assertEquals($sv['result'],
                        matrix::get_real_value_from_strand($type, $value)
                );
            }
        }
    }

    public function test_get_matrix_comp_by_criteria() {
        global $DB;
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $this->assertEquals($comp->shortname, 'COPREV.2');
        $comp = $this->matrix->get_matrix_comp_by_criteria('id', $comp->id);
        $this->assertEquals($comp->shortname, 'COPREV.2');
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $this->assertEquals($comp->shortname, 'COPREV.1.1');
        $this->expectException(matrix_exception::class);
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2ZDQSD');
    }

    public function test_get_values_for_ue_and_competency() {
        global $DB;
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $uc51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $uc54 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC54');

        $values = $this->matrix->get_values_for_ue_and_competency($uc51->id, $comp->id, false);
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

        $values = $this->matrix->get_values_for_ue_and_competency($uc54->id, $comp->id, false);
        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(1, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(30, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(300, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(2000, $val->value);
                    break;
            }
        }

    }

    public function test_get_values_for_ue_and_competency_aggregated() {
        global $DB;
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $uc55 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC55');
        $values = $this->matrix->get_values_for_ue_and_competency($uc55->id, $comp->id, true);

        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(2, $val->value);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(10, $val->value); //CoPrev.2.7bis.
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(100, $val->value); // CoPrev.2.7bis.
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(1000, $val->value); //CoPrev.2.7bis.
                    break;
            }
        }
    }

    public function test_get_total_values_for_ue_and_competency() {
        global $DB;
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $uc51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $uc54 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC54');

        $values = $this->matrix->get_total_values_for_ue_and_competency($uc51->id, $comp->id, false);
        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
            }
        }

        $values = $this->matrix->get_total_values_for_ue_and_competency($uc54->id, $comp->id, false);
        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(1, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(0, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(0.5, $val->totalvalue);
                    break;
            }
        }

    }

    public function test_get_total_values_for_ue_and_competency_aggregated() {
        global $DB;
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $matrixues = $this->matrix->get_matrix_ues();
        $uc55 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC55');
        $values = $this->matrix->get_total_values_for_ue_and_competency($uc55->id, $comp->id, true);

        $this->assertNotEmpty($values);
        foreach ($values as $val) {
            switch ($val->type) {
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(2.5, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_ABILITY:
                    $this->assertEquals(3, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(5, $val->totalvalue);
                    break;
                case \local_competvetsuivi\matrix\matrix::MATRIX_COMP_TYPE_EVALUATION:
                    $this->assertEquals(3, $val->totalvalue);
                    break;
            }
        }
    }

    public function test_has_children() {
        global $DB;
        $this->resetAfterTest();
        $coprev2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $coprev23 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2.3');
        $this->assertTrue($this->matrix->has_children($coprev2));
        $this->assertFalse($this->matrix->has_children($coprev23));
    }

    public function test_get_matrix_ue_by_criteria() {
        global $DB;
        $this->resetAfterTest();
        $uc51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $this->assertEquals('UC51', $uc51->shortname);
        $uc51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UE51');
        $this->assertEquals('UC51', $uc51->shortname);
    }

    public function test_normalize_uc_name() {
        $this->resetAfterTest();
        $this->assertEquals('UC51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UC51'));
        $this->assertEquals('UC51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UE51'));
        $this->assertEquals('UCUV51', \local_competvetsuivi\matrix\matrix::normalize_uc_name('UV51'));
    }

    public function test_get_root_competency() {
        global $DB;
        $this->resetAfterTest();
        $rootcomp = $this->matrix->get_root_competency();
        $this->assertEquals('COPREV', $rootcomp->shortname);
    }

    public function test_get_child_competencies_root_direct_child() {
        $this->resetAfterTest();
        $comps = $this->matrix->get_child_competencies(0, true);
        $this->assertCount(1, $comps); // This should be COPREV
        $coprev = reset($comps);
        $this->assertEquals('COPREV', $coprev->shortname);
    }

    public function test_get_child_competencies_coprev_direct_child() {
        $this->resetAfterTest();
        $coprev = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $comps = $this->matrix->get_child_competencies($coprev->id, true);
        $this->assertCount(3, $comps); // This should be COPREV
        $coprev = reset($comps);
        $this->assertEquals('COPREV.1', $coprev->shortname);
    }

    public function test_get_child_competencies_root() {
        $this->resetAfterTest();
        $comps = $this->matrix->get_child_competencies();
        $this->assertCount(23, $comps); // ALL COPREV competencies including COPREV itself
    }

    public function test_get_child_competencies_coprev2() {
        $this->resetAfterTest();
        $coprev2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $comps = $this->matrix->get_child_competencies($coprev2->id);
        $this->assertCount(10, $comps);
        $comps = $this->matrix->get_child_competencies($coprev2->id, true);
        $this->assertCount(10, $comps);
    }

    public function test_get_child_competencies_coprev2_directchild_cache() {
        $this->resetAfterTest();
        $coprev2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $compsv1 = $this->matrix->get_child_competencies($coprev2->id, true);
        $this->assertCount(10, $compsv1);
        $compsv2 = $this->matrix->get_child_competencies($coprev2->id, true);
        $this->assertCount(10, $compsv2);
        $this->assertArraySubset($compsv1, $compsv2);
    }
}
