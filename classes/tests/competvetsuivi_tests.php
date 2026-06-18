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
 * File containing common function for tests.
 *
 * @package   local_competvetsuivi
 * @category  test
 * @copyright 2019 CALL Learning <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\tests;

use advanced_testcase;

/**
 * Base test class for local_competvetsuivi tests.
 *
 * @package   local_competvetsuivi
 * @category  test
 * @copyright 2019 CALL Learning <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class competvetsuivi_tests extends advanced_testcase {
    /**
     * @var \stdClass
     */
    protected $user;
    /**
     * @var \stdClass
     */
    protected $cohort1;
    /**
     * @var \stdClass
     */
    protected $cohort2;
    /**
     * Path of the fixture.
     *
     * @var string
     */
    protected $fixturepath = '/local/competvetsuivi/tests/fixtures/basic';
    /**
     * Sample matrix.
     *
     * @var \local_competvetsuivi\matrix\matrix
     */
    public $matrix;

    /**
     * Setup the data.
     */
    protected function presetup_data(): void {
        $this->user = static::getDataGenerator()->create_user();
        $this->cohort1 = static::getDataGenerator()->create_cohort(['idnumber' => 'COHORT1']);
        $this->cohort2 = static::getDataGenerator()->create_cohort(['idnumber' => 'COHORT2']);
    }

    /**
     * Load the model data
     *
     * @throws coding_exception
     */
    public function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->presetup_data();
        $this->load_data_from_json_fixtures($CFG->dirroot . $this->fixturepath);

        // Setup Matrix as it is used often in tests.
        $matrixid = $DB->get_field('cvs_matrix', 'id', ['shortname' => 'MATRIX1']);
        $matrix = new \local_competvetsuivi\matrix\matrix($matrixid);

        $matrix->load_data();
        $this->matrix = $matrix;
    }

    /**
     * Load data from a series of JSON representing the table data.
     *
     * @param string $fixturepath
     * @throws coding_exception
     */
    private function load_data_from_json_fixtures(string $fixturepath): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('local_competvetsuivi');
        $tables = ['matrix', 'matrix_cohorts', 'matrix_ue', 'matrix_comp', 'matrix_comp_ue', 'userdata'];
        foreach ($tables as $tablename) {
            $filename = $fixturepath . '/' . $tablename . '.json';
            if (file_exists($filename)) {
                $generatorfn = "create_$tablename";
                $records = json_decode(file_get_contents($filename), true);
                if ($tablename == 'matrix_comp_ue') {
                    $generator->create_matrix_comp_ue_bulk($records); // Create the entities in bulk mode.
                } else {
                    foreach ($records as $r) {
                        $generator->$generatorfn($r); // Create the entity.
                    }
                }
            }
        }
    }
}
