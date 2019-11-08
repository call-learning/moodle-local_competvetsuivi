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
use local_competvetsuivi\utils;
use renderer_base;
use stdClass;
use templatable;
use local_competvetsuivi\chartingutils;

class competency_progress_overview implements \renderable, templatable {
    protected $compcharts;
    protected $linkbuilder = null;
    public $rootcomp = null;
    public $childrencomps = array();
    protected $strandlist = null;
    protected $matrix = null;
    const MAX_FULLNAME_LN = 100;

    public function __construct(
            $rootcomp,
            $matrix,
            $strandlist,
            $userdata,
            $currentsemester,
            $linkbuildercallback = null) {
        $this->rootcomp = $rootcomp;
        $this->strandlist = $strandlist;

        $rootcompid = $rootcomp ? $rootcomp->id : 0;
        $this->childrencomps = $matrix->get_child_competencies($rootcompid, true);
        foreach ($this->childrencomps as $comp) {
            $this->compcharts[$comp->id] =
                    new chart_item(
                            chartingutils::get_data_for_progressbar($matrix, $comp, $strandlist, $userdata, $currentsemester)
                    );
        }
        $defaultlinkbuilder = function($competency) {
            global $FULLME;
            return new \moodle_url($FULLME, array('competencyid' => $competency->id));
        };
        $this->linkbuilder = $linkbuildercallback ? $linkbuildercallback : $defaultlinkbuilder;
        $this->matrix = $matrix;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a chart_item.mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $FULLME;
        // TODO : fix this, we should have a way to override
        $exportablecontext = new \stdClass();
        $exportablecontext->comp_fullname = $this->rootcomp ?
                $this->rootcomp->fullname : get_string('rootcomp', 'local_competvetsuivi');

        $exportablecontext->comp_types = array_map(function($comptypeid) {
            return (object) ['comp_type_id' => $comptypeid, 'comp_type_name' => matrix::get_competency_type_name($comptypeid)];
        }, $this->strandlist);

        $exportablecontext->breadcrumbs = array();

        // Build breadcrump
        if ($this->rootcomp) {
            $allcompsid = explode('/', $this->rootcomp->path);
            // Here array_values is necessary if not the json transformation will think breadcrumbs is an object and not an array
            $allcompsid = array_values(array_filter($allcompsid, function($val) {
                return $val != "";
            }));
            $allcomps = $this->matrix->get_matrix_competencies();
            $linkbuilder = $this->linkbuilder;

            $exportablecontext->breadcrumbs = array_map(
                    function($compid) use ($allcomps, $linkbuilder) {
                        $comp = $allcomps[$compid];
                        return (object) [
                                'name' => $comp->shortname,
                                'link' => ($linkbuilder)($comp)->out(false),
                        ];
                    },
                    $allcompsid
            );

            // TODO we rely on a parameter competencyid that could be different in different context/
            // we need to abstract this
            $homeurl = new  \moodle_url($FULLME);
            $homeurl->remove_params('competencyid');
            array_unshift($exportablecontext->breadcrumbs, (object) [
                    'name' => get_string('home'),
                    'link' => $homeurl->out(false),
            ]);
        }
        $exportablecontext->compitems = [];
        foreach ($this->childrencomps as $c) {
            $compitem = new \stdClass();
            $compitem->competency_fn = $c->fullname;
            if (strlen($compitem->competency_fn)> self::MAX_FULLNAME_LN ) {
                $compitem->competency_fn = trim(\core_text::substr($compitem->competency_fn, 0, self::MAX_FULLNAME_LN)) . '...';
            }
            $compitem->competency_sn = $c->shortname;

            $compitem->competency_link = null;
            if ($this->matrix->has_children($c)) {
                $compitem->competency_link = ($this->linkbuilder)($c)->out(false);
            }
            $stranddata = new \stdClass();
            $stranddata->graphdata = $this->compcharts[$c->id]->export_for_template($output);
            $compitem->compvsuegraphdata = $this->compcharts[$c->id]->export_for_template($output);;
            $exportablecontext->compitems[] = $compitem;
        }
        return $exportablecontext;
    }
}