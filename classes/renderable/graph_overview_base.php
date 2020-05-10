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
use renderer_base;

/**
 * Class graph_overview_base
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class graph_overview_base {
    /**
     * Used to build URL (see graph_overview_trait). This is to be overriden
     */
    const PARAM_COMPID = 'TOBEDEFINED'; // Used to build URL (see graph_overview_trait).
    /**
     * Max Fullname length
     */
    const MAX_FULLNAME_LN = 100;
    /**
     * @var callable callback function to build links
     */
    protected $linkbuilder = null;
    /**
     * @var \stdClass $rootcomp Root competency
     */
    public $rootcomp = null;
    /**
     * @var array Children competences
     */
    public $childrencomps = array();
    /**
     * @var array all strnads
     */
    protected $strandlist = null;
    /**
     * @var matrix the matrix
     */
    protected $matrix = null;
    /**
     * @var array array of charts
     */
    public $charts = array();

    /**
     * Init the bar chart from values
     *
     * @param matrix $matrix
     * @param array $strandlist
     * @param null $rootcomp
     * @param null $linkbuildercallback
     */
    public function init_bar_chart(
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

    /**
     * Export the strand list for renderer
     *
     * @param \stdClass $exportablecontext
     */
    protected function export_strand_list(&$exportablecontext) {
        $exportablecontext->comp_types = array_map(function($comptypeid) {
            return (object) ['comp_type_id' => $comptypeid, 'comp_type_name' => matrix::get_competency_type_name($comptypeid)];
        }, $this->strandlist);
    }

    /**
     * Get exportable content for renderer
     *
     * @param renderer_base $output
     * @return \stdClass
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function get_bar_chart_exportable_context(renderer_base $output) {
        global $FULLME;
        // TODO : fix this, we should have a way to override.
        $exportablecontext = new \stdClass();
        $exportablecontext->graph_title = get_string('graphtitle:level0', 'local_competvetsuivi');
        $this->export_strand_list($exportablecontext);
        $exportablecontext->competency_desc = $this->rootcomp ?
            format_text($this->rootcomp->description, $this->rootcomp->descriptionformat) : "";

        $exportablecontext->breadcrumbs = array();

        $allcomps = $this->matrix->get_matrix_competencies();

        // We build a numeric array of macro competences (from 1 to 8 but can be more).
        $allmacrocomps = array_filter($allcomps, function($c) {
            return substr_count($c->path, '/') < 2;
        });
        $allmacrocompsid = array_values(array_map(function($c) {
            return $c->id;
        }, $allmacrocomps));

        // Build breadcrump and set the title.
        if ($this->rootcomp) {
            $allcompsid = explode('/', $this->rootcomp->path);
            // Here array_values is necessary if not the json transformation will think breadcrumbs is an object and not an array.
            $allcompsid = array_values(array_filter($allcompsid, function($val) {
                return $val != "";
            }));
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
            // we need to abstract this.
            $homeurl = new  \moodle_url($FULLME);
            $homeurl->remove_params(static::PARAM_COMPID);
            array_unshift($exportablecontext->breadcrumbs, (object) [
                'name' => get_string('home', 'local_competvetsuivi'),
                'link' => $homeurl->out(false),
            ]);

            // Set the right title.

            $level = min(count($allcompsid), 2);
            $exportablecontext->graph_title = get_string("graphtitle:level$level", 'local_competvetsuivi');;
        }
        foreach ($this->childrencomps as $c) {
            if (empty($this->charts[$c->id])) {
                continue; // Make sure we skip empty items.
            }
            $compitem = new \stdClass();
            $compitem->competency_fn = $c->fullname;
            if (strlen($compitem->competency_fn) > static::MAX_FULLNAME_LN) {
                $compitem->competency_fn = trim(\core_text::substr($compitem->competency_fn, 0, static::MAX_FULLNAME_LN)) . '...';
            }
            $compitem->competency_sn = $c->shortname;
            $compitem->competency_desc = $c->description;
            $compitem->competency_link = null;
            if ($this->matrix->has_children($c)) {
                $compitem->competency_link = ($this->linkbuilder)($c)->out(false);
            }
            list($macrocompid) = sscanf($c->path, '/%d/%s');

            $compitem->competency_mcompindex = array_search($macrocompid, $allmacrocompsid);
            $compitem->graphdata = $this->charts[$c->id]->export_for_template($output);
            $exportablecontext->compitems[] = $compitem;
        }
        return $exportablecontext;
    }
}