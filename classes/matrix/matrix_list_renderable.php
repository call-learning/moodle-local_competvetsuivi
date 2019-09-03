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
 * Renderable for list of matrix
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\matrix;
defined('MOODLE_INTERNAL') || die();
global $CFG;
use renderable;
use renderer_base;
use templatable;
use moodle_url;

/**
 * Class to list all available matrix
 *
 */
class matrix_list_renderable implements renderable, templatable {

    /**
     * Constructor.
     *
     */
    public function __construct() {
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     * This will export list of course sorted by category
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB,$CFG;
        $context = new \stdClass();
        $allmatrix = $DB->get_records('cvs_matrix');
        $context->matrix = [];
        if ($allmatrix) {
            foreach($allmatrix as $matrix) {
                $matrix->editurl = new moodle_url(
                        $CFG->wwwroot . '/local/competvetsuivi/admin/matrix/edit.php',
                        array('id' => $matrix->id)
                );
                $matrix->deleteurl = new moodle_url(
                        $CFG->wwwroot . '/local/competvetsuivi/admin/matrix/delete.php',
                        array('id' => $matrix->id)
                );
                $matrix->viewurl = new moodle_url(
                        $CFG->wwwroot . '/local/competvetsuivi/admin/matrix/view.php',
                        array('id' => $matrix->id)
                );
                $matrix->assignurl = new moodle_url(
                        $CFG->wwwroot . '/local/competvetsuivi/admin/matrix/assigncohort.php',
                        array('id' => $matrix->id)
                );

                $context->matrix[] = $matrix;
            }
        }
        $context->addactionurl = $CFG->wwwroot.'/local/competvetsuivi/admin/matrix/add.php';
        return $context;
    }
}
