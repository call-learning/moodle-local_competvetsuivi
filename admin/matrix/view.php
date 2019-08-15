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

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('managematrix');
require_login();
$id = required_param('id', PARAM_INT);
$matrix = new \local_competvetsuivi\matrix\matrix($id);

// Override pagetype to show blocks properly.
$header = get_string('matrix:view', 'local_competvetsuivi', $matrix->shortname);
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/view.php');
$PAGE->set_url($pageurl);
// Navbar
$listpageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');
$PAGE->navbar->add(get_string('matrix:list', 'local_competvetsuivi'), new moodle_url($listpageurl));
$PAGE->navbar->add($header, null);

$matrix->load_data();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('matrixviewtitle', 'local_competvetsuivi', $matrix->fullname), 3);
$table = new html_table();
$table->attributes['class'] = 'generaltable boxaligncenter flexible-wrap';
$uenames = array_map(function($ue) { return $ue->fullname;}, $matrix->get_matrix_ues());
array_unshift($uenames, get_string('competencies','local_competvetsuivi'));
$table->head = $uenames;

$competencies = $matrix->get_matrix_competencies();
foreach ($competencies as $comp) {

    $cells = array(new html_table_cell($comp->fullname));
    foreach($matrix->get_matrix_ues() as $ue) {
        $values = array();
        foreach ($matrix->get_values_for_ue_and_competency($ue->id,$comp->id) as $compsue) {
            $values[] = matrix::comptype_to_string($compsue->type).':'. $compsue->value;
        }
        $cells[] = new \html_table_cell(\html_writer::alist($values));
    }
    $table->data[] = new html_table_row($cells);
}
echo html_writer::table($table);


echo $OUTPUT->footer();
