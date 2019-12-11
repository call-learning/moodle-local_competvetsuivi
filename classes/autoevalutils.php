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
    public static function get_student_results($userid, $matrix, $questionbankcategorysn, $rootcomp = null) {
        global $CFG, $DB;
        include_once($CFG->dirroot . '/question/engine/lib.php');
        include_once($CFG->dirroot . '/mod/quiz/locallib.php'); // Yeah, if not quiz_attempt not defined
        // Get all relevant questions
        $params = array('competvetid' => $questionbankcategorysn);
        $sql = "SELECT "
                . $DB->sql_concat_join("'-'", ["q.id", "qc.id", "qs.id"])
                . " AS uniqueid, qs.quizid AS quizid FROM {question} q "
                . "LEFT JOIN {question_categories} qc ON qc.id = q.category "
                . "LEFT JOIN {quiz_slots} qs ON qs.questionid = q.id "
                . "WHERE qc.idnumber=:competvetid";

        $allquestions = $DB->get_records_sql($sql, $params);

        $sqlallcomps = 'SELECT c.shortname, c.id FROM {cvs_matrix_comp} AS c';
        $sqlallcomplike = "";
        $sqlallcompparams = array('matrixid' => $matrix->id);
        if ($rootcomp) {
            $sqlallcomplike = "WHERE " . $DB->sql_like('c.path', ':rootpathcheck')
                    . " OR c.path=:rootpath";
            $sqlallcompparams['rootpathcheck'] = "%/{$rootcomp->id}/%";
            $sqlallcompparams['rootpath'] = "/{$rootcomp->id}";
        }
        $sqlallcomporder = ' ORDER BY id ASC';
        $allcompetenciesmatch = $DB->get_records_sql_menu(
                "$sqlallcomps $sqlallcomplike $sqlallcomporder",
                $sqlallcompparams
        );

        $dm = new \question_engine_data_mapper();
        $questionresults = [];
        foreach ($allquestions as $qs) {
            $qubas = $dm->load_questions_usages_by_activity(
                    new qubaids_for_users_attempts($qs->quizid, $userid));
            foreach ($qubas as $quba) {
                foreach ($quba->get_attempt_iterator() as $qa) {
                    $question = $qa->get_question();
                    $markfract = $qa->get_fraction(); // Question fraction is the percentage for this question
                    $coef = $qa->get_max_mark(); // This is really the question weight, not the max, the max mark is
                    // obtained using max_fraction/min_fraction
                    $minmark = $qa->get_min_fraction();
                    $maxmark = $qa->get_max_fraction();
                    $markfraction = ($markfract - $minmark) / ($maxmark - $minmark) * $coef;
                    $questionsn = trim(strtoupper(trim($question->name)), '.');
                    if (key_exists($questionsn, $allcompetenciesmatch)) {
                        $questionid = $allcompetenciesmatch[$questionsn]; // The key is now the competency id
                        if (empty($questionresults[$questionid])) {
                            $questionresults[$questionid] = $markfraction;
                        } else {
                            $questionresults[$questionid] = max($markfraction, $questionresults[$questionid]);
                        }
                    }
                }
            }
        }

        // Now make sure that for each competency we check the subcompetencies for results
        foreach ($matrix->get_child_competencies(0, true) as $cmp) {
            static::compute_results_recursively($questionresults, $matrix, $cmp);
        }

        ksort($questionresults); // Sort it so it is easier to get the corresponding competencies
        return $questionresults;
    }

    static private function compute_results_recursively(&$currentresultarray, $matrix, $currentcomp) {
        $compresult = [];
        foreach ($matrix->get_child_competencies($currentcomp->id) as $cmp) {
            $compmean = static::compute_results_recursively($currentresultarray, $matrix, $cmp);
            if ($compmean >= 0) {
                $compresult[] = $compmean;
            }
        }
        if (count($compresult)) {
            $meanvalue = array_sum($compresult) / count($compresult);
            $currentresultarray[$currentcomp->id] = $meanvalue;
        } else {
            $meanvalue = -1; // "Do not exist" value, not taken into account
            if (key_exists($currentcomp->id, $currentresultarray)) {
                $meanvalue = $currentresultarray[$currentcomp->id];
            }
        }
        return $meanvalue;
    }
}