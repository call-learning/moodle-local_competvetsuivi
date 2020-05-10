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
 * Matrix management add form
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
global $CFG;

/**
 * Add Form
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_assign_form extends moodleform {

    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $id = empty($this->_customdata['id']) ? '' : $this->_customdata['id'];
        if ($id) {
            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_INT);
        }
        $cohorts = $DB->get_records_menu('cohort', null, '', 'id, name');
        $element = $mform->addElement(
            'select',
            'matrixcohortsassignment',
            get_string('matrixcohortsassignment', 'local_competvetsuivi'),
            $cohorts);
        $element->setMultiple(true);
        $mform->addHelpButton('matrixcohortsassignment', 'matrixcohortsassignment', 'local_competvetsuivi');
        $mform->setType('matrixcohortsassignment', PARAM_RAW);

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        return $errors;
    }
}