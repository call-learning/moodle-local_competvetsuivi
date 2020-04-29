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
 * User upload data task
 *
 * @package     local_competvetsuivi
 * @category    user upload data task
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi\task;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_competvetsuivi\userdata;
use moodle_url;
use core_user;

/**
 * Check if we need to send meeting reminders to users as a notification
 */
class userdata_csv_upload extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('userdatacsvuploadtask', 'local_competvetsuivi');
    }

    /**
     * Send out messages.
     */
    public function execute() {
        global $CFG;
        if ($CFG->enablecompetvetsuivi) {
            // TODO: send a message to admin when uploading completed.
            static::process_userdata_csv();
        }
    }

    public static function process_userdata_csv() {
        $userdatafilepath = get_config('local_competvetsuivi', 'userdatafilepath');
        if (is_dir($userdatafilepath)) {
            $filetoprocess = null;
            foreach (glob("{$userdatafilepath}/*.csv") as $filename) {
                if (userdata::check_file_valid($filename)) {
                    $filetoprocess = $filename;
                    $status = userdata::import_user_data_from_file($filename);

                    // Delete the file if imported successfully.
                    if ($status === true) {
                        unlink($filename);
                    }
                    break;
                }
            }
        }
    }
}

