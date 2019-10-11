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

use renderer_base;
use stdClass;
use templatable;

class chart_item implements \renderable, templatable {
    public $type;
    public $data;
    public $options;

    public function __construct(
            $data,
            $charttype = 'progress',
            $options = array()) {
        $this->type  = $charttype;
        $this->data = $data;
        $this->options = $options;
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
        $exportablecontext = new \stdClass();
        $exportablecontext->type = $this->type;
        $exportablecontext->data = json_encode($this->data, true);
        $exportablecontext->options = json_encode($this->options, true);
        $exportablecontext->uniqueidentifier = \html_writer::random_id('chart'.$this->type);
        return $exportablecontext;
    }
}