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
require_once($CFG->libdir.'/weblib.php');

admin_externalpage_setup('managematrix');
require_login();
$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm',false, PARAM_BOOL);

// Override pagetype to show blocks properly.
$header = get_string('matrix:add','local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot.'/local/competvetsuivi/admin/matrix/delete.php');
$listpageurl = new moodle_url($CFG->wwwroot.'/local/competvetsuivi/admin/matrix/list.php');
$PAGE->set_url($pageurl);

echo $OUTPUT->header();
if (!$confirm) {
    $confirmurl = new moodle_url($pageurl,array('confirm'=>true, 'id'=>$id, 'sesskey'=>sesskey()));
    $cancelurl = new moodle_url($CFG->wwwroot.'/local/competvetsuivi/admin/matrix/list.php');
    echo $OUTPUT->confirm(get_string('matrix:delete','local_competvetsuivi'),$confirmurl, $listpageurl);
} else {
    require_sesskey();
    $matrix = new \local_competvetsuivi\matrix\matrix($id);
    $matrix->delete(true);
    echo $OUTPUT->notification(get_string('matrixdeleted', 'local_competvetsuivi'), 'notifysuccess');
    echo $OUTPUT->single_button($listpageurl, get_string('continue'));
}
echo $OUTPUT->footer();
