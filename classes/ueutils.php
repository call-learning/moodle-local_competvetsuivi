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
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

defined('MOODLE_INTERNAL') || die();

use local_competvetsuivi\matrix\matrix;

/**
 * Class ueutils
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ueutils {

    /**
     * Get the first UE
     *
     * @param matrix $matrix
     * @return mixed|null
     */
    public static function get_first_ue($matrix) {
        if (empty($matrix->ues)) {
            return null;
        }
        return array_values($matrix->ues)[0];
    }

    /**
     * Calculate the semester number for the given UE/UC
     *
     * @param \stdClass $ue
     * @param matrix $matrix
     * @return float|int
     */
    public static function get_semester_for_ue($ue, $matrix) {
        $semester = 1;
        $firstue = self::get_first_ue($matrix);
        $firstueval = intval(substr($firstue->shortname, 2));
        if ($ue) {
            return floor(((intval(substr($ue->shortname, 2)) - $firstueval) / 10) + 1);
        }
        return $semester;
    }

    /**
     * Get all UEs/UCs contained in a semester
     *
     * @param int $semester
     * @param matrix $matrix
     * @return array
     */
    public static function get_ues_for_semester($semester, $matrix) {
        $uelist = $matrix->ues;
        return array_filter($uelist, function($ue) use ($semester, $matrix) {
            // For now it is a guess work but it should be coming from the database as a group of UEs.
            return self::get_semester_for_ue($ue, $matrix) == $semester;
        });
    }

    /**
     * Get the number of semester. This will probably be overrident when using groups
     *
     * @param matrix $matrix
     * @return int
     */
    public static function get_semester_count($matrix) {
        $uelist = $matrix->ues;
        $mapsemester = array_map(function($ue) use ($matrix) {
            return self::get_semester_for_ue($ue, $matrix);
        }, $uelist);
        return count(array_unique($mapsemester));
    }

    /**
     * Starting month (september always)
     */
    const YEAR_START_MONTH = 9; // September.

    /**
     * Get current semester from the name (shortname) of the last seen UC/UE
     *
     * @param string $lastseenuesn : last seen ue shortname
     * @param matrix $matrix
     * @return float|int
     * @throws matrix\matrix_exception
     */
    public static function get_current_semester_index($lastseenuesn, $matrix) {
        $matrixues = $matrix->get_matrix_ues();
        $semester = 1;
        if ($lastseenuesn) {
            // Make sure this UE belongs to the matrix.
            $foundue = null;
            foreach ($matrixues as $ue) {
                if ($lastseenuesn == $ue->shortname) {
                    $foundue = $ue;
                    break;
                }
            }
            if ($foundue) {
                $semester = self::get_semester_for_ue($ue, $matrix);
            }

        }
        return $semester;
    }

    /**
     * Return the contribution of given UE to the immediate child competencies rooted by rootcompid.
     * Results are for each competency for a given set of strands.
     * We calculate for each strand the total contribution and we highlight the highest value
     * The other strands will be respresented as a percentage of this value
     *
     * @param matrix $matrix : ue
     * @param \stdClass $currentue
     * @param array $strandids
     * @param int $rootcompid root competency id to start with. If null we take the macro competencies
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws matrix\matrix_exception
     */
    public static function get_ue_vs_competencies($matrix, $currentue, $strandids, $rootcompid = 0) {
        $resultsdoghtnut = static::get_ue_vs_competencies_percent($matrix, $currentue, $strandids, $rootcompid);
        $resultsmarkers = [];
        foreach ($resultsdoghtnut->compsvalues as $compid => $res) {
            $strands = [];
            foreach ($res->strandvals as $strandid => $st) {
                $strands [$strandid] = $st->val;
            }
            $resultsmarkers[$compid] = $strands;
        }
        return $resultsmarkers;
    }

    /**
     * Return the contribution of given UE to the immediate child competencies rooted by rootcompid.
     * Results are for each competency for a given set of strands.
     * We calculate for each strand the total contribution and we calculate the total possible value (
     * the addition of all strands together) that will be the reference. Basically all strands added
     * will always be 100%.
     *
     * @param matrix $matrix
     * @param \stdClass $currentue
     * @param array $strandids
     * @param int $rootcompid
     * @return \stdClass
     * @throws \dml_exception
     * @throws matrix\matrix_exception
     */
    public static function get_ue_vs_competencies_percent($matrix, $currentue, $strandids, $rootcompid = 0) {

        // Deal with cache.
        $hash = cacheutils::get_ue_vs_competencies_percent_hash($matrix, $currentue, $strandids, $rootcompid);
        $cachedvalue = cacheutils::get($hash, 'ue_vs_comp_pc');
        if ($cachedvalue) {
            return $cachedvalue;
        }
        // Deal with cache.

        /* @var $matrix matrix The matrix to be checked */
        $allcomps = $matrix->get_child_competencies($rootcompid, true);

        $compuevalues = [];

        // Go through all competencies and strands and find out about the contribution of the UE to this competency.
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
            // Get the max value across all strands.
            $totalforcomp = array_sum($strandvalues);
            $compvalue = new \stdClass();
            $compvalue->val = $totalforcomp / $maxval;
            if ($compvalue->val > 0) { // Remove 0 values.
                $compvalue->strandvals = [];
                foreach ($strandvalues as $strandid => $strandtotal) {
                    $compvalue->strandvals[$strandid] = new \stdClass();
                    $compvalue->strandvals[$strandid]->val = $strandtotal / $totalforcomp;
                    $compvalue->strandvals[$strandid]->type = $strandid;
                    $compvalue->strandvals[$strandid]->patternindex = $strandid;
                }
                $compvalue->colorindex = $index; // Index for color => this works because get_child_competencies orders by id.
                $compvalue->fullname = $allcomps[$compid]->fullname;
                $compvalue->shortname = $allcomps[$compid]->shortname;
                $results->compsvalues[$compid] = $compvalue;
            }
            $index++;
        }

        // Deal with cache.
        $isset = cacheutils::set('ue_vs_comp_pc', $hash, $results);
        // Deal with cache.
        return $results;
    }
}