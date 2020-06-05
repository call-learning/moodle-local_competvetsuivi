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
 * Data view Page
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\chartingutils;
use local_competvetsuivi\matrix\matrix_list_renderable;
use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use local_competvetsuivi\utils;

require_once(__DIR__ . '/../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_login();

$returnurl = optional_param('returnurl', null, PARAM_URL);
$userid = optional_param('userid', 0, PARAM_INT);
$matrixid = optional_param('matrixid', 0, PARAM_INT);
$compidparamname = local_competvetsuivi\renderable\competency_progress_overview::PARAM_COMPID;
$currentcompid = optional_param($compidparamname, false, PARAM_INT);
$userid = $userid ? $userid : $USER->id;
$user = \core_user::get_user($userid);

if (!$matrixid) {
    $matrixid = utils::get_matrixid_for_user($user->id);
    if ($matrixid) {
        print_error('nocohortforuser');
    }
}
$matrix = new \local_competvetsuivi\matrix\matrix($matrixid);

// Override pagetype to show blocks properly.
$header = get_string('matrix:viewdata',
    'local_competvetsuivi');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/viewuserdata.php');
$PAGE->set_url($pageurl);
if ($returnurl) {
    $PAGE->set_button($OUTPUT->single_button(
        new moodle_url($returnurl), get_string('back'), 'ucdetails-backbtn')
    );
}

$userdata = local_competvetsuivi\userdata::get_user_data($user->email);
$matrix->load_data();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('matrixviewdatatitle', 'local_competvetsuivi',
    array('matrixname' => $matrix->shortname, 'username' => fullname($user))), 3);

$strandlist = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY);
$lastseenue = local_competvetsuivi\userdata::get_user_last_ue_name($user->email);
$currentsemester = ueutils::get_current_semester_index($lastseenue, $matrix);

$currentcomp = null;
if ($currentcompid) {
    $currentcomp = $matrix->get_matrix_comp_by_criteria('id', $currentcompid);
}

$progressoverview = new \local_competvetsuivi\renderable\competency_progress_overview(
    $currentcomp,
    $matrix,
    $strandlist,
    $userdata,
    $currentsemester,
    $userid,
    null,
    false
);

$renderer = $PAGE->get_renderer('local_competvetsuivi');
echo $renderer->render($progressoverview);
echo $OUTPUT->footer();
