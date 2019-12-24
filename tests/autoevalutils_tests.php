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

defined('MOODLE_INTERNAL') || die();

// For installation and usage of PHPUnit within Moodle please read:
// https://docs.moodle.org/dev/PHPUnit
//
// Documentation for writing PHPUnit tests for Moodle can be found here:
// https://docs.moodle.org/dev/PHPUnit_integration
// https://docs.moodle.org/dev/Writing_PHPUnit_tests
//
// The official PHPUnit homepage is at:
// https://phpunit.de

include('lib.php');

/**
 * The autoevalutils_test test class.
 *
 * @package    local_competvetsuivi
 * @copyright  2019 CALL Learning <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoevalutils_tests extends competvetsuivi_tests {
    protected $courses = [];
    protected $quizzes = [];
    protected $questions = [];
    protected $matrix = null;

    const COURSE_QUIZ_NB = 2;
    const QBANK_QUESTION_NB = 4;
    const QBANK_CATEGORY_ID = 'qbankcat';

    protected static function make_a_quiz_question($competency) {
        global $USER;

        question_bank::load_question_definition_classes('multichoice');
        $mc = new qtype_multichoice_multi_question();
        $mc->id = 0;
        $mc->category = 0;
        $mc->idnumber = $competency->shortname;
        $mc->parent = 0;
        $mc->questiontextformat = FORMAT_HTML;
        $mc->generalfeedbackformat = FORMAT_HTML;
        $mc->defaultmark = 1;
        $mc->penalty = 0.3333333;
        $mc->length = 1;
        $mc->stamp = make_unique_id_code();
        $mc->version = make_unique_id_code();
        $mc->hidden = 0;
        $mc->timecreated = time();
        $mc->timemodified = time();
        $mc->createdby = $USER->id;
        $mc->modifiedby = $USER->id;
        $mc->name = 'Multi-choice question, single response';
        $mc->questiontext = "Competency {$competency->shortname}";
        $mc->generalfeedback = '';
        $mc->qtype = question_bank::get_qtype('multichoice');

        $mc->shuffleanswers = 0;
        $mc->answernumbering = 'abc';

        $mc->correctfeedback = self::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $mc->correctfeedbackformat = FORMAT_HTML;
        $mc->partiallycorrectfeedback = self::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK;
        $mc->partiallycorrectfeedbackformat = FORMAT_HTML;
        $mc->shownumcorrect = true;
        $mc->incorrectfeedback = self::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $mc->incorrectfeedbackformat = FORMAT_HTML;

        $mc->answers = array(
                13 => new question_answer(13, 'A', 0, '', FORMAT_HTML),
                14 => new question_answer(14, 'B', 0.25, '', FORMAT_HTML),
                15 => new question_answer(15, 'C', 0.5, '', FORMAT_HTML),
                16 => new question_answer(16, 'D', 1, '', FORMAT_HTML),
        );

        return $mc;
    }

    public function setUp() {
        global $DB;
        parent::setUp();

        $generator = $this->getDataGenerator();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');

        // Create a course and a quiz.
        for ($i = 0; $i < self::COURSE_QUIZ_NB; $i++) {
            $course = $generator->create_course();
            $this->course[] = $course;
            $qiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));
            $this->quizzes[] = $qiz;
        }

        // Create a specific question bank category

        $category = $questiongenerator->create_question_category(array('name' => 'Question CATEGORY',
                'idnumber' => self::QBANK_CATEGORY_ID));

        // Create all questions now
        $matrixid = $DB->get_field('cvs_matrix', 'id', array('shortname' => 'MATRIX1'));
        $matrix = new local_competvetsuivi\matrix\matrix($matrixid);
        $matrix->load_data();
        $this->matrix = $matrix;
        $cache = cache::make('core', 'questiondata');
        for ($i = 0; $i < self::QBANK_QUESTION_NB; $i++) {
            $questiondata = $questiongenerator->create_question('multichoice', null,
                    ['name' => 'Example question', 'category' => $category->id]);
            $cache->delete($questiondata->id);
            quiz_add_quiz_question($questiondata->id, $this->quizzes[$i % 2]);
            $this->questions[] = $questiondata;
        }

        // Note for numerical question results see qtype_multichoice_test_helper : 100 <=> 3.14, rest if 0%
    }

    public function test_get_all_question_from_qbank_category() {
        $this->resetAfterTest();
        $allquestions = \local_competvetsuivi\autoevalutils::get_all_question_from_qbank_category(
                self::QBANK_CATEGORY_ID
        );
        $this->assertCount(4, $allquestions);
    }

    public function test_get_student_results() {
        $this->resetAfterTest();
        $allcomps = \local_competvetsuivi\autoevalutils::get_all_competency_association($this->matrix, null);
        $compresult = array (
                'COPREV' => '276000',
                'COPREV.1' => '276001',
                'COPREV.1.1' => '276002',
                'COPREV.1.1BIS' => '276003',
                'COPREV.1.2' => '276004',
                'COPREV.1.3' => '276005',
                'COPREV.1.4' => '276006',
                'COPREV.2' => '276007',
                'COPREV.2.1' => '276008',
                'COPREV.2.2' => '276009',
                'COPREV.2.3' => '276010',
                'COPREV.2.3BIS' => '276011',
                'COPREV.2.4' => '276012',
                'COPREV.2.5' => '276013',
                'COPREV.2.6' => '276014',
                'COPREV.2.7' => '276015',
                'COPREV.2.7BIS' => '276016',
                'COPREV.2.8' => '276017',
                'COPREV.3' => '276018',
                'COPREV.3.1' => '276019',
                'COPREV.3.2' => '276020',
                'COPREV.3.3' => '276021',
                'COPREV.3.4' => '276022',
        );
        $this->assertEquals($compresult, $allcomps);

        $comp2 = $this->matrix->get_matrix_comp_by_criteria('shortname', 'COPREV.2');
        $allcomps = \local_competvetsuivi\autoevalutils::get_all_competency_association($this->matrix, $comp2);
        $compresult = array (
                'COPREV.2' => '276007',
                'COPREV.2.1' => '276008',
                'COPREV.2.2' => '276009',
                'COPREV.2.3' => '276010',
                'COPREV.2.3BIS' => '276011',
                'COPREV.2.4' => '276012',
                'COPREV.2.5' => '276013',
                'COPREV.2.6' => '276014',
                'COPREV.2.7' => '276015',
                'COPREV.2.7BIS' => '276016',
                'COPREV.2.8' => '276017',
        );
        $this->assertEquals($compresult, $allcomps);
    }

}

