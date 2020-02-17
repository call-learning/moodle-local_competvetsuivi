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
     * Get  progress vs possible value per strand for the selection of UEs
     *
     * @param $matrix
     * @param $currentcomp
     * @param $userdata
     * @param array $strands : array of comptype id
     * @return array
     */
    public static function get_comp_progress($matrix, $currentcomp, $userdata, $strands = array(), $ueselection = null) {

        // Deal with cache
        $hash = cacheutils::get_comp_progress_hash($matrix, $currentcomp, $userdata, $strands, $ueselection);
        $cachedvalue = cacheutils::get($hash, 'comp_progress');
        if ($cachedvalue) {
            return $cachedvalue;
        }
        // Deal with cache

        // For each competency regroup all finished ues and values
        $possiblevsactual = utils::get_possible_vs_actual_values($matrix, $currentcomp, $userdata, $ueselection, true);
        $progressperstrand = [];
        $maxperstrand = [];
        foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypeid => $comptypname) {
            if (key_exists($comptypeid, $possiblevsactual)
                    && (in_array($comptypeid, $strands)
                            || empty($strands))) {
                $progressperstrand[$comptypeid] = array_reduce($possiblevsactual[$comptypeid],
                        function($acc, $val) use ($comptypeid) {
                            return $acc + $val->possibleval * $val->userval; // Previous value
                        },
                        0);

                $maxperstrand[$comptypeid] = array_reduce($possiblevsactual[$comptypeid],
                        function($acc, $val) use ($comptypeid) {
                            return $acc + $val->possibleval; // Previous value
                        },
                        0);

            }
        }
        $returnvalue = array($progressperstrand, $maxperstrand);
        // Deal with cache
        $isset = cacheutils::set('comp_progress', $hash, $returnvalue);
        // Deal with cache
        return $returnvalue;
    }

    const INITIAL_SEMESTER = 5;

    /**
     * Get the progress in each UE/Semester and place markers accordingly
     * TODO : Implements Caching
     * @param $matrix
     * @param $comp
     * @param $strandlist
     * @param $userdata
     * @param $currentsemester
     * @param null $userselftestresults
     * @return array
     */
    public static function get_data_for_progressbar($matrix, $comp, $strandlist, $userdata, $currentsemester,
            $userselftestresults = null) {
        $alldata = [];

        // Init array
        $userprogress = array_fill_keys($strandlist, []);
        $maxprogress = array_fill_keys($strandlist, []);;
        $semestercount = ueutils::get_semester_count($matrix);
        // We get the cumulated progress for each semester (if they have any progress) with a marker for the maximum possible progress
        for ($semester = 1; $semester <= $semestercount; $semester++) {
            $ueselection = ueutils::get_ues_for_semester($semester, $matrix);
            list($progressspertrand, $maxperstrand) =
                    chartingutils::get_comp_progress($matrix, $comp, $userdata, $strandlist, $ueselection);
            foreach ($strandlist as $comptypeid) {
                $userprogress[$comptypeid][$semester] = $progressspertrand[$comptypeid];
                $maxprogress[$comptypeid][$semester] = $maxperstrand[$comptypeid];
            }
        }

        foreach ($strandlist as $comptypeid) {
            $data = new \stdClass();
            $data->markers = [];
            // Now we calculate the results per strand in percentage as well as the markers (semesters cumulated)
            $res = new \stdClass();
            $res->label = matrix::comptype_to_string($comptypeid);
            $res->type = $comptypeid;
            $maximumscore = array_sum($maxprogress[$comptypeid]);
            if ($maximumscore == 0) {
                $res->value = 0;
            } else {
                $res->value = array_sum($userprogress[$comptypeid]) / $maximumscore;
            }
            $data->result = $res;

            // Now the markers: we place them for each semester that have a max progress > 0 and it is cumulative
            // Here we will place the markers. Calculation of their position is 100% of the accumulated semester
            $data->markers = [];
            $accumulator = 0;
            $maximumprogress = array_sum($maxprogress[$comptypeid]);
            $semestercount = ueutils::get_semester_count($matrix);
            for ($semester = 1; $semester <= $semestercount; $semester++) {
                $accumulator += $maxprogress[$comptypeid][$semester];
                if ($maxprogress[$comptypeid][$semester]) {
                    $marker = new \stdClass();
                    $marker->label = intval($semester) + static::INITIAL_SEMESTER - 1;
                    $marker->value = $accumulator / $maximumprogress;
                    $marker->active = $semester > $currentsemester;
                    $data->markers[] = $marker;
                }
            }
            // Add self test results
            if ($userselftestresults
                    && key_exists($comptypeid, $userselftestresults)
                    && key_exists($comp->id, $userselftestresults[$comptypeid])) {
                $data->starmarkers = [$userselftestresults[$comptypeid][$comp->id]];
            }
            $alldata[] = $data;
        }

        return $alldata;
    }
}