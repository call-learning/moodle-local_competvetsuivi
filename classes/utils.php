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

class utils {
    public static function get_possible_vs_actual_values(matrix $matrix, $comp, $userdata, $ueselection = null,
            $recursive = false) {
        if (!$ueselection) {
            $matrixues = $matrix->get_matrix_ues();
        } else {
            $matrixues = $ueselection;
        }
        $possiblevsactual = array();
        foreach ($matrixues as $ue) {
            $values = $matrix->get_values_for_ue_and_competency($ue->id, $comp->id, $recursive);

            foreach ($values as $ueval) {
                $data = new \stdClass();
                $data->possibleval = $ueval->value;
                $data->userval = 0;
                $data->ue = $ue->shortname;
                $data->ueid = $ue->id;
                if (!empty($userdata[$ue->shortname])) {
                    $data->userval = $userdata[$ue->shortname];
                }
                if (empty($possiblevsactual[$ueval->type])) {
                    $possiblevsactual[$ueval->type] = array();
                }
                $possiblevsactual[$ueval->type][] = $data;
            }
        }
        return $possiblevsactual;
    }

    /**
     * Get used matrix for user
     * We take the first one in the list but really we should throw an error or a warning
     * TODO: Alert admin when user are assigned to several cohorts
     * @param $userid
     */
    public static function get_matrixid_for_user($userid) {
        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');
        $cohorts = cohort_get_user_cohorts($userid);
        $matrixid = 0;
        if ($cohorts) {
            global $DB;
            $cohort = reset($cohorts);
            $matrixid = $DB->get_field('cvs_matrix_cohorts','matrixid',array('cohortid'=>$cohort->id));
        }
        return $matrixid;
    }
}