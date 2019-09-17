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
$matrixues = $matrix->get_matrix_ues();
$uenames = array_map(function($ue) {
    return $ue->fullname;
}, $matrixues);
$uenamexues =
        array_combine($uenames, $matrixues); // We have now an array with UE names => ue, it is now easier to get info from each ue

$competencies = $matrix->get_matrix_competencies();

$barchartoptions = [
        "scales" => [
                "yAxes" => [
                        [
                                "barPercentage" => 1.0,
                                "stacked" => true,
                        ],
                ],
                "xAxes" => [
                        [
                                "stacked" => false,
                                "ticks" => [
                                        "beginAtZero" => true,
                                        "max" => 1.0,
                                        "min" => 0,
                                ]
                        ]
                ]
        ],
    //"legend" => false,
        "backgroundColor" => [
                matrix::MATRIX_COMP_TYPE_KNOWLEDGE => 'rgba(255, 99, 132, 0.2)',
                matrix::MATRIX_COMP_TYPE_ABILITY => 'rgba(54, 162, 235, 0.2)'
        ],
];

$competenciesstrandsnames = matrix::get_all_competencies_strands();
$competenciesstrandsnames = array_slice($competenciesstrandsnames, 0, 2); // Only get the first two
$context = new stdClass();
$context->competencygraphs = [];

$treegraph = new local_competvetsuivi\output\comp_tree_graph();
/*
    var data = [
                {
                    "groupshortname":"S1",
                    "groupname":"Semester 1",
                    "rlabels": ["Connaissances", "Capacité"],
                    "results":[1.5, 1],
                    "maxresults":[1.5, 1.5]
                },
                {
                    "groupshortname":"S2",
                    "groupname":"Semester 2",
                    "rlabels": ["Connaissances", "Capacité"],
                    "results":[0.5,0.5],
                    "maxresults":[1.5, 0.5]
                }
            ];

*/

foreach ($competencies as $comp) {

    // Get the UE for each semester
    $data = [];
    for ($semester = 1; $semester < 7; $semester ++) {
        $ueselection = ueutils::get_ues_for_semester($semester, $matrix);
        list($progressspertrand, $maxperstrand) =
                chartingutils::get_comp_progress($matrix, $comp, $userdata, array('knowledge', 'ability'), $ueselection);
        $data[] = [
                'groupshortname' => 'S' . $semester,
                'groupname' => 'Semester' . $semester,
                "rlabels" => ["Connaissances", "Capacité"],
                "results" => array_values($progressspertrand),
                "maxresults" => array_values($maxperstrand)
        ];

    }
    $treegraph->add_competency_progressbar_graph(
            $comp,
            $data,
            $barchartoptions,
            $comp->shortname);
}
$renderer = $PAGE->get_renderer('local_competvetsuivi');
echo $renderer->render($treegraph);
echo $OUTPUT->footer();
