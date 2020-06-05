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
use local_competvetsuivi\cacheutils;
use local_competvetsuivi\ueutils;

/**
 * The chartingutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cacheutils_test extends competvetsuivi_tests {

    public function test_get_ue_vs_competencie_hash() {
        $this->resetAfterTest();
        $currentue = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $rootcomp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $currentue2 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC53');
        $rootcomp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $hash = cacheutils::get_ue_vs_competencie_hash($this->matrix, $currentue, $rootcomp->id, true);
        $hashsame = cacheutils::get_ue_vs_competencie_hash($this->matrix, $currentue, $rootcomp->id, true);
        $this->assertEquals($hash, $hashsame); // Assert hash is the same twice.
        $hashdiff = cacheutils::get_ue_vs_competencie_hash($this->matrix, $currentue2, $rootcomp2->id, true);
        $this->assertNotEquals($hash, $hashdiff); // Competency and ue change.
        $hashdiff = cacheutils::get_ue_vs_competencie_hash($this->matrix, $currentue, $rootcomp->id, false);
        $this->assertNotEquals($hash, $hashdiff); // Same semester change.
        $hashdiff = cacheutils::get_ue_vs_competencie_hash($this->matrix, $currentue, $rootcomp2->id, true);
        $this->assertNotEquals($hash, $hashdiff); // Current ue change.
    }

    public function test_get_ue_vs_competencies_percent_hash() {
        $this->resetAfterTest();
        $currentue = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC51');
        $rootcomp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $currentue2 = $this->matrix->get_matrix_ue_by_criteria('shortname', 'UC53');
        $rootcomp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $strandids = [matrix::MATRIX_COMP_TYPE_ABILITY, matrix::MATRIX_COMP_TYPE_KNOWLEDGE];
        $hash = cacheutils::get_ue_vs_competencies_percent_hash($this->matrix, $currentue, $strandids, $rootcomp->id);
        $hashsame = cacheutils::get_ue_vs_competencies_percent_hash($this->matrix, $currentue, $strandids, $rootcomp->id);
        $this->assertEquals($hash, $hashsame); // Assert hash is the same twice.
        $hashdiff = cacheutils::get_ue_vs_competencies_percent_hash($this->matrix, $currentue, [matrix::MATRIX_COMP_TYPE_ABILITY],
            $rootcomp->id);
        $this->assertNotEquals($hash, $hashdiff); // Not same strand.
        $hashdiff = cacheutils::get_ue_vs_competencies_percent_hash($this->matrix, $currentue2, $strandids, $rootcomp->id);
        $this->assertNotEquals($hash, $hashdiff); // Not same ue.
        $hashdiff = cacheutils::get_ue_vs_competencies_percent_hash($this->matrix, $currentue, $strandids, $rootcomp2->id);
        $this->assertNotEquals($hash, $hashdiff); // Not same comp.

    }

    public function test_get_comp_progress_hash() {
        $this->resetAfterTest();
        $rootcomp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $rootcomp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV');
        $strands = [matrix::MATRIX_COMP_TYPE_ABILITY, matrix::MATRIX_COMP_TYPE_KNOWLEDGE];
        $useremail = "Etudiant-145@ecole.fr";
        $userdata = local_competvetsuivi\userdata::get_user_data($useremail);
        $ueselection = ueutils::get_ues_for_semester(1, $this->matrix);
        $hash = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp, $userdata, $strands, $ueselection);
        $hashsame = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp, $userdata, $strands, $ueselection);
        $this->assertEquals($hash, $hashsame); // Assert hash is the same twice.
        $hashdiff = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp, $userdata);
        $this->assertNotEquals($hash, $hashdiff); // Missing ueselection and strands.
        $hashdiff = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp, $userdata, $strands);
        $this->assertNotEquals($hash, $hashdiff); // Missing ueselection and strands.
        $hashdiff = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp, $userdata, $strands);
        $this->assertNotEquals($hash, $hashdiff); // Missing ueselection.
        $hashdiff = cacheutils::get_comp_progress_hash($this->matrix, $rootcomp2, $userdata, $strands, $ueselection);
        $this->assertNotEquals($hash, $hashdiff); // Different selection.
    }
}