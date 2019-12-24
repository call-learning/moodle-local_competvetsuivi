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
 * The ueutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ueutils_tests extends competvetsuivi_tests {

    public function test_get_first_ue() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $firstue = \local_competvetsuivi\ueutils::get_first_ue($matrix);
        $this->assertEquals('UC51', $firstue->shortname);
    }

    public function test_get_semester_for_ue() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $ue51 = $matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $ue102 = $matrix->get_matrix_ue_by_criteria('shortname', 'UC102');
        $this->assertEquals(1, \local_competvetsuivi\ueutils::get_semester_for_ue($ue51, $matrix));
        $this->assertEquals(6, \local_competvetsuivi\ueutils::get_semester_for_ue($ue102, $matrix));
    }

    public function test_get_ues_for_semester() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $uelists1 = \local_competvetsuivi\ueutils::get_ues_for_semester(1, $matrix);
        $uelists6 = \local_competvetsuivi\ueutils::get_ues_for_semester(6, $matrix);
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
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $semestercount = \local_competvetsuivi\ueutils::get_semester_count($matrix);
        $this->assertEquals(8, $semestercount);
    }

    public function test_get_current_semester_index() {
        global $DB;
        $this->resetAfterTest();
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $this->assertEquals(1, \local_competvetsuivi\ueutils::get_current_semester_index('UC51', $matrix));
        $this->assertEquals(2, \local_competvetsuivi\ueutils::get_current_semester_index('UC61', $matrix));
        $this->assertEquals(8, \local_competvetsuivi\ueutils::get_current_semester_index('UC121', $matrix));
    }

    public function test_get_ue_vs_competencies() {
        $this->resetAfterTest();
    }

    public function test_get_ue_vs_competencies_percent() {
        $this->resetAfterTest();
    }
}

