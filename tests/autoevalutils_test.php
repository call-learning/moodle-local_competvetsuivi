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
 * File containing tests for autoevalutils_test.
 *
 * @package     local_competvetsuivi
 * @category    test
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\autoevalutils;

defined('MOODLE_INTERNAL') || die();

// For installation and usage of PHPUnit within Moodle please read:
// https://docs.moodle.org/dev/PHPUnit
//
// Documentation for writing PHPUnit tests for Moodle can be found here:
// https://docs.moodle.org/dev/PHPUnit_integration
// https://docs.moodle.org/dev/Writing_PHPUnit_tests
//
// The official PHPUnit homepage is at:
// https://phpunit.de .

require_once(__DIR__ . '/lib.php');
global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * The autoevalutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoevalutils_test extends competvetsuivi_tests {
    /**
     * @var array $courses sample courses
     */
    protected $courses = [];
    /**
     * @var array $courses sample quiz
     */
    protected $quizzes = [];
    /**
     * @var array $courses sample questions
     */
    protected $questions = [];

    /**
     * Number of question per quiz
     */
    const COURSE_QUIZ_NB = 2;
    /**
     * Responses to the questions quiz.
     */
    const QBANK_QUESTION_COMP = ['COPREV' => 'One',
        'COPREV.1.1' => 'Two',
        'COPREV.1.2' => 'Four',
        'COPREV.2' => 'Two',
        'COPREV.2.1' => 'Three',
        'COPREV.2.2' => 'Three',
        'COPREV.3.1' => 'Two',
        'COPREV.3.4' => 'Four',
    ];
    /**
     * Possible answers
     */
    const QUESTION_POSSIBLE_ANSWERS = array('One' => '1', 'Two' => '0.75', 'Three' => '0.5', 'Four' => '0.25', 'Five' => '0');

    /**
     * Get sample question data
     *
     * @param \stdClass $competency
     * @return stdClass
     */
    protected static function get_mc_question_data($competency) {
        global $USER;
        $qdata = new stdClass();

        $qdata->name = $competency->shortname;
        $qdata->questiontext = array('text' => "Question for {$competency->shortname}", 'format' => FORMAT_HTML);
        $qdata->generalfeedback = array('text' => "Question for {$competency->shortname} answered.", 'format' => FORMAT_HTML);
        $qdata->defaultmark = 1;
        $qdata->noanswers = 5;
        $qdata->numhints = 2;
        $qdata->penalty = 0.3333333;

        $qdata->shuffleanswers = 1;
        $qdata->answernumbering = '123';
        $qdata->single = '1';
        $qdata->correctfeedback = array('text' => test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK,
            'format' => FORMAT_HTML);
        $qdata->partiallycorrectfeedback = array('text' => test_question_maker::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK,
            'format' => FORMAT_HTML);
        $qdata->shownumcorrect = 1;
        $qdata->incorrectfeedback = array('text' => test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK,
            'format' => FORMAT_HTML);
        $qdata->fraction = array_values(self::QUESTION_POSSIBLE_ANSWERS);
        $qdata->answer = array(
            0 => array(
                'text' => 'One',
                'format' => FORMAT_PLAIN
            ),
            1 => array(
                'text' => 'Two',
                'format' => FORMAT_PLAIN
            ),
            2 => array(
                'text' => 'Three',
                'format' => FORMAT_PLAIN
            ),
            3 => array(
                'text' => 'Four',
                'format' => FORMAT_PLAIN
            ),
            4 => array(
                'text' => 'Five',
                'format' => FORMAT_PLAIN
            )
        );

        $qdata->feedback = array(
            0 => array(
                'text' => 'One is odd.',
                'format' => FORMAT_HTML
            ),
            1 => array(
                'text' => 'Two is even.',
                'format' => FORMAT_HTML
            ),
            2 => array(
                'text' => 'Three is odd.',
                'format' => FORMAT_HTML
            ),
            3 => array(
                'text' => 'Four is even.',
                'format' => FORMAT_HTML
            ),
            4 => array(
                'text' => '',
                'format' => FORMAT_HTML
            )
        );

        $qdata->hint = array(
            0 => array(
                'text' => 'Hint 1.',
                'format' => FORMAT_HTML
            ),
            1 => array(
                'text' => 'Hint 2.',
                'format' => FORMAT_HTML
            )
        );
        $qdata->hintclearwrong = array(0, 1);
        $qdata->hintshownumcorrect = array(1, 1);

        return $qdata;
    }

    /**
     * Create question
     *
     * @param \stdClass $questiongenerator
     * @param \stdClass $competency
     * @param int $categoryid
     * @return object
     * @throws coding_exception
     */
    protected function create_question($questiongenerator, $competency, $categoryid) {
        $fromform = $this->get_mc_question_data($competency);

        $question = new stdClass();
        $question->category = $categoryid;
        $question->qtype = 'multichoice';
        $question->createdby = 0;
        $fromform->category = $categoryid;

        // See the function call $questiongenerator->update_question($question).

        $qtype = $question->qtype;
        $question = question_bank::get_qtype($qtype)->save_question($question, $fromform);

        return $question;
    }

    /**
     * Setup the test
     * @throws \local_competvetsuivi\matrix\matrix_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function setUp() {
        global $DB;
        global $CFG;
        parent::setUp();

        $generator = $this->getDataGenerator();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        // Create a course and a quiz.
        for ($i = 0; $i < self::COURSE_QUIZ_NB; $i++) {
            $course = $generator->create_course();
            $this->course[] = $course;
            $quiz = $quizgenerator->create_instance(array('course' => $course->id,
                'questionsperpage' => 0,
                'grade' => 100.0,
                'sumgrades' => 2));
            $this->quizzes[] = $quiz;
        }

        // Create a specific question bank category.

        $category = $questiongenerator->create_question_category(array('name' =>
            local_competvetsuivi\utils::get_default_question_bank_category_name()));

        // Create all questions now.

        foreach (self::QBANK_QUESTION_COMP as $compname => $result) {
            $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', $compname);
            $questiondata = $this->create_question($questiongenerator, $comp, $category->id);
            $this->questions[$compname] = $questiondata;
        }

        foreach ($this->quizzes as $quiz) {
            foreach (self::QBANK_QUESTION_COMP as $compname => $result) {
                quiz_add_quiz_question($this->questions[$compname]->id, $quiz);
            }
        }
        // Note for numerical question results see qtype_multichoice_test_helper : 100 <=> 3.14, rest if 0%.

        // Next start the quiz (see mod/qui/test/attempt_walkthrough_test.

        foreach ($this->quizzes as $qid => $q) {
            $quizobj = quiz::create($q->id, $this->user->id);
            $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
            $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
            $timenow = time();
            $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $this->user->id);
            quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
            quiz_attempt_save_started($quizobj, $quba, $attempt);
            // Process some responses from the student.
            $attemptobj = quiz_attempt::create($attempt->id);
            $tosubmit = [];
            foreach ($attemptobj->get_slots() as $slot) {
                $cquestion = $quba->get_question($slot);
                if (key_exists($cquestion->name, self::QBANK_QUESTION_COMP)) {
                    $tosubmit[$slot] = array('answer' => self::QBANK_QUESTION_COMP[$cquestion->name]);
                }
                // Just an exception for COPREV3.4, we have two different answers so we can check
                // if we take the max.
                if ($cquestion->name == 'COPREV.3.4' && $qid % 2) {
                    $tosubmit[$slot] = array('answer' => 'Two');
                }
            }
            $attemptobj->process_submitted_actions($timenow, false, $tosubmit);
            // Finish the attempt.
            $attemptobj = quiz_attempt::create($attempt->id);
            $attemptobj->process_finish($timenow, false);
        }
    }

    public function test_get_all_question_from_qbank_category() {
        $this->resetAfterTest();
        $allquestions = autoevalutils::get_all_question_from_qbank_category($this->matrix);
        // We have two quiz with the same questions, so it will be 10.
        $this->assertCount(count(self::QBANK_QUESTION_COMP) * 2, $allquestions);
    }

    public function test_get_all_competency_association() {
        $this->resetAfterTest();
        $allcomps = autoevalutils::get_all_competency_association($this->matrix, null);
        $compresult = array(
            'COPREV',
            'COPREV.1',
            'COPREV.1.1',
            'COPREV.1.1BIS',
            'COPREV.1.2',
            'COPREV.1.3',
            'COPREV.1.4',
            'COPREV.2',
            'COPREV.2.1',
            'COPREV.2.2',
            'COPREV.2.3',
            'COPREV.2.3BIS',
            'COPREV.2.4',
            'COPREV.2.5',
            'COPREV.2.6',
            'COPREV.2.7',
            'COPREV.2.7BIS',
            'COPREV.2.8',
            'COPREV.3',
            'COPREV.3.1',
            'COPREV.3.2',
            'COPREV.3.3',
            'COPREV.3.4'
        );
        $this->assertArraySubset($compresult, array_keys($allcomps));

        $comp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $allcomps = autoevalutils::get_all_competency_association($this->matrix, $comp2);
        $compresult = array(
            'COPREV.2',
            'COPREV.2.1',
            'COPREV.2.2',
            'COPREV.2.3',
            'COPREV.2.3BIS',
            'COPREV.2.4',
            'COPREV.2.5',
            'COPREV.2.6',
            'COPREV.2.7',
            'COPREV.2.7BIS',
            'COPREV.2.8'
        );
        $this->assertArraySubset($compresult, array_keys($allcomps));
    }

    public function test_get_question_mark() {
        $this->resetAfterTest();

        $allquiz = array_map(function($q) {
            return $q->id;
        }, $this->quizzes);

        $attempts = quiz_get_user_attempts($allquiz, $this->user->id);
        foreach ($attempts as $a) {
            $attemptobj = quiz_attempt::create($a->id);
            foreach ($attemptobj->get_slots() as $slot) {
                $qa = $attemptobj->get_question_attempt($slot);
                $currentresponse = $qa->get_response_summary();
                $expectedmark = 0; // No answer.
                if (key_exists($currentresponse, self::QUESTION_POSSIBLE_ANSWERS)) {
                    $expectedmark = self::QUESTION_POSSIBLE_ANSWERS[$qa->get_response_summary()];
                }
                $mark = autoevalutils::get_question_mark($qa);
                $this->assertEquals($expectedmark, $mark, "Expected that $expectedmark is equal to $mark.");
            }
        }
    }

    public function test_compute_results_recursively_mean() {
        $this->resetAfterTest();
        $rootcompetency = $this->matrix->get_root_competency();
        $coprev34 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.4');
        $coprev31 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.1');
        $coprev3 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3');
        $questionresults = array(
            $coprev34->id => 0.75,
            $coprev31->id => 0.25,
        );
        autoevalutils::compute_results_recursively($questionresults, $this->matrix, $rootcompetency);
        $this->assertEquals(0.5, $questionresults[$coprev3->id]); // Mean.
        $this->assertEquals(0.75, $questionresults[$coprev34->id]);
        $this->assertEquals(0.25, $questionresults[$coprev31->id]);

    }

    public function test_compute_results_recursively_override() {
        $this->resetAfterTest();
        $rootcompetency = $this->matrix->get_root_competency();
        $coprev34 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.4');
        $coprev31 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.1');
        $coprev3 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3');
        $questionresults = array(
            $coprev34->id => 0.75,
            $coprev31->id => 0.25,
            $coprev3->id => 0.25,
        );
        autoevalutils::compute_results_recursively($questionresults, $this->matrix, $rootcompetency);
        $this->assertEquals(0.25, $questionresults[$coprev3->id]);

    }

    public function test_get_student_results_simple() {
        $this->resetAfterTest();
        // Easy use case: we look at COPREV.3.1 =>  0.75.
        $comp3 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.1');

        // Here we look at the root competency.
        $resultforallcomps =
            autoevalutils::get_student_results(
                $this->user->id,
                $this->matrix,
                $comp3);

        $this->assertEquals(0.75, $resultforallcomps[$comp3->id]);
    }

    public function test_get_student_results_aggregated_override() {
        $this->resetAfterTest();
        $comp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $resultforallcomps =
            autoevalutils::get_student_results(
                $this->user->id,
                $this->matrix,
                $comp2);
        $this->assertEquals(0.75, $resultforallcomps[$comp2->id]); // Here the result below are overriden.
    }

    public function test_get_student_results_aggregated_mean() {
        $this->resetAfterTest();
        $comp1 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.1');
        $resultforallcomps =
            autoevalutils::get_student_results(
                $this->user->id,
                $this->matrix);
        $this->assertEquals(0.5, $resultforallcomps[$comp1->id]); // Here the result below are the mean of
        // Sub competencies.
    }

    public function test_get_student_results_check_max_grade() {
        $this->resetAfterTest();
        // Check we take the max grade for this student (there are two answers : 0.75 and 0.25.
        $comp = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.3.4');
        $resultforallcomps =
            autoevalutils::get_student_results(
                $this->user->id,
                $this->matrix);
        $this->assertEquals(0.75, $resultforallcomps[$comp->id]); // Here the result below are the mean of
        // Sub competencies.
    }
}

