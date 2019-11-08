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
 * Progress bar item
 *
 * @package     local_competvetsuivi
 * @category    chart item renderable
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\output;

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use local_competvetsuivi\utils;
use renderer_base;
use stdClass;
use templatable;
use local_competvetsuivi\chartingutils;

class uevscompetency_overview extends graph_overview_base implements \renderable, templatable {
    const PARAM_COMPID = 'competencyvsueid'; // Used to build URL (see graph_overview_trait)

    protected $ue = null;

    public function __construct(
            $matrix,
            $ueid,
            $strandlist,
            $rootcomp = null,
            $samesemesteronly = true,
            $linkbuildercallback = null
    ) {
        $this->init($matrix, $strandlist, $rootcomp, $linkbuildercallback);
        $rootcompid = $rootcomp ? $rootcomp->id : 0;

        $this->ue = $matrix->get_matrix_ue_by_criteria('id', $ueid);
        $results = ueutils::get_ue_vs_competencies($matrix, $this->ue, $rootcompid, $samesemesteronly);
        foreach (array_keys($results) as $compid) {
            $chartdata = [];
            foreach ($strandlist as $st) {
                // Now we calculate the results per strand in percentage as well as the markers (semesters cumulated)
                $data = new \stdClass();
                $res = new \stdClass();
                $res->label = matrix::comptype_to_string($st);
                $res->type = $st;
                $res->value = $results[$compid][$st];
                $data->markers = [];
                $data->result = $res;
                if ($res->value > 0) {
                    $chartdata [] = $data;
                }
            }
            if (!empty($chartdata)) {
                $this->charts[$compid] = new chart_item($chartdata);
            }
        }
        $this->samesemesteronly = $samesemesteronly;

    }

    /**
     * Function to export the renderer data in a format that is suitable for a chart_item.mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $exportablecontext = $this->get_intial_exportable_context($output);
        return $exportablecontext;
    }
}