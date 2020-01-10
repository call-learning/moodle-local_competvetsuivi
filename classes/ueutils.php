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

    public static function get_first_ue($matrix) {
        if (empty($matrix->ues)) {
            return null;
        }
        return array_values($matrix->ues)[0];
    }

    /**
     * Calculate the semester number for the given UE/UC
     *
     * @param $ue
     * @return float|int
     */
    public static function get_semester_for_ue($ue, $matrix) {
        $semester = 1;
        $firstue = ueutils::get_first_ue($matrix);
        $firstueval = intval(substr($firstue->shortname, 2));
        if ($ue) {
            return floor(((intval(substr($ue->shortname, 2)) - $firstueval) / 10) + 1);
        }
        return $semester;
    }

    /**
     * Get all UEs/UCs contained in a semester
     *
     * @param $semester
     * @param $matrix
     * @return array
     */
    public static function get_ues_for_semester($semester, $matrix) {
        $uelist = $matrix->ues;
        return array_filter($uelist, function($ue) use ($semester, $matrix) {
            // For now it is a guess work but it should be coming from the database as a group of UEs
            return ueutils::get_semester_for_ue($ue, $matrix) == $semester;
        });
    }

    /**
     * Get the number of semester. This will probably be overrident when using groups
     *
     * @return int
     */
    public static function get_semester_count($matrix) {
        $uelist = $matrix->ues;
        $mapsemester = array_map(function($ue) use ($matrix) {
            return ueutils::get_semester_for_ue($ue, $matrix);
        }, $uelist);
        return count(array_unique($mapsemester));
    }

    const YEAR_START_MONTH = 9; // September

    /**
     * Get current semester from the name (shortname) of the last seen UC/UE
     *
     * @param $lastseenuesn: last seen ue shortname
     * @param $matrix
     * @return float|int
     */
    public static function get_current_semester_index($lastseenuesn, $matrix) {
        $matrixues = $matrix->get_matrix_ues();
        $semester = 1;
        if ($lastseenuesn) {
            // Make sure this UE belongs to the matrix
            $foundue = null;
            foreach ($matrixues as $ue) {
                if ($lastseenuesn == $ue->shortname) {
                    $foundue = $ue;
                    break;
                }
            }
            if ($foundue) {
                $semester = ueutils::get_semester_for_ue($ue, $matrix);
            }

        }
        return $semester;
    }

    /**
     * Return the contribution of given UE to the immediate child competencies rooted by rootcompid.
     * Results are for each competency and then each strands covered by the UE.
     * We calculate the max value for each ue over the set of subcompetencies
     * TODO : Implements Caching
     * @param $matrix : ue
     * @param $ue : given ue
     * @param int $rootcompid root competency id to start with. If null we take the macro competencies
     * @param bool $samesemesteronly only in the same semester
     * @return array
     */
    public static function get_ue_vs_competencies($matrix, $currentue, $rootcompid = 0, $samesemesteronly = false) {
        // Deal with cache
        $hash = cacheutils::get_ue_vs_competencie_hash($matrix, $currentue, $rootcompid, $samesemesteronly);
        $cachedvalue = cacheutils::get($hash, 'ue_vs_comp');
        if ($cachedvalue) {
            return $cachedvalue;
        }
        // Deal with cache
        /** @var $matrix matrix */
        $allcomps = $matrix->get_child_competencies($rootcompid, true);

        // Case it is a leaf competency
        if ($rootcompid && !$allcomps) {
            $allcomps = [ $matrix->get_matrix_competencies()[$rootcompid] ];
        }

        $compuestrandvalues = [];

        // Restrict UE to semester or not
        if ($samesemesteronly) {
            $semester = self::get_semester_for_ue($currentue, $matrix);
            $allues = self::get_ues_for_semester($semester, $matrix);
        } else {
            $allues = $matrix->get_matrix_ues();
        }
        // Go through all competencies and find out about the contribution of each UE to this competency
        foreach ($allcomps as $comp) {
            if (!key_exists($comp->id, $compuestrandvalues)) {
                $compuestrandvalues[$comp->id] = [];
            }
            foreach ($allues as $ue) {
                $currentuevals = $matrix->get_total_values_for_ue_and_competency($ue->id, $comp->id, true);
                foreach ($currentuevals as $strandval) {
                    $strandid = $strandval->type;
                    if (!key_exists($ue->id, $compuestrandvalues[$comp->id])) {
                        $compuestrandvalues[$comp->id][$ue->id] = [];
                    }
                    if (!isset($compuestrandvalues[$comp->id][$ue->id][$strandid])) {
                        $compuestrandvalues[$comp->id][$ue->id][$strandid] = 0;
                    }
                    $compuestrandvalues[$comp->id][$ue->id][$strandid] += $strandval->totalvalue;
                }
            }
        }
        // Now calculate the results for each comp
        $results = [];
        foreach ($allcomps as $comp) {
            // Now we have the max value for each ue and each strand, we calculate the range (0, max)
            // First initialize the array
            $maxuevalues = array_fill_keys(array_keys(matrix::MATRIX_COMP_TYPE_NAMES), 0);
            // Then for each UE add its contribution to the semester/cursus so we have the max contributed
            $maxuevalues = array_reduce($compuestrandvalues[$comp->id], function($carry, $item) {
                foreach (array_keys(matrix::MATRIX_COMP_TYPE_NAMES) as $strandid) {
                    $carry[$strandid] += $item[$strandid];
                }
                return $carry;
            }, $maxuevalues);
            // Now for the current UE, just calculate its contribution
            foreach (array_keys(matrix::MATRIX_COMP_TYPE_NAMES) as $strandid) {
                $results[$comp->id][$strandid] =
                        $maxuevalues[$strandid] ?
                                $compuestrandvalues[$comp->id][$currentue->id][$strandid] / $maxuevalues[$strandid] : 0;
            }
        }

        // Deal with cache
        $isset = cacheutils::set('ue_vs_comp', $hash, $results);
        // Deal with cache
        return $results;
    }

    /**
     * Return the contribution of given UE to the immediate child competencies rooted by rootcompid.
     * Results are for each competency for a given set of strands.
     * We calculate for each strand the total contribution and we highlight the highest value
     * The other strands will be respresented as a percentage of this value
     * TODO : Implements Caching
     * @param $matrix
     * @param $currentue
     * @param $strandids
     * @param int $rootcompid
     * @return \stdClass
     */
    public static function get_ue_vs_competencies_percent($matrix, $currentue, $strandids, $rootcompid = 0) {

        // Deal with cache
        $hash = cacheutils::get_ue_vs_competencies_percent_hash($matrix, $currentue, $strandids, $rootcompid);
        $cachedvalue = cacheutils::get($hash, 'ue_vs_comp_pc');
        if ($cachedvalue) {
            return $cachedvalue;
        }
        // Deal with cache

        /** @var $matrix matrix */
        $allcomps = $matrix->get_child_competencies($rootcompid, true);

        $compuevalues = [];

        // Go through all competencies and strands and find out about the contribution of the UE to this competency
        $maxval = 0;

        foreach ($allcomps as $comp) {
            $currentuevals = $matrix->get_total_values_for_ue_and_competency($currentue->id, $comp->id, true);
            foreach ($currentuevals as $strandval) {
                if (!in_array($strandval->type, $strandids)) {
                    continue;
                }
                if (!key_exists($comp->id, $compuevalues)) {
                    $compuevalues[$comp->id] = [];
                }
                if (!key_exists($strandval->type, $compuevalues[$comp->id])) {
                    $compuevalues[$comp->id][$strandval->type] = 0;
                }
                $maxval += $strandval->totalvalue;
                $compuevalues[$comp->id][$strandval->type] += $strandval->totalvalue;
            }
        }
        // Now calculate the results for each competency
        /*
            The way we go about it:
            - We want to obtain a percentage of contribution to the competency ref. the total : so val is this percentage
            - within each competency, we want to obtain the contribution of each strand
        */

        $results = new \stdClass();
        $results->compsvalues = [];
        $index = 0;
        if (!$maxval) {
            return $results; // Nothing if no max val attained.
        }
        foreach ($compuevalues as $compid => $strandvalues) {
            // Get the max value across all strands
            $totalforcomp = array_sum($strandvalues);
            $compvalue = new \stdClass();
            $compvalue->val = $totalforcomp / $maxval;
            if ($compvalue->val > 0) { // Remove 0 values
                $compvalue->strandvals = [];
                foreach ($strandvalues as $strandid => $strandtotal) {
                    $compvalue->strandvals[$strandid] = new \stdClass();
                    $compvalue->strandvals[$strandid]->val = $strandtotal / $totalforcomp;
                    $compvalue->strandvals[$strandid]->type = $strandid;
                }
                $compvalue->colorindex = $index; // Index for color => this works because get_child_competencies orders by id
                $compvalue->fullname = $allcomps[$compid]->fullname;
                $compvalue->shortname = $allcomps[$compid]->shortname;
                $results->compsvalues[$compid] = $compvalue;
            }
            $index++;
        }

        // Deal with cache
        $isset = cacheutils::set('ue_vs_comp_pc', $hash, $results);
        // Deal with cache
        return $results;
    }
}