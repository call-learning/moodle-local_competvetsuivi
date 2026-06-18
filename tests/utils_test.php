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
 * @package   local_competvetsuivi
 * @category  test
 * @copyright 2019 CALL Learning <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_competvetsuivi;

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\tests\competvetsuivi_tests;

/**
 * The utils_test test class.
 *
 * @package   local_competvetsuivi
 * @copyright 2019 CALL Learning <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_competvetsuivi\utils::class)]
final class utils_test extends competvetsuivi_tests {
    public function test_get_matrixid_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $matrixid = utils::get_matrixid_for_user($this->user->id);
        $this->assertFalse($matrixid);

        cohort_add_member($this->cohort1->id, $this->user->id);
        cohort_add_member($this->cohort2->id, $this->user->id);
        $matrix1id = $DB->get_field('cvs_matrix', 'id', ['shortname' => 'MATRIX1']);
        $matrixid = utils::get_matrixid_for_user($this->user->id);
        $this->assertEquals($matrix1id, $matrixid);
    }

    public function test_assign_matrix_cohort(): void {
        global $DB;
        $this->resetAfterTest();
        $matrix1id = $DB->get_field('cvs_matrix', 'id', ['shortname' => 'MATRIX1']);
        $cohortid = $DB->get_field('cohort', 'id', ['idnumber' => 'COHORT1']);
        utils::assign_matrix_cohort($matrix1id, $cohortid);
        $this->assertCount(1, $DB->get_records(
            'cvs_matrix_cohorts',
            ['matrixid' => $matrix1id, 'cohortid' => $cohortid]
        ));
    }

    public function test_get_possible_vs_actual_values(): void {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', ['shortname' => 'MATRIX1']);
        $matrix = new matrix($matrixid);
        $matrix->load_data();
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $userdata = userdata::get_user_data("Etudiant-145@ecole.fr");
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $comp, $userdata);
        $uc55vals = [];
        foreach ($possiblevsactual as $type => $vals) {
            $uc55vals[$type] = array_values(array_filter($vals, function ($u) {
                return $u->ue == 'UC55';
            }))[0];
        }

        $this->assertNotEmpty($uc55vals);
        foreach ($uc55vals as $type => $val) {
            switch ($type) {
                case matrix::MATRIX_COMP_TYPE_ABILITY:
                case matrix::MATRIX_COMP_TYPE_EVALUATION:
                case matrix::MATRIX_COMP_TYPE_KNOWLEDGE:
                    $this->assertEquals(0.5, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
                case matrix::MATRIX_COMP_TYPE_OBJECTIVES:
                    $this->assertEquals(1, $val->possibleval);
                    $this->assertEquals(1, $val->userval);
                    break;
            }
        }
    }

    public function test_get_possible_vs_actual_values_aggregated(): void {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', ['shortname' => 'MATRIX1']);
        $matrix = new matrix($matrixid);
        $matrix->load_data();
        $comp = $matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $userdata = userdata::get_user_data("Etudiant-145@ecole.fr");
        $ueselection = ueutils::get_ues_for_semester(1, $matrix);
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $comp, $userdata, $ueselection, true);

        $sumvalues = [];
        foreach ($possiblevsactual as $type => $vals) {
            $sumvalues[$type] = array_sum(array_map(function ($v) {
                return $v->possibleval * $v->userval;
            }, $vals));
        }

        $this->assertNotEmpty($sumvalues);
        $this->assertEquals(4.5, $sumvalues[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(3, $sumvalues[matrix::MATRIX_COMP_TYPE_ABILITY]);
        $this->assertEquals(4, $sumvalues[matrix::MATRIX_COMP_TYPE_OBJECTIVES]);
        $this->assertEquals(2.5, $sumvalues[matrix::MATRIX_COMP_TYPE_EVALUATION]);
    }

    public function test_get_default_question_bank_category_name(): void {
        $this->resetAfterTest();

        $categoryname = utils::get_default_question_bank_category_name();
        $this->assertEquals(utils::DEFAULT_QUESTION_BANK_CATEGORY_SN, $categoryname);

        set_config('cvsquestionbankdefaultcategoryname', 'AAAAAAAAAA', 'local_competvetsuivi');
        $categoryname = utils::get_default_question_bank_category_name();
        $this->assertEquals('AAAAAAAAAA', $categoryname);
    }
}
