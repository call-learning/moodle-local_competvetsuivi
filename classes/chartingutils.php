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
 * Generic tools
 *
 * @package     local_competvetsuivi
 * @category    charting tools
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

use local_competvetsuivi\matrix\matrix;

class chartingutils {

    public const MAX_VALUE_PER_STRAND = [
            matrix::MATRIX_COMP_TYPE_CAPABILITY => 1,
            matrix::MATRIX_COMP_TYPE_LEARNING => 10,
            matrix::MATRIX_COMP_TYPE_OBJECTIVES => 100,
            matrix::MATRIX_COMP_TYPE_EVALUATION => 1000
    ];

    public static function get_comp_dataset($matrix, $currentcomp, $userdata) {
        // For each competency regroup all finished ues and values
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $currentcomp, $userdata, true);
        foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypeid => $comptypname) {

            // Show per semester
            //            for ($semester = 0; $semester < 8; $semester++) { // TODO : rewrite this part
            //                $uelist = ueutils::get_ues_for_semester($semester, $matrix); //
            // Write the chart
            $currentuserdata = 0;
            $possibleuserdata = 0;
            if (key_exists($comptypeid, $possiblevsactual)) {
                $currentuserdata = array_reduce($possiblevsactual[$comptypeid],
                        function($acc, $val) {
                            return $acc + intval($val->userval) * intval($val->possibleval);
                        },
                        0);
                $possibleuserdata = array_reduce(
                        $possiblevsactual[$comptypeid],
                        function($acc, $val) {
                            return $acc + intval($val->possibleval);
                        },
                        0);
            }
            $possibledataset[] = $possibleuserdata / static::MAX_VALUE_PER_STRAND[$comptypeid];
            $currentuserdataset[] = $currentuserdata / static::MAX_VALUE_PER_STRAND[$comptypeid];
        }
        return array($possibledataset, $currentuserdataset);
    }

}