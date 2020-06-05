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
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\renderable;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class userdata_log
 *
 * @package local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userdata_log implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        $context = new \stdClass();
        $context->userdatalog = [];
        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers();
        $store = $readers['logstore_standard'];
        $allevents = $store->get_events_select('eventname = :eventname',
            array('eventname' => '\\local_competvetsuivi\\event\\userdata_imported'), 'timecreated DESC',
            0, 0);

        foreach ($allevents as $evt) {
            $data = $evt->get_data();
            $other = $data['other'];
            unset($data['other']);
            $data = array_merge($data, $other);
            $context->userdatalog[] = $data;
        }
        return $context;
    }
}
