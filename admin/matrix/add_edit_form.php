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

require_once(__DIR__ . '/../../../../config.php');

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
global $CFG;

/**
 * Add Form
 *
 * @package local_competvetsuivi
 */
class add_edit_form extends moodleform {

    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $fullname = empty($this->_customdata['fullname']) ? '' : $this->_customdata['fullname'];
        $shortname = empty($this->_customdata['shortname']) ? '' : $this->_customdata['shortname'];
        $id = empty($this->_customdata['id']) ? '' : $this->_customdata['id'];
        if ($id) {
            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('text', 'fullname', get_string('matrixname', 'local_competvetsuivi'), $fullname);
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('matrixshortname', 'local_competvetsuivi'), $shortname);
        $mform->setType('shortname', PARAM_TEXT);

        $mform->addElement(
                'filepicker',
                'matrixfile',
                get_string('matrixfileadd', 'local_competvetsuivi'),
                null,
                array('accepted_types' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))); // See lib/classes/filetypes.php
        $mform->addHelpButton('matrixfile', 'matrixfileadd', 'local_competvetsuivi');
        $mform->setType('matrixfile', PARAM_FILE);

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