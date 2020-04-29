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

use local_competvetsuivi\event\matrix_updated;
use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\utils;

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once('cohort_assign_form.php');

admin_externalpage_setup('managematrix');
require_login();
$id = required_param('id', PARAM_INT);

// Override pagetype to show blocks properly.
$header = get_string('matrix:assigncohorts', 'local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/edit.php');
$PAGE->set_url($pageurl);
// Navbar.
$listpageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');
$PAGE->navbar->add(get_string('matrix:assigncohorts', 'local_competvetsuivi'), new moodle_url($listpageurl));
$PAGE->navbar->add($header, null);

$matrix = new matrix($id);

$matrixdata = array('fullname' => $matrix->fullname, 'shortname' => $matrix->shortname, 'id' => $matrix->id);
$mform = new cohort_assign_form(null, array(
        'fullname' => $matrix->fullname,
        'shortname' => $matrix->shortname,
        'id' => $matrix->id)
);
$currendata = array();
$currendata['matrixcohortsassignment'] =
    $DB->get_fieldset_select('cvs_matrix_cohorts', 'cohortid', 'matrixid = :matrixid', array('matrixid' => $matrix->id));
$mform->set_data($currendata);

$listpageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');
if ($mform->is_cancelled()) {
    redirect($listpageurl);
}

echo $OUTPUT->header();
if ($data = $mform->get_data()) {
    global $DB;
    // First delete all assignments for this matrix.

    $DB->delete_records('cvs_matrix_cohorts', array('matrixid' => $matrix->id));

    foreach ($data->matrixcohortsassignment as $cohortid) {
        utils::assign_matrix_cohort($matrix->id, $cohortid);
    }
    $action = get_string('cohortassigned', 'local_competvetsuivi');
    $eventparams = array('objectid' => $matrix->id, 'context' => context_system::instance(), 'other' => array(
        'actions' => $action
    ));
    $event = matrix_updated::create($eventparams);
    $event->trigger();

    echo $OUTPUT->notification($action, 'notifysuccess');
    echo $OUTPUT->single_button($listpageurl, get_string('continue'));
} else {
    $mform->display();
}
echo $OUTPUT->footer();
