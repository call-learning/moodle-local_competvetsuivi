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

namespace local_competvetsuivi\renderable;

use local_competvetsuivi\matrix\matrix;
use renderer_base;


abstract class graph_overview_base {
    const PARAM_COMPID = 'TOBEDEFINED'; // Used to build URL (see graph_overview_trait)
    const MAX_FULLNAME_LN = 100;

    protected $linkbuilder = null;
    public $rootcomp = null;
    public $childrencomps = array();
    protected $strandlist = null;
    protected $matrix = null;
    public $charts = array();

    public function init(
            $matrix,
            $strandlist,
            $rootcomp = null,
            $linkbuildercallback = null) {
        $this->rootcomp = $rootcomp;
        $this->strandlist = $strandlist;
        $this->matrix = $matrix;
        $urlparam = static::PARAM_COMPID;

        $defaultlinkbuilder = function($competency) use ($urlparam) {
            global $FULLME;
            return new \moodle_url($FULLME, array($urlparam => $competency->id));
        };
        $this->linkbuilder = $linkbuildercallback ? $linkbuildercallback : $defaultlinkbuilder;
        $this->childrencomps = $matrix->get_child_competencies($rootcomp ? $rootcomp->id : 0, true);
    }

    protected function get_intial_exportable_context(renderer_base $output) {
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
            $homeurl->remove_params(static::PARAM_COMPID);
            array_unshift($exportablecontext->breadcrumbs, (object) [
                    'name' => get_string('home'),
                    'link' => $homeurl->out(false),
            ]);
        }
        foreach ($this->childrencomps as $c) {
            if (empty($this->charts[$c->id])) {
                continue; // Make sure we skip empty items
            }
            $compitem = new \stdClass();
            $compitem->competency_fn = $c->fullname;
            if (strlen($compitem->competency_fn) > static::MAX_FULLNAME_LN) {
                $compitem->competency_fn = trim(\core_text::substr($compitem->competency_fn, 0, static::MAX_FULLNAME_LN)) . '...';
            }
            $compitem->competency_sn = $c->shortname;

            $compitem->competency_link = null;
            if ($this->matrix->has_children($c)) {
                $compitem->competency_link = ($this->linkbuilder)($c)->out(false);
            }
            $compitem->graphdata = $this->charts[$c->id]->export_for_template($output);
            $exportablecontext->compitems[] = $compitem;
        }
        return $exportablecontext;
    }
}