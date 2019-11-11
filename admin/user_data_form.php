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

require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
global $CFG;

/**
 * Add Form
 *
 * @package local_competvetsuivi
 */
class user_data_form extends moodleform {

    const DEFAULT_USERDATA_DIR = '/tmp/usermatrix/';

    /**
     * The form definition.
     */
    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $mform->addElement('text',
                'userdatafilepath',
                get_string('userdatafilepath', 'local_competvetsuivi'));

        $mform->addHelpButton('userdatafilepath', 'userdatafilepath', 'local_competvetsuivi');
        $mform->setType('userdatafilepath', PARAM_RAW);
        $mform->setDefault('userdatafilepath', static::DEFAULT_USERDATA_DIR);

        $instructions = get_string('userdatadirectupload', 'local_competvetsuivi');
        $mform->addElement('static', '', \html_writer::div($instructions));

        $mform->addElement('filepicker',
                'filetoupload',
                get_string('userdatafile', 'local_competvetsuivi'),
                '',
                array('accepted_types' => array('text/csv'))); // See lib/classes/filetypes.php

        $mform->addHelpButton('filetoupload', 'userdatafile', 'local_competvetsuivi');
        $mform->setType('filetoupload', PARAM_FILE);

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     * @throws coding_exception
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        if ($data['userdatafilepath'] && !file_exists($data['userdatafilepath'])) {
            $error['userdatafilepath'] = get_string('directorydoesnotexist', 'local_competvetsuivi');
        }
        return $errors;
    }
}