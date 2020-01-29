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
 * Auto evaluation utils
 *
 * @package     local_competvetsuivi
 * @category    Autoeval utils
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

use local_competvetsuivi\matrix\matrix;
use mod_quiz\question\qubaids_for_users_attempts;

class autoevalutils {

    /**
     * Get all question from a specific category shortname. The shortname is trimmed and put to upper
     * case letter before comparison
     *
     * @param $questionbankcategorysn
     * @return array
     * @throws \dml_exception
     */
    public static function get_all_question_from_qbank_category($matrix) {
        global $DB;

        // $matrixsnkey = trim(strtoupper($matrix->shortname));
        $defaultcategoryname = utils::get_default_question_bank_category_name();
        // Get all relevant questions
        $params = array('defaultbanksn'=>$defaultcategoryname);
        $sql = "SELECT "
                . $DB->sql_concat_join("'-'", ["q.id", "qc.id", "qs.id"])
                . " AS uniqueid, qs.quizid AS quizid, qs.questionid AS questionid FROM {question} q "
                . "LEFT JOIN {question_categories} qc ON qc.id = q.category "
                . "LEFT JOIN {quiz_slots} qs ON qs.questionid = q.id "
                . "WHERE UPPER(qc.idnumber)=:defaultbanksn";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Return an associative array which can be used to match competency shortname with their respective IDs
     *
     * @param $matrix
     * @param $rootcomp
     * @return array
     */
    public static function get_all_competency_association($matrix, $rootcomp) {
        /** @var  matrix $matrix */
        $complist = $matrix->get_matrix_competencies();
        $compassociation = [];
        if ($rootcomp) {
            $compassociation[$rootcomp->shortname] = intval($rootcomp->id); // We add the root competency to the set
        }
        $currentpath = $rootcomp ? $rootcomp->path . '/' : '/';
        foreach ($complist as $cid => $cmp) {
            if (strpos($cmp->path, $currentpath) === 0) {
                $compassociation[$cmp->shortname] = $cid;
            }
        }
        return $compassociation;
    }

    public static function get_question_mark($qa) {
        $markfract = $qa->get_fraction(); // Question fraction is the percentage for this question
        $coef = $qa->get_max_mark(); // This is really the question weight, not the max, the max mark is
        // obtained using max_fraction/min_fraction
        $minmark = $qa->get_min_fraction();
        $maxmark = $qa->get_max_fraction();
        return ($markfract - $minmark) / ($maxmark - $minmark) * $coef;
    }

    /**
     * Get student results
     * This will compute the result for each competency in the matrix. We compute the average result of the
     * children competencies. If the question is asked twice we take the best result.
     * TODO : Implements Caching
     *
     * @param $userid
     * @param $matrix
     * @param null $rootcomp
     * @return array An array indexed by competency id and the numerical result
     * @throws \dml_exception
     */
    public static function get_student_results($userid, $matrix, $rootcomp = null) {
        global $CFG, $DB;
        include_once($CFG->dirroot . '/question/engine/lib.php');
        include_once($CFG->dirroot . '/mod/quiz/locallib.php'); // Yeah, if not quiz_attempt not defined

        $allquestions = static::get_all_question_from_qbank_category($matrix);
        $allquestionsid = array_map(function($q) {
            return $q->questionid;
        }, $allquestions);

        $allcompetenciesmatch = static::get_all_competency_association($matrix, $rootcomp);

        $dm = new \question_engine_data_mapper();
        $questionresults = [];
        $allquizid = array_reduce(
                $allquestions,
                function($carry, $item) {
                    if (!in_array($item->quizid, $carry)) {
                        $carry[] = $item->quizid;
                    }
                    return $carry;
                }, []);
        foreach ($allquizid as $qid) {
            $qubas = $dm->load_questions_usages_by_activity(
                    new qubaids_for_users_attempts($qid, $userid));
            foreach ($qubas as $quba) {
                foreach ($quba->get_attempt_iterator() as $qa) {
                    $question = $qa->get_question();
                    if (in_array($question->id, $allquestionsid)) {
                        $questionsn = trim(strtoupper(trim($question->idnumber)), '.');
                        if (key_exists($questionsn, $allcompetenciesmatch)) {
                            $competencyid = $allcompetenciesmatch[$questionsn]; // The key is now the competency id
                            $qmark = static::get_question_mark($qa);
                            $questionresults[$competencyid] = key_exists($competencyid, $questionresults) ?
                                    max($qmark, $questionresults[$competencyid]) : $qmark;
                        }
                    }
                }
            }
        }

        // Now make sure that for each competency we check the subcompetencies for results
        /** @var $matrix \local_competvetsuivi\matrix\matrix */

        $rootcompetency = $matrix->get_root_competency();
        static::compute_results_recursively($questionresults, $matrix, $rootcompetency);

        ksort($questionresults); // Sort it so it is easier to get the corresponding competencies
        return $questionresults;
    }

    /**
     * Compute the results recursively. If a result already exists, we keep it as is.
     *
     * @param $currentresultarray
     * @param $matrix
     * @param $currentcomp
     * @return float|int|mixed
     */
    public static function compute_results_recursively(&$currentresultarray, $matrix, $currentcomp) {
        $compresult = [];
        /** @var $matrix matrix */
        foreach ($matrix->get_child_competencies($currentcomp->id, true) as $cmp) {
            $compmean = static::compute_results_recursively($currentresultarray, $matrix, $cmp);
            if ($compmean >= 0) {
                $compresult[] = $compmean;
            }
        }
        $meanvalue = -1; // "Do not exist" value, not taken into account
        if (key_exists($currentcomp->id, $currentresultarray)) {  // If the value already exist, don't touch it
            $meanvalue = $currentresultarray[$currentcomp->id];
        } else if (count($compresult)) {
            $meanvalue = array_sum($compresult) / count($compresult);
            $currentresultarray[$currentcomp->id] = $meanvalue;
        }
        return $meanvalue;
    }
}