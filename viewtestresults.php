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
 * Test result view page
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\chartingutils;
use local_competvetsuivi\matrix\matrix_list_renderable;
use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use local_competvetsuivi\utils;

require_once(__DIR__ . '/../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_login();

$userid = optional_param('userid', 0,PARAM_INT);
$matrixid = optional_param('matrixid', 0,PARAM_INT);
$compidparamname = local_competvetsuivi\renderable\competency_progress_overview::PARAM_COMPID;
$currentcompid = optional_param($compidparamname, false, PARAM_INT);
$userid = $userid ? $userid : $USER->id;
$user = \core_user::get_user($userid);

if(!$matrixid) {
    $matrixid = utils::get_matrixid_for_user($user->id);
    if ($matrixid) {
        print_error('nocohortforuser');
    }
}
$matrix = new \local_competvetsuivi\matrix\matrix($matrixid);

// Override pagetype to show blocks properly.
$header = get_string('matrix:viewtestresults',
        'local_competvetsuivi');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/viewtestresults.php');
$PAGE->set_url($pageurl);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('usertestresults', 'local_competvetsuivi',
        array('matrixname' => $matrix->shortname, 'username' => fullname($user))), 3);

include_once ($CFG->dirroot.'/question/engine/lib.php');
include_once ($CFG->dirroot.'/mod/quiz/locallib.php'); // Yeah, if not quiz_attempt not defined
// Get all relevant questions
$params = array('competvetid'=> $CFG->questionbankcategory);
$sql = "SELECT qs.* FROM {question} q 
    LEFT JOIN {question_categories} qc ON qc.id = q.category
    LEFT JOIN {quiz_slots} qs ON qs.questionid = q.id 
    WHERE qc.idnumber=:competvetid";

$allquestions = $DB->get_records_sql($sql, $params);
$dm = new \question_engine_data_mapper();

$questionresults = [];
foreach ($allquestions as $qs) {
    $qubas = $dm->load_questions_usages_by_activity(
            new \mod_quiz\question\qubaids_for_users_attempts($qs->quizid, $user->id));
    foreach ($qubas as $quba) {
        foreach ($quba->get_attempt_iterator() as $qa) {
            $question = $qa->get_question();
            $mark =  $qa->get_mark();
            $markfract = $qa->get_fraction(); // Question fraction is the percentage for this question
            $coef =  $qa->get_max_mark(); // This is really the question weight, not the max, the max mark is
            // obtained using max_fraction/min_fraction
            $minmark = $qa->get_min_fraction();
            $maxmark = $qa->get_max_fraction();
            $markfraction = ($markfract - $minmark)/($maxmark-$minmark) * $coef;
            if (empty($questionresults[$question->idnumber])) {
                $questionresults[$question->name] = $markfraction;
            } else {
                $questionresults[$question->name] = max($markfraction, $questionresults[$question->idnumber]);
            }
        }
    }
}
var_dump($questionresults);
// http://competvetsuivi.local/local/competvetsuivi/viewtestresults.php?userid=3&matrixid=4
echo $OUTPUT->footer();
