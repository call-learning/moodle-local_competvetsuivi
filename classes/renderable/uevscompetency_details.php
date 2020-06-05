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
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\renderable;
defined('MOODLE_INTERNAL') || die();

use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use renderer_base;
use stdClass;
use templatable;

/**
 * Class uevscompetency_details
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uevscompetency_details extends graph_overview_base implements \renderable, templatable {
    /**
     * Used to build URL (see graph_overview_trait).
     */
    const PARAM_COMPID = 'competencyvsueid';

    /**
     * @var \stdClass $ue the current ue
     */
    protected $ue = null;
    /**
     * @var bool $samesemesteronly looking at current ue or full semester?
     */
    protected $samesemesteronly = false;

    /**
     * uevscompetency_details constructor.
     *
     * @param matrix $matrix
     * @param int $ueid
     * @param array $strandlist
     * @param null $rootcomp
     * @param bool $samesemesteronly
     * @param null $linkbuildercallback
     */
    public function __construct(
        $matrix,
        $ueid,
        $strandlist,
        $rootcomp = null,
        $samesemesteronly = true,
        $linkbuildercallback = null
    ) {
        $this->init_bar_chart($matrix, $strandlist, $rootcomp, $linkbuildercallback);
        $rootcompid = $rootcomp ? $rootcomp->id : 0;

        $this->ue = $matrix->get_matrix_ue_by_criteria('id', $ueid);
        $results = ueutils::get_ue_vs_competencies($matrix, $this->ue, $rootcompid, $samesemesteronly);
        foreach (array_keys($results) as $compid) {
            $chartdata = [];
            $nullvalues = 0;
            foreach ($strandlist as $st) {
                // Now we calculate the results per strand in percentage as well as the markers (semesters cumulated).
                $data = new \stdClass();
                $res = new \stdClass();
                $res->label = matrix::comptype_to_string($st);
                $res->type = $st;
                $res->value = $results[$compid][$st];
                $data->markers = [];
                $data->result = $res;
                $nullvalues += ($res->value > 0) ? 0 : 1;
                $chartdata [] = $data;
            }
            if (!empty($chartdata) && $nullvalues != count($strandlist)) {
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
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $exportablecontext = $this->get_bar_chart_exportable_context($output);
        $exportablecontext->graph_title = get_string('contribution:title', 'local_competvetsuivi', $this->ue->shortname);
        if ($this->samesemesteronly) {
            $exportablecontext->graph_title =
                get_string('contributionsamesemester:title', 'local_competvetsuivi', $this->ue->shortname);
        }
        return $exportablecontext;
    }
}