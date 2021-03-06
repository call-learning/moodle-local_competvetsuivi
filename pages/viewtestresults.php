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
 * Test result view page
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_competvetsuivi\utils;

require_once(__DIR__ . '/../../../config.php');
require_once('lib.php');

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

$matrix = get_matrix($matrixid, $user);

// Override pagetype to show blocks properly.

$header = get_string('matrix:viewtestresults', 'local_competvetsuivi');
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/viewtestresults.php');
setup_page($header, $pageurl, $returnurl);


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('usertestresults', 'local_competvetsuivi',
    array('matrixname' => $matrix->shortname, 'username' => fullname($user))), 3);
$questionresults = local_competvetsuivi\autoevalutils::get_student_results(
    $userid,
    $matrix);

var_dump($questionresults);
echo $OUTPUT->footer();
