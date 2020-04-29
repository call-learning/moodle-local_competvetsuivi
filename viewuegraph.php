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

use local_competvetsuivi\matrix\matrix;

require_once(__DIR__ . '/../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_login();

$matrixid = optional_param('matrixid', 0, PARAM_INT);
$ueid = optional_param('ueid', 0, PARAM_INT);
$compidparamname = local_competvetsuivi\renderable\uevscompetency_overview::PARAM_COMPID;
$currentcompid = optional_param($compidparamname, false, PARAM_INT);

if (!$matrixid || !$DB->record_exists(matrix::CLASS_TABLE, array('id' => $matrixid))) {
    print_error('nomatrixgiven');
}
if (!$ueid) {
    print_error('nouegiven');
}
$matrix = new matrix($matrixid);

// Override pagetype to show blocks properly.
$header = get_string('matrixuevscomp:viewgraphs',
    'local_competvetsuivi');

$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/viewuegraph.php');
$PAGE->set_url($pageurl);

$matrix->load_data();
$ue = $matrix->get_matrix_ue_by_criteria('id', $ueid);

$strandlist = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('matrixuevscomptitle', 'local_competvetsuivi',
    array('matrixname' => $matrix->shortname, 'uename' => $ue->fullname)), 3);

$currentcomp = null;
if ($currentcompid) {
    $currentcomp = $matrix->get_matrix_comp_by_criteria('id', $currentcompid);
}

$progresspercent = new \local_competvetsuivi\renderable\uevscompetency_summary(
    $matrix,
    $ueid,
    $currentcomp
);

$progressoverview = new \local_competvetsuivi\renderable\uevscompetency_overview(
    $matrix,
    $ueid,
    $strandlist,
    $currentcomp
);

$renderer = $PAGE->get_renderer('local_competvetsuivi');

echo $renderer->render($progresspercent);
echo $renderer->render($progressoverview);

echo $OUTPUT->footer();
