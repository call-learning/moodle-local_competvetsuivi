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

use local_competvetsuivi\matrix\matrix_list_renderable;

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once('add_edit_form.php');

admin_externalpage_setup('managematrix');
require_login();
$id = required_param('id', PARAM_INT);

// Override pagetype to show blocks properly.
$header = get_string('matrix:edit', 'local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/edit.php');
$PAGE->set_url($pageurl);
// Navbar
$listpageurl = new moodle_url($CFG->wwwroot.'/local/competvetsuivi/admin/matrix/list.php');
$PAGE->navbar->add(get_string('matrix:list', 'local_competvetsuivi'), new moodle_url($listpageurl));
$PAGE->navbar->add($header, null);


$matrix = new \local_competvetsuivi\matrix\matrix($id);

$matrixdata = array('fullname'=>$matrix->fullname, 'shortname'=>$matrix->shortname,'id'=>$matrix->id);
$mform = new add_edit_form(null, array('fullname'=>$matrix->fullname, 'shortname'=>$matrix->shortname,'id'=>$matrix->id));
$mform->set_data($matrixdata);

$listpageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');
if ($mform->is_cancelled()) {
    redirect($listpageurl);
}

echo $OUTPUT->header();
if ($data = $mform->get_data()) {

    $filename = $mform->get_new_filename('matrixfile');

    $matrix->shortname = $data->shortname;
    $matrix->fullname = $data->fullname;
    // Save the file and reload the matrix
    if ($filename) {
        $matrix->reset_matrix();
        $filename = $mform->get_new_filename('matrixfile');
        $tempfile = $mform->save_temp_file('matrixfile');
        $hash = file_storage::hash_from_string($mform->get_file_content('matrixfile'));
        \local_competvetsuivi\matrix\matrix::import_from_file($tempfile, $hash, $data->fullname, $data->shortname, $matrix);

    }
    $matrix->save(); // Save shortname, fullname but also hash
    $eventparams = array('objectid' => $matrix->id, 'context' => context_system::instance());
    $event = \local_competvetsuivi\event\matrix_updated::create($eventparams);
    $event->trigger();

    echo $OUTPUT->notification(get_string('matrixupdated', 'local_competvetsuivi'), 'notifysuccess');
    echo $OUTPUT->single_button($listpageurl, get_string('continue'));
} else {
    $mform->display();
}
echo $OUTPUT->footer();
