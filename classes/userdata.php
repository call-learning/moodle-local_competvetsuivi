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
 * User data class
 *
 * @package     local_competvetsuivi
 * @category    User data tools
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_competvetsuivi;

use csv_import_reader;

class userdata {

    const MAIL_COLUMN_NAME = 'Mail'; // TODO add this as a plugin parameter
    const LAST_UNIT_SEEN = 'LastUnitSeen'; // TODO This will be the new column if we need to check for last seen unit

    /**
     * Do a couple of checks on the file at hand to see if it contains the right data
     *
     * @param $filename
     * @return bool
     */
    public static function check_file_valid($filename) {
        $fileexists = file_exists($filename);
        $filemimetypecheck = $fileexists && in_array(mime_content_type($filename), array('text/csv', 'text/plain'));
        return $fileexists && $filemimetypecheck;
    }

    /**
     * Import/update user data into the database
     *
     * If errors are not fatal, then just stack them up in an array of messages and parameter for storage or later display
     *
     * @param $filename
     * @return true or a list of error (langstrings + eventual parameters) as an array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function import_user_data_from_file($filename) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/csvlib.class.php');
        $returnvalue = true;
        $type = 'local_competvetsuivi_userdata';
        $importid = csv_import_reader::get_new_iid($type);

        $importer = new csv_import_reader($importid, $type);
        $content = file_get_contents($filename);
        $importer->load_csv_content($content, 'utf-8', 'semicolon');

        // If there are no import errors then proceed.
        if (empty($importer->get_error())) {

            // Get header (field names).
            $headers = $importer->get_columns();
            self::trim_headers($headers);
            if (($columnerror = self::check_columns($headers)) === true) {

                $importer->init();
                $emailcolumnindex = array_search(static::MAIL_COLUMN_NAME, $headers);
                $useddataheaders = array_splice($headers, $emailcolumnindex + 1);
                // TODO: This is a hack: We either need to change the header in the user data source or the matrix
                $useddataheaders = array_map(function($label) {
                    return str_replace('UE', 'UC', $label);
                }, $useddataheaders);

                $inserteduser = 0;
                $updateduser = 0;
                while ($userrecord = $importer->next()) {
                    $useremail = $userrecord[$emailcolumnindex]; // Get user email
                    if (!trim($useremail)) continue; // Skip empty lines
                    // The email is followed by the data itself
                    $userdatarow = array_splice($userrecord, $emailcolumnindex + 1);

                    $userdata = new \stdClass();
                    $userdata->useremail = $useremail;

                    $finaldatarow = array();
                    $userdata->lastseenunit = $useddataheaders[0];
                    // Convert heading and content to a more useable one (like boolean)
                    foreach ($userdatarow as $k => $row) {
                        if ($row !== "") {
                            $userdata->lastseenunit = $useddataheaders[$k];
                        }
                        $userdatarow[$k] = $row ? 1 : 0;
                    }

                    // We combine the two array and render a json
                    $userdata->userdata = json_encode(array_combine($useddataheaders, $userdatarow));

                    $toupdate = $DB->get_field('cvs_userdata', 'id', array('useremail' => $useremail));
                    if ($toupdate) {
                        $userdata->id = $toupdate;
                        $DB->update_record('cvs_userdata', $userdata);
                        $updateduser++;
                    } else {
                        $DB->insert_record('cvs_userdata', $userdata);
                        $inserteduser++;
                    }
                }

                // Send an event after importation
                $eventparams = array('context' => \context_system::instance(),
                        'other' => array('filename' => $filename, 'inserted' => $inserteduser, 'updated' => $updateduser));
                $event = \local_competvetsuivi\event\userdata_imported::create($eventparams);
                $event->trigger();
            } else {
                $returnvalue = array('importerror' => get_string('csvloaderror', 'error', $columnerror));
            }
        } else {
            $returnvalue = array('importerror' => $importer->get_error());
        }

        $importer->cleanup();
        return $returnvalue;
    }

    public static function check_columns($columnsheaders) {
        if (!in_array(self::MAIL_COLUMN_NAME, $columnsheaders)) {
            return self::MAIL_COLUMN_NAME;
        }
        return true;
    }

    public static function get_user_data($useremail) {
        global $DB;
        $data = $DB->get_record('cvs_userdata', array('useremail' => $useremail));
        if ($data) {
            return json_decode($data->userdata, true);
        }
        return false;
    }

    public static function get_user_last_ue_name($useremail) {
        global $DB;
        $data = $DB->get_record('cvs_userdata', array('useremail' => $useremail));

        if ($data) {
            return $data->lastseenunit;
        }
        return "";
    }

    protected static function trim_headers(&$columnheaders) {
        foreach ($columnheaders as $i => $h) {
            $h = trim($h); // Remove whitespace.
            $h = clean_param($h, PARAM_RAW); // Clean the header.
            $columnheaders[$i] = $h;
        }
    }

}