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
 * File containing tests for ueutils_test.
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;

defined('MOODLE_INTERNAL') || die();

// For installation and usage of PHPUnit within Moodle please read:
// https://docs.moodle.org/dev/PHPUnit
//
// Documentation for writing PHPUnit tests for Moodle can be found here:
// https://docs.moodle.org/dev/PHPUnit_integration
// https://docs.moodle.org/dev/Writing_PHPUnit_tests
//
// The official PHPUnit homepage is at:
// https://phpunit.de.

require_once(__DIR__ . '/lib.php');

/**
 * The ueutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ueutils_test extends competvetsuivi_tests {
    public function test_get_first_ue() {
        $this->resetAfterTest();
        $firstue = ueutils::get_first_ue($this->matrix);
        $this->assertEquals('UC51', $firstue->shortname);
    }

    public function test_get_semester_for_ue() {
        $this->resetAfterTest();
        $ue51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $ue102 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC102');
        $this->assertEquals(1, ueutils::get_semester_for_ue($ue51, $this->matrix));
        $this->assertEquals(6, ueutils::get_semester_for_ue($ue102, $this->matrix));
    }

    public function test_get_ues_for_semester() {
        $this->resetAfterTest();
        $uelists1 = ueutils::get_ues_for_semester(1, $this->matrix);
        $uelists6 = ueutils::get_ues_for_semester(6, $this->matrix);
        $this->assertEquals(array('UC51', 'UC52', 'UC53', 'UC54', 'UC55'),
            array_values(array_map(function($ue) {
                return $ue->shortname;
            }, $uelists1)));
        $this->assertEquals(array('UC101', 'UC102', 'UC103', 'UC104', 'UC105', 'UC106', 'UC107'),
            array_values(
                array_map(function($ue) {
                    return $ue->shortname;
                }, $uelists6)));
    }

    public function test_get_semester_count() {
        $this->resetAfterTest();
        $semestercount = ueutils::get_semester_count($this->matrix);
        $this->assertEquals(8, $semestercount);
    }

    public function test_get_current_semester_index() {
        $this->resetAfterTest();
        $this->assertEquals(1, ueutils::get_current_semester_index('UC51', $this->matrix));
        $this->assertEquals(2, ueutils::get_current_semester_index('UC61', $this->matrix));
        $this->assertEquals(8, ueutils::get_current_semester_index('UC121', $this->matrix));
    }

    public function test_get_ue_vs_competencies_whole_year() {
        $this->resetAfterTest();
        $coprev11 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $ue54 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC54');

        $ueresults = ueutils::get_ue_vs_competencies($this->matrix, $ue54, $coprev11->id);
        /*
         * Here we expect: For the semester => (Knowledge=>6.5, Capability => 4, ...)
         *  UC54 contributes (Knowledge=>1, Capability => 0...)
         */

        $compresult = $ueresults[$coprev11->id];
        $this->assertNotEmpty($ueresults);
        $this->assertEquals(1 / 6.5, $compresult[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(0 / 3, $compresult[matrix::MATRIX_COMP_TYPE_ABILITY]);
        $this->assertEquals(0 / 4, $compresult[matrix::MATRIX_COMP_TYPE_OBJECTIVES]);
        $this->assertEquals(0.5 / 3, $compresult[matrix::MATRIX_COMP_TYPE_EVALUATION]);
    }

    public function test_get_ue_vs_competencies_current_semester() {
        $this->resetAfterTest();
        $coprev11 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $ue54 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC54');

        $ueresults = ueutils::get_ue_vs_competencies($this->matrix, $ue54, $coprev11->id, true);
        /*
         * Here we expect: For the semester => (Knowledge=>1.5, Capability => 0.5, ...)
         *  UC54 contributes (Knowledge=>1, Capability => 0...)
         */
        $compresult = $ueresults[$coprev11->id];
        $this->assertNotEmpty($ueresults);
        $this->assertEquals(1 / 1.5, $compresult[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(0.5 / 1, $compresult[matrix::MATRIX_COMP_TYPE_EVALUATION]);
    }

    public function test_get_ue_vs_competencies_current_semester_aggregated() {
        $this->resetAfterTest();
        $ue54 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC54');
        $coprev = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $coprev1 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $coprev2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $coprev3 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3');
        $ueresults = ueutils::get_ue_vs_competencies($this->matrix, $ue54, $coprev->id, true);
        /*
         * Here we expect that the contribution to the semester will be the max contribution overall for this UE in this
         * semester and the set of competencies, divided by the maximum possible contribution for this semester across the
         * competencies
         */
        $this->assertNotEmpty($ueresults);
        $coprev1results = $ueresults[$coprev1->id];
        $coprev2results = $ueresults[$coprev2->id];
        $coprev3results = $ueresults[$coprev3->id];

        // 4.5 = addition of all values in column.
        $this->assertEquals(3 / 4.5, $coprev1results[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(1 / 3, $coprev1results[matrix::MATRIX_COMP_TYPE_ABILITY]);

        $this->assertEquals(4 / 8.5, $coprev2results[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(3.5 / 7, $coprev2results[matrix::MATRIX_COMP_TYPE_ABILITY]);

        $this->assertEquals(1 / 2, $coprev3results[matrix::MATRIX_COMP_TYPE_KNOWLEDGE]);
        $this->assertEquals(1 / 1, $coprev3results[matrix::MATRIX_COMP_TYPE_ABILITY]);
    }

    public function test_get_ue_vs_competencies_percent() {
        $this->resetAfterTest();
        $ue51 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $coprev = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $coprev1 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $coprev2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $coprev3 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3');
        $strands = [matrix::MATRIX_COMP_TYPE_ABILITY, matrix::MATRIX_COMP_TYPE_KNOWLEDGE];
        $ueresults = ueutils::get_ue_vs_competencies_percent($this->matrix, $ue51, $strands, $coprev->id);
        $this->assertArraySubset(array($coprev2->id, $coprev3->id), array_keys($ueresults->compsvalues));
        $this->assertTrue(!key_exists($coprev1->id, $ueresults->compsvalues));
        $this->assertEquals(0.5, $ueresults->compsvalues[$coprev2->id]->val);
        $this->assertEquals(0.5, $ueresults->compsvalues[$coprev3->id]->val);
    }
}

