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
 * Lib common to all pages
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Setup page header
 * @param string $header
 * @param moodle_url $pageurl
 * @param moodle_url $returnurl
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function setup_page($header, $pageurl, $returnurl) {
    global $PAGE, $OUTPUT;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title($header);
    if ($returnurl) {
        $PAGE->set_button($OUTPUT->single_button(
            new moodle_url($returnurl), get_string('back'), 'cvspage-backbtn')
        );
    }
    $PAGE->set_url($pageurl);
}

/**
 * Get matrix or if null the user's attached matrix
 * @param \local_competvetsuivi\matrix\matrix $matrixid
 * @param stdClass $user
 * @return \local_competvetsuivi\matrix\matrix
 * @throws dml_exception
 * @throws moodle_exception
 */
function get_matrix($matrixid, $user) {
    if (!$matrixid) {
        $matrixid = utils::get_matrixid_for_user($user->id);
        if ($matrixid) {
            print_error('nocohortforuser');
        }
    }
    $matrix = new \local_competvetsuivi\matrix\matrix($matrixid);
    $matrix->load_data();
    return $matrix;
}