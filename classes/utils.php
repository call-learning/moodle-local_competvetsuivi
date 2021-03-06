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
 * Class utils
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Get possible vs actual value
     *
     * @param matrix $matrix
     * @param \stdClass $comp
     * @param \stdClass $userdata
     * @param null $ueselection
     * @param bool $recursive
     * @return array
     * @throws matrix\matrix_exception
     */
    public static function get_possible_vs_actual_values(matrix $matrix, $comp, $userdata, $ueselection = null,
            $recursive = false) {
        if (!$ueselection) {
            $matrixues = $matrix->get_matrix_ues();
        } else {
            $matrixues = $ueselection;
        }
        $possiblevsactual = array();
        foreach ($matrixues as $ue) {
            $values = $matrix->get_total_values_for_ue_and_competency($ue->id, $comp->id, $recursive);

            foreach ($values as $ueval) {
                $data = new \stdClass();
                $data->possibleval = $ueval->totalvalue;
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
     *
     * @param int $userid
     * @return matrix id of the first matching matrix or false if not found
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_matrixid_for_user($userid) {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $cohorts = cohort_get_user_cohorts($userid);
        $matrixid = false;
        if ($cohorts) {
            global $DB;
            $cohortsid = array_map(function($c) {
                return $c->id;
            }, $cohorts);
            list($insql, $inparams) = $DB->get_in_or_equal($cohortsid);
            $matrixid = $DB->get_field_sql('SELECT matrixid FROM {cvs_matrix_cohorts} WHERE cohortid ' . $insql . ' LIMIT 1',
                    $inparams);

            $matrixexists = $DB->record_exists('cvs_matrix', ['id' => $matrixid]);
            if (!$matrixexists) {
                $matrixid = false;
            }
        }

        return $matrixid;
    }

    /**
     * Assign a cohort to a matrix if it is not already assigned to
     *
     * @param int $matrixid
     * @param int $cohortid
     * @throws \dml_exception
     */
    public static function assign_matrix_cohort($matrixid, $cohortid) {
        global $DB;
        $assignment = new \stdClass();
        $assignment->matrixid = $matrixid;
        $assignment->cohortid = $cohortid;
        if (!$DB->record_exists('cvs_matrix_cohorts', array('matrixid' => $matrixid, 'cohortid' => $cohortid))) {
            $DB->insert_record('cvs_matrix_cohorts', $assignment);
        }
    }

    /**
     * Default shortname for question bank
     */
    const DEFAULT_QUESTION_BANK_CATEGORY_SN = 'Auto-evaluation_competences';

    /**
     * Get question bank category name
     *
     * @return bool|mixed|object|string
     * @throws \dml_exception
     */
    public static function get_default_question_bank_category_name() {
        global $CFG;
        $globalcfg = get_config('local_competvetsuivi', 'cvsquestionbankdefaultcategoryname');
        if (!$globalcfg) {
            $globalcfg = empty($CFG->cvsquestionbankdefaultcategoryname) ? static::DEFAULT_QUESTION_BANK_CATEGORY_SN
                    : $CFG->cvsquestionbankdefaultcategoryname;
        }
        return $globalcfg;
    }
}