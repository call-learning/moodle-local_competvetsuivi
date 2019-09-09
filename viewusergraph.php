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

// ChartJS
$chartjspath = '/local/competvetsuivi/lib/js/Chart/Chart';
if ($CFG->debugdeveloper) {
    $chartjspath .= '.min';
}
$PAGE->requires->js($chartjspath . '.js', true);
$PAGE->requires->css($chartjspath . '.css', true);
// End ChartJS

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

$arrayheader = array(get_string('competencies', 'local_competvetsuivi'),
        get_string('competencyfullname', 'local_competvetsuivi'));
$arrayheader[] = get_string('currentprogress', 'local_competvetsuivi');
$arrayheader[] = get_string('currentprogress', 'local_competvetsuivi');

$table->head = $arrayheader;

$competencies = $matrix->get_matrix_competencies();

$chartjsscript = array(); // Collection of javascript to append at then end of the output
//We should use bullet chart (see http://nvd3.org/examples/bullet.html)
// Or with ChartJS https://jsfiddle.net/usrntq5d/
$barchartoptions = [
        "scales" => [
                "yAxes" => [
                        [
                                "barThickness" => 30,
                                "stacked" => true,
                        ],
                ],
        ],
        "xAxes" => [
                [
                        "stacked" => false,
                ]
        ],
        "legend" => false,
];

$competenciesstrandsnames = [];

foreach (matrix::MATRIX_COMP_TYPE_NAMES as $comptypname) {
    $competenciesstrandsnames[] = get_string('matrixcomptype:' . $comptypname, 'local_competvetsuivi');
}

foreach ($competencies as $comp) {
    $cells = array(new html_table_cell($comp->shortname), new html_table_cell($comp->fullname));
    list($possibledataset, $currentuserdataset) = chartingutils::get_comp_dataset($matrix, $comp, $userdata);
    $celltext = "";

    // Horizontal bar
    $chartuid = \html_writer::random_id($comp->shortname);
    $celltext .= \html_writer::div(
            \html_writer::tag('canvas', '', array('id' => $chartuid)),
            '',
            array("style" => "position: relative; width:25vw")
    );
    $hbarscriptdata = (object) [
            'uid' => $chartuid,
            'type'=> 'horizontalBar',
            'data' => [
                    'labels' => $competenciesstrandsnames,
                    "datasets" => [
                            [
                                    "data" => $currentuserdataset,
                                    "backgroundColor" => "rgba(155, 0, 132, 0.5)",
                            ],
                            [
                                    "data" => $possibledataset,
                                    "backgroundColor" => "rgba(0, 0, 255, 0.2)",
                            ]

                    ]
            ],
            'options' => $barchartoptions,
    ];
    $chartjsscript[] = $hbarscriptdata;

    $cells[] = new html_table_cell($celltext);

    $celltext = "";

    // Radar
    $chartuid = \html_writer::random_id($comp->shortname);
    $celltext .= \html_writer::div(
            \html_writer::tag('canvas', '', array('id' => $chartuid)),
            '',
            array("style" => "position: relative; width:25vw")
    );
    $hbarscriptdata = (object) [
            'uid' => $chartuid,
            'type'=> 'radar',
            'data' => [
                    'labels' => $competenciesstrandsnames,
                    "datasets" => [
                            [
                                    "data" => $currentuserdataset,
                                    "backgroundColor" => "rgba(155, 0, 132, 0.5)",
                            ],
                            [
                                    "data" => $possibledataset,
                                    "backgroundColor" => "rgba(0, 0, 255, 0.2)",
                            ]

                    ]
            ],
            'options' => $barchartoptions,
    ];
    $chartjsscript[] = $hbarscriptdata;

    $cells[] = new html_table_cell($celltext);

    $table->data[] = new html_table_row($cells);
}
echo html_writer::table($table);
$code = "";
foreach ($chartjsscript as $cjs) {
    $options = json_encode($cjs);
    $code .= "\n
    var ctx = document.getElementById('{$cjs->uid}').getContext('2d');
    Chart.plugins.register({
      afterDatasetsUpdate: function(chart) {
        Chart.helpers.each(chart.getDatasetMeta(0).data, function(rectangle, index) {
          rectangle._view.width = rectangle._model.height = 15;
        });
      },
    })
    var chart = new Chart(ctx, $options); \n";
}
echo \html_writer::script($code);
echo $OUTPUT->footer();
