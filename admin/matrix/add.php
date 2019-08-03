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
 * Matrix management page
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once('add_edit_form.php');

admin_externalpage_setup('managematrix');
require_login();

// Override pagetype to show blocks properly.
$header = get_string('matrix:add', 'local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/add.php');

$PAGE->set_url($pageurl);

$mform = new add_edit_form();
$mform->set_data(array());

$listurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');
if ($mform->is_cancelled()) {
    redirect($listurl);
} else if ($data = $mform->get_data()) {
    // Add a new matrix
    $filename = $mform->get_new_filename('matrixfile');
    $tempfile = $mform->save_temp_file('matrixfile');
    $hash = file_storage::hash_from_string($mform->get_file_content('matrixfile'));

    try {
        $matrix = \local_competvetsuivi\matrix\matrix::import_from_file($filename, $tempfile, $hash, $data->fullname, $data->shortname);
        $eventparams = array('objectid' => $matrix->id, 'context' => context_system::instance());
        $event = \local_competvetsuivi\event\matrix_added::create($eventparams);
        $event->trigger();
        $OUTPUT->notification(get_string('matrixadded', 'local_competvetsuivi'), 'notifysuccess');
    } catch (\local_competvetsuivi\matrix\matrix_exception $e) {
        $OUTPUT->notification($e->getMessage(), 'notifyfailure');
    }
    unlink($tempfile); // Remove temp file
    redirect($listurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
