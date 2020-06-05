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
 * View UC Details
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\matrix\matrix;

require_once(__DIR__ . '/../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_login();

$returnurl = optional_param('returnurl', null, PARAM_URL);
$matrixid = required_param('matrixid', PARAM_INT);
$ueid = required_param('ueid', PARAM_INT);
$compidparamname = local_competvetsuivi\renderable\uevscompetency_details::PARAM_COMPID;
$currentcompid = optional_param($compidparamname, false, PARAM_INT);

$matrix = new matrix($matrixid);
if (!$matrix) {
    print_error('invalidmatrix');
}
$matrix->load_data();
$ue = $matrix->get_matrix_ue_by_criteria('id', $ueid);

// Override pagetype to show blocks properly.
$header = get_string('matrixuevscomptitle', 'local_competvetsuivi',
    array('matrixname' => $matrix->shortname, 'uename' => $ue->fullname));

$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
if ($returnurl) {
    $PAGE->set_button($OUTPUT->single_button(
        new moodle_url($returnurl), get_string('back'), 'ucdetails-backbtn')
    );
}

$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/pages/ucdetails.php');
$PAGE->set_url($pageurl);

$strandlist = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY);

echo $OUTPUT->header();

$currentcomp = null;
if ($currentcompid) {
    $currentcomp = $matrix->get_matrix_comp_by_criteria('id', $currentcompid);
}
$progresspercent = new \local_competvetsuivi\renderable\uevscompetency_summary(
    $matrix,
    $ueid,
    $currentcomp
);

$progressdetails = new \local_competvetsuivi\renderable\uevscompetency_details(
    $matrix,
    $ueid,
    $strandlist,
    $currentcomp,
    false
);

$renderer = $PAGE->get_renderer('local_competvetsuivi');

if (!$currentcompid) {
    echo $renderer->render($progresspercent);
}
echo $renderer->render($progressdetails);

echo $OUTPUT->footer();
