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
    /**
     * Get  progress vs possible value per strand for the selection of ue
     *
     * @param $matrix
     * @param $currentcomp
     * @param $userdata
     * @param array $strands
     * @return array
     */
    public static function get_comp_progress($matrix, $currentcomp, $userdata, $strands = array(), $ueselection = null) {
        // For each competency regroup all finished ues and values
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $currentcomp, $userdata, $ueselection, true);
        $progressperstrand = [];
        $maxperstrand = [];
        foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypeid => $comptypname) {
            if (key_exists($comptypeid, $possiblevsactual)
                    && (in_array($comptypname, $strands)
                            || empty($strands))) {
                $progressperstrand[$comptypeid] = array_reduce($possiblevsactual[$comptypeid],
                        function($acc, $val) use ($comptypeid) {
                            $progress = 0;
                            $strandfactor = $val->possibleval / (matrix::MAX_VALUE_PER_STRAND[$comptypeid] / 3);
                            switch ($strandfactor) {
                                case 1 :
                                    $progress = 1;
                                    break;
                                case 2:
                                    $progress = 0.5;
                                    break;
                            }
                            return $acc + $progress*$val->userval; // Previous value
                        },
                        0);

                $maxperstrand[$comptypeid] = array_reduce($possiblevsactual[$comptypeid],
                        function($acc, $val) use ($comptypeid) {
                            $max = 0;
                            $strandfactor = $val->possibleval / (matrix::MAX_VALUE_PER_STRAND[$comptypeid] / 3);
                            switch ($strandfactor) {
                                case 1 :
                                    $max = 1;
                                    break;
                                case 2:
                                    $max = 0.5;
                                    break;
                            }
                            return $acc + $max; // Previous value
                        },
                        0);

            }
        }
        return array($progressperstrand, $maxperstrand);
    }
}