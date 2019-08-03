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

require_once(__DIR__ . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('managematrix');
require_login();

// Override pagetype to show blocks properly.
$header = get_string('managematrix','local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot.'/local/competvetsuivi/admin/matrix/list.php');

$PAGE->set_url($pageurl);

$renderer = $PAGE->get_renderer('core');
$renderable = new local_competvetsuivi\matrix\matrix_list_renderable();

echo $OUTPUT->header();
echo $renderer->render_from_template('local_competvetsuivi/matrix_list', $renderable->export_for_template($renderer));
echo $OUTPUT->footer();
