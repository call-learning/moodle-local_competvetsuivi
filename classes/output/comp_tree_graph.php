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
 * Chart Item
 *
 * @package     local_competvetsuivi
 * @category    chart item renderable
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\output;

use local_competvetsuivi\matrix\matrix;
use renderer_base;
use stdClass;
use templatable;

class comp_tree_graph implements \renderable, templatable {
    public $items;
    public $rootitems;

    public function __construct() {
        $this->items = array();
        $this->rootitems = array();
    }

    public function add_item($comp, $progressdata) {
        $this->items[] = new comp_tree_graph_item($comp, $progressdata);
    }

    public function order_children() {
        $this->rootitems = array();
        foreach ($this->items as $it) {
            $it->clear_children();
        }
        foreach ($this->items as $it) {
            $parentpath = explode('/', $it->comp->path);
            $parentpath = array_filter($parentpath, function($el) { return $el;});
            $parentpathln = count($parentpath);
            if ($parentpathln <= 1) {
                $this->rootitems[] = $it;
            } else {
                foreach ($this->items as $itbis) {
                    if ($parentpath[$parentpathln - 1] == $itbis->comp->id) {
                        $itbis->add_children($it);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $this->order_children();
        $exportablecontext = new \stdClass();
        $exportablecontext->rootitems = array();
        foreach ($this->rootitems as $item) {
            $exportablecontext->rootitems[] = $item->export_for_template($output);
        }
        return $exportablecontext;
    }
}