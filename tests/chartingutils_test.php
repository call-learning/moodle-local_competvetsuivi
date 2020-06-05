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
 * File containing tests for chartingutils_test.
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
// https://phpunit.de .

require_once(__DIR__ . '/lib.php');

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\chartingutils;
use local_competvetsuivi\ueutils;

/**
 * The chartingutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chartingutils_test extends competvetsuivi_tests {

    /**
     * Check competencies results
     * @param array $computedresults
     * @throws \local_competvetsuivi\matrix\matrix_exception
     */
    protected function assert_competencies_results($computedresults) {
        foreach ($computedresults as $compname => $expectedresults) {
            $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', $compname);
            $userdata = local_competvetsuivi\userdata::get_user_data("Etudiant-145@ecole.fr");
            // User has been validated up to and including UC55.
            $strands = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY,
                matrix::MATRIX_COMP_TYPE_EVALUATION);
            list($progressperstrand, $maxperstrand) =
                chartingutils::get_comp_progress($this->matrix, $comp, $userdata, $strands);
            foreach ($strands as $strand) {
                $this->assertEquals($expectedresults[$strand][1],
                    $maxperstrand[$strand],
                    "Max calculation issue - Strand($strand) : $compname");
                $this->assertEquals($expectedresults[$strand][0],
                    $progressperstrand[$strand],
                    "Progress calculation Issue - Strand($strand) : $compname");
            }
        }
    }

    public function test_get_comp_progress() {
        $this->resetAfterTest();

        $computedresults = array(
            "COPREV.1.1" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [1.5, 6.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [0.5, 4],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [1, 3]
            ),
            "COPREV.1.1BIS" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [1.5, 6.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [1, 4],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [1, 2]
            ),
            "COPREV.1.2" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [0, 3.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [0, 1.5],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [0, 1]
            ),
            "COPREV.1.3" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [1.5, 9.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [1.5, 9],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [0.5, 5.5]
            ),
            "COPREV.1.4" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [0, 2.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [0, 1.5],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [0, 1]
            ),

        );
        $this->assert_competencies_results($computedresults);

    }

    public function test_get_comp_progress_aggregated() {
        $this->resetAfterTest();

        $computedresults = array(
            "COPREV.1" => array(
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [4.5, 28.5],
                matrix::MATRIX_COMP_TYPE_ABILITY => [3, 20],
                matrix::MATRIX_COMP_TYPE_OBJECTIVES => [4, 16.5],
                matrix::MATRIX_COMP_TYPE_EVALUATION => [2.5, 12.5]
            ));
        $this->assert_competencies_results($computedresults);
    }

    public function test_get_data_for_progressbar() {
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1.1');
        $useremail = "Etudiant-145@ecole.fr";
        $userdata = local_competvetsuivi\userdata::get_user_data($useremail);
        $lastseenue = local_competvetsuivi\userdata::get_user_last_ue_name($useremail);
        $currentsemester = ueutils::get_current_semester_index($lastseenue, $this->matrix);

        $data = chartingutils::get_data_for_progressbar($this->matrix,
            $comp,
            array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY),
            $userdata,
            $currentsemester);

        $computedresults = array(
            matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 1.5 / 6.5,
            matrix::MATRIX_COMP_TYPE_ABILITY => 0.5 / 4,
        );
        $markers = array( // Markers positions are cumulative and we only see markers who have a different percentage.
            matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [
                '5' => 1.5 / 6.5,
                '8' => (1.5 + 1) / 6.5,
                '10' => (1.5 + 1 + 1) / 6.5,
                '11' => (1.5 + 1 + 1 + 1.5) / 6.5,
                '12' => (1.5 + 1 + 1 + 1.5 + 1.5) / 6.5,  // 100%.
            ],
            matrix::MATRIX_COMP_TYPE_ABILITY => [
                '5' => 0.5 / 4,
                '8' => (0.5 + 0.5) / 4,
                '10' => (0.5 + 0.5 + 1) / 4,
                '11' => (0.5 + 0.5 + 1 + 1.5) / 4,
                '12' => (0.5 + 0.5 + 1 + 1.5 + 0.5) / 4, // 100%.
            ]
        );
        $this->assert_competencies_results_marker($computedresults, $markers, $data);
    }

    public function test_get_data_for_progressbar_aggregated() {
        $this->resetAfterTest();
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $useremail = "Etudiant-145@ecole.fr";
        $userdata = local_competvetsuivi\userdata::get_user_data($useremail);
        $lastseenue = local_competvetsuivi\userdata::get_user_last_ue_name($useremail);
        $currentsemester = ueutils::get_current_semester_index($lastseenue, $this->matrix);
        $data = chartingutils::get_data_for_progressbar($this->matrix,
            $comp,
            array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY),
            $userdata,
            $currentsemester);

        $computedresults = array(
            matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 4.5 / 28.5,
            matrix::MATRIX_COMP_TYPE_ABILITY => 3 / 20,
        );
        $markers = array( // Markers positions are cumulative and we only see markers who have a different percentage.
            matrix::MATRIX_COMP_TYPE_KNOWLEDGE => [
                '5' => 4.5 / 28.5,
                '6' => (4.5 + 1.5) / 28.5,
                '8' => (4.5 + 1.5 + 5.5) / 28.5,
                '10' => (4.5 + 1.5 + 5.5 + 4) / 28.5,
                '11' => (4.5 + 1.5 + 5.5 + 4 + 9.5) / 28.5,
                '12' => (4.5 + 1.5 + 5.5 + 4 + 9.5 + 3.5) / 28.5,
            ],
            matrix::MATRIX_COMP_TYPE_ABILITY => [
                '5' => 3 / 20,
                '6' => (3 + 1) / 20,
                '8' => (3 + 1 + 2.5) / 20,
                '10' => (3 + 1 + 2.5 + 3) / 20,
                '11' => (3 + 1 + 2.5 + 3 + 6.5) / 20,
                '12' => (3 + 1 + 2.5 + 3 + 6.5 + 4) / 20,
            ]
        );
        $this->assert_competencies_results_marker($computedresults, $markers, $data);
    }

    /**
     * Assert that marker has the right position
     * @param array $computedresults
     * @param array $markers
     * @param array $data
     */
    protected function assert_competencies_results_marker($computedresults, $markers, $data) {
        $this->assertEquals($computedresults[matrix::MATRIX_COMP_TYPE_KNOWLEDGE], $data[0]->result->value);
        $this->assertEquals($computedresults[matrix::MATRIX_COMP_TYPE_ABILITY], $data[1]->result->value);
        foreach ($markers as $strandid => $results) {
            $markersforsemester = array_filter($data, function($d) use ($strandid) {
                return $d->result->type == $strandid;
            });
            $markersforsemester = reset($markersforsemester);

            foreach ($results as $semesterlabel => $cumulativeresult) {
                $currentmarker = array_filter($markersforsemester->markers, function($m) use ($semesterlabel) {
                    return $m->label == $semesterlabel;
                });
                $currentmarker = reset($currentmarker);
                $this->assertEquals($cumulativeresult, $currentmarker->value,
                    "Current marker {$currentmarker->label} within strand({$strandid}), should have a value
                        of {$cumulativeresult} but has a value of {$currentmarker->value}");
                $shouldbeactive = ($currentmarker->label) > 5; // User is currently looking at UC55.
                if ($shouldbeactive) {
                    $this->assertTrue($currentmarker->active,
                        "Current marker {$currentmarker->label} within strand({$strandid}) should be active.");
                } else {
                    $this->assertFalse($currentmarker->active,
                        "Current marker {$currentmarker->label} within strand({$strandid}) should be inactive.");
                }
            }
        }
    }
}