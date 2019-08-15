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
use local_competvetsuivi\matrix\matrix;

require_once(__DIR__ . '/../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_login();

$userid = required_param('id', PARAM_INT);
$matrixid = required_param('matrixid', PARAM_INT);
$matrix = new \local_competvetsuivi\matrix\matrix($matrixid);
$userid = $userid ? $userid : $USER->id;
$user = \core_user::get_user($userid);

// Override pagetype to show blocks properly.
$header = get_string('matrix:viewdata',
        'local_competvetsuivi');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/viewuserdata.php');
$PAGE->set_url($pageurl);

$userdata = local_competvetsuivi\userdata::get_user_data($user->email);
$matrix->load_data();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('matrixviewdatatitle', 'local_competvetsuivi',
        array('matrixname' => $matrix->shortname, 'username' => fullname($user))), 3);
$table = new html_table();
$table->attributes['class'] = 'generaltable boxaligncenter flexible-wrap';

$matrixues = $matrix->get_matrix_ues();
$uenames = array_map(function($ue) {
    return $ue->fullname;
}, $matrixues);
$uenamexues =
        array_combine($uenames, $matrixues); // We have now an array with UE names => ue, it is now easier to get info from each ue

$arrayheader = array(get_string('competencies', 'local_competvetsuivi'));
foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypname) {
    $arrayheader[] = get_string('matrixcomptype:' . $comptypname, 'local_competvetsuivi');
}
$table->head = $arrayheader;

$competencies = $matrix->get_matrix_competencies();

foreach ($competencies as $comp) {
    // For each competency regroup all finished ues and values
    $type = array();
    foreach ($matrixues as $ue) {
        $values = $matrix->get_values_for_ue_and_competency($ue->id, $comp->id);
        if (!empty($userdata[$ue->shortname])) {
            foreach ($values as $ueval) {
                if (empty($type[$ueval->type])) {
                    $type[$ueval->type] = array();
                }
                $type[$ueval->type][] = $ueval->value;
            }
        }
    }
    $cells = array(new html_table_cell($comp->fullname));
    foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypeid => $comptypname) {
        $celltext = "";
        if (!empty($type[$comptypeid])) {
            $celltext = join(',', $type[$comptypeid]);
        }
        $cells[] = new html_table_cell($celltext);
    }
    $table->data[] = new html_table_row($cells);
}
echo html_writer::table($table);

echo $OUTPUT->footer();
