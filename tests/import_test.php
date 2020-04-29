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
 * File containing tests for import_test.
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\userdata;

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

/**
 * The import_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_test extends advanced_testcase {

    public function test_import_matrix() {
        $this->resetAfterTest();
        $filename = dirname(__FILE__) . '/fixtures/matrix_sample.xlsx';
        $content = file_get_contents($filename);
        $hash = sha1($content);
        list($matrixobject, $logmessage) = matrix::import_from_file(
            $filename,
            $hash,
            'TestMatrix',
            'TESTMATRIX');

        $matrix = new matrix($matrixobject->id);
        $matrix->load_data();
        $comps = array_values($matrix->get_matrix_competencies());
        $mastercomp = $comps[0]; // COPREV
        $childcomp = $comps[1]; // COPREV.1
        $leafcomp = $comps[4]; // COPREV.1.2.
        $this->assertEquals('COPREV', $mastercomp->shortname);
        $this->assertEquals("/{$mastercomp->id}", $mastercomp->path);
        $this->assertEquals('COPREV.1', $childcomp->shortname);
        $this->assertEquals("/{$mastercomp->id}/{$childcomp->id}", $childcomp->path);
        $this->assertEquals('COPREV.1.2', $leafcomp->shortname);
        $this->assertEquals("/{$mastercomp->id}/{$childcomp->id}/{$leafcomp->id}", $leafcomp->path);
        $this->assertEquals("Competencies loaded 73, Macrocompetencies 2, UC/UE number 50.", $logmessage);

    }

    public function test_import_users() {
        global $DB;
        $this->resetAfterTest();
        // With UE.
        $filename = dirname(__FILE__) . '/fixtures/userdata_sample.csv';
        $status = userdata::import_user_data_from_file($filename);
        $user = $DB->get_record('cvs_userdata', array('useremail' => 'Etudiant-143@ecole.fr'));
        $userdata = json_decode($user->userdata);
        $this->assertEquals(1, $userdata->UC53);
        $this->assertEquals(0, $userdata->UC52);
        $this->assertEquals($user->lastseenunit, 'UC55');
        // Now with UC.
        $filename = dirname(__FILE__) . '/fixtures/userdata_sample_uc.csv';
        $status = userdata::import_user_data_from_file($filename);
        $user = $DB->get_record('cvs_userdata', array('useremail' => 'Etudiant-143@ecole.fr'));
        $userdata = json_decode($user->userdata);
        $this->assertEquals(1, $userdata->UC53);
        $this->assertEquals(0, $userdata->UC52);
        $this->assertEquals($user->lastseenunit, 'UC55');
    }
}
