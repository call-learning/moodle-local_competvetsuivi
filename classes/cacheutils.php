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
 * Auto evaluation utils
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

defined('MOODLE_INTERNAL') || die();

use cache;
use local_competvetsuivi\matrix\matrix;

/**
 * Class cacheutils
 *
 * @package local_competvetsuivi
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cacheutils {

    /**
     * Get unique Hash from parameters
     *
     * @param matrix $matrix
     * @param \stdClass $currentue
     * @param int $rootcompid
     * @param bool $samesemesteronly
     * @return string
     */
    public static function get_ue_vs_competencie_hash($matrix, $currentue, $rootcompid, $samesemesteronly) {
        return hash('sha256', strval($matrix->id) . strval($matrix->timemodified) .
            $currentue->shortname . strval($rootcompid) . strval($samesemesteronly));
    }

    /**
     * Get unique Hash from parameters
     *
     * @param matrix $matrix
     * @param \stdClass $currentue
     * @param array $strandids
     * @param int $rootcompid
     * @return string
     */
    public static function get_ue_vs_competencies_percent_hash($matrix, $currentue, $strandids, $rootcompid) {
        $strandstrings = join('', $strandids);
        return hash('sha256', strval($matrix->id) . strval($matrix->timemodified) .
            $currentue->shortname . $strandstrings . strval($rootcompid));
    }

    /**
     * Get unique Hash from parameters
     *
     * @param matrix $matrix
     * @param \stdClass $currentcomp
     * @param \stdClass $userdata
     * @param array $strands
     * @param null $ueselection
     * @return string
     */
    public static function get_comp_progress_hash($matrix, $currentcomp, $userdata, $strands = array(), $ueselection = null) {
        /* @var $matrix matrix The related matrix */
        $userdatastring = json_encode($userdata);
        $strandstrings = join('', $strands);
        $uestring = $ueselection ?
            array_reduce($ueselection, function($acc, $item) {
                return $acc . $item->shortname;
            }, "") : "";

        return hash('sha256',
            strval($matrix->id) . strval($matrix->timemodified) . $currentcomp->shortname . $userdatastring . $strandstrings .
            $uestring);
    }

    /**
     * Get value from key
     *
     * @param string $hashkey
     * @param string $cachetype
     * @return bool|false|float|int|mixed|string
     * @throws \coding_exception
     */
    public static function get($hashkey, $cachetype) {
        $cache = cache::make('local_competvetsuivi', $cachetype);
        if ($cache) {
            return $cache->get($hashkey);
        }
        return false;
    }

    /**
     * Associate value and key in the cache
     *
     * @param string $cachetype
     * @param string $hashkey
     * @param mixed $value
     * @return bool
     */
    public static function set($cachetype, $hashkey, $value) {
        $cache = cache::make('local_competvetsuivi', $cachetype);
        if ($cache) {
            return $cache->set($hashkey, $value);
        }
        return false;
    }
}