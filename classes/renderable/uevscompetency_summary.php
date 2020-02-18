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
 * Progress bar item : UC during whole year
 *
 * @package     local_competvetsuivi
 * @category    chart item renderable
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\renderable;

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use renderer_base;
use stdClass;
use templatable;

class uevscompetency_summary extends graph_overview_base implements \renderable, templatable {
    const PARAM_COMPID = 'competencyvsueid'; // Used to build URL (see graph_overview_trait)

    protected $ue = null;
    protected $chartdata = null;

    public function __construct(
            $matrix,
            $ueid,
            $rootcomp = null
    ) {
        $this->strandlist = [matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY];
        $this->init_bar_chart($matrix, $this->strandlist, $rootcomp, null);
        $rootcompid = $rootcomp ? $rootcomp->id : 0;

        $this->ue = $matrix->get_matrix_ue_by_criteria('id', $ueid);
        $chartdata = ueutils::get_ue_vs_competencies_percent($matrix, $this->ue, $this->strandlist, $rootcompid);
        $chartdata->overlaystrandid = matrix::MATRIX_COMP_TYPE_KNOWLEDGE;
        $this->chartdata = $chartdata;
        $this->chart = new chart_item($chartdata, 'ring');
    }

    /**
     * Function to export the renderer data in a format that is suitable for a chart_item.mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $exportablecontext = new stdClass();
        $exportablecontext->chartdata = $this->chart->export_for_template($output);
        $exportablecontext->graph_title = get_string('repartition:title', 'local_competvetsuivi', $this->ue->shortname);

        $this->export_strand_list($exportablecontext);

        $legendvals = [];
        foreach ($this->chartdata->compsvalues as $cval) {
            $newval = new \stdClass();
            $newval->fullname = $cval->fullname;
            $newval->shortname = $cval->shortname;
            $newval->colorindex = $cval->colorindex;
            $newval->val = round($cval->val * 100);
            $strandvals = [];
            foreach ($cval->strandvals as $type => $st) {
                $stval = new \stdClass();
                $stval->isoverlaystrand = ($type == $this->chartdata->overlaystrandid);
                $percentval = $st->val * $cval->val * 100;
                // We try to round it to the right amount so we can add the percentages to the right full value
                // For example if we have a whole percentage of 34%, we should have 33 + 1.
                $stval->percentval = round($percentval);
                $strandvals[] = $stval;
            }
            $newval->strandvals = $strandvals;
            $legendvals[] = $newval;
        }
        $exportablecontext->comps_legend = $legendvals;
        $exportablecontext->comp_strandlist_string = join(',', array_map(function($comptypeid) {
            return matrix::get_competency_type_name($comptypeid);
        }, $this->strandlist));
        return $exportablecontext;
    }
}