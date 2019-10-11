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
 * @category    generic tools
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

use local_competvetsuivi\matrix\matrix;

class ueutils {

    const FIRST_UE_SEMESTER = 51;
    /**
     * Calculate the semester number for the given UE/UC
     * @param $ue
     * @return float|int
     */
    public static function get_semester_for_ue($ue) {
        $semester = 1;
        if ($ue) {
            return floor(((intval(substr($ue->shortname, 2)) - static::FIRST_UE_SEMESTER) / 10) + 1);
        }
        return $semester;
    }

    /**
     * Get all UEs/UCs contained in a semester
     * @param $semester
     * @param $matrix
     * @return array
     */
    public static function get_ues_for_semester($semester, $matrix) {
        $uelist = $matrix->ues;
        return array_filter($uelist, function($ue) use ($semester) {
            // For now it is a guess work but it should be coming from the database as a group of UEs
            return ueutils::get_semester_for_ue($ue) == $semester;
        });
    }

    const MAX_SEMESTERS = 7;

    /**
     * Get the number of semester. This will probably be overrident when using groups
     * @return int
     */
    public static function get_semester_count() {
        return static::MAX_SEMESTERS;
    }

    const YEAR_START_MONTH = 9; // September
    /**
     * Get current semester from the name (shortname) of the last seen UC/UE
     * @param $lastseenue
     * @param $matrix
     * @return float|int
     */
    public static function get_current_semester_index($lastseenue, $matrix) {
        $matrixues = $matrix->get_matrix_ues();
        $semester = 1;
        if ($lastseenue) {
            // Make sure this UE belongs to the matrix
            $foundue = null;
            foreach ($matrixues as $ue) {
                if ($lastseenue == $ue->shortname) {
                    $foundue = $ue;
                    break;
                }
            }
            if ($foundue) {
                $semester = ueutils::get_semester_for_ue($ue);
            }

        }
        return $semester;
    }

    public static function get_ue_vs_competencies($matrix, $ue, $rootcompid = 0, $samesemesteronly=false) {

        $allcomps = $matrix->get_child_competencies($rootcompid);

        $uevalues = [];
        $allues = [];

        if ($samesemesteronly) {
            $semester = self::get_semester_for_ue($ue);
            $allues = self::get_ues_for_semester($semester, $matrix);
        } else {
            $allues= $matrix->get_matrix_ues();
        }
        foreach ($allcomps as $comp) {
            foreach ($allues as $ue) {
                $currentuevals = $matrix->get_values_for_ue_and_competency($ue->id, $comp->id, false);
                foreach ($currentuevals as $strandval) {
                    $strandid = $strandval->type;
                    if (!key_exists($ue->id, $uevalues)) {
                        $uevalues[$ue->id] = [];
                    }
                    if (!isset($uevalues[$ue->id][$strandid])) {
                        $uevalues[$ue->id][$strandid] = 0;
                    }
                    $uevalues[$ue->id][$strandid] += chartingutils::get_real_value_from_strand($strandid,
                            $strandval->value);
                }
            }
        }
        $maxuevalues = [];
        foreach (array_keys(matrix::MATRIX_COMP_TYPE_NAMES) as $strandid) {
            $maxuevalues[$strandid] = 0;
        }
        $maxuevalues = array_reduce($uevalues,  function ($carry, $item) {
            foreach (array_keys(matrix::MATRIX_COMP_TYPE_NAMES) as $strandid) {
                $carry[$strandid] += $item[$strandid];
            }
            return $carry;
        }, $maxuevalues);
        $results = [];
        foreach (array_keys(matrix::MATRIX_COMP_TYPE_NAMES) as $strandid) {
            $results[$strandid] = $maxuevalues[$strandid] ? $uevalues[$ue->id][$strandid] / $maxuevalues[$strandid] : 0;
        }

        return $results;
    }

}