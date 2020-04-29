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
 * User Data management page
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once('user_data_form.php');

admin_externalpage_setup('userdatamgmt');
require_login();

// Override pagetype to show blocks properly.
$header = get_string('competvetuserdatamgmt', 'local_competvetsuivi');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/userdata.php');

$PAGE->set_url($pageurl);

$mform = new user_data_form();

$defaultdt = [];
if (get_config('local_competvetsuivi', 'userdatafilepath')) {
    $defaultdt['userdatafilepath'] = get_config('local_competvetsuivi', 'userdatafilepath');
}
$message = "";
if ($mform->is_cancelled()) {
    redirect($pageurl);
} else if ($data = $mform->get_data()) {
    // Set the right value for userdatafilepath or/and upload the file.
    if (file_exists($data->userdatafilepath)) {
        set_config('userdatafilepath', $data->userdatafilepath, 'local_competvetsuivi');
        $defaultdt['userdatafilepath'] = $data->userdatafilepath;
    }
    if ($mform->get_new_filename('filetoupload')) {
        $tempfile = $mform->save_temp_file('filetoupload');
        $delimiter = $data->delimiter_name;
        $status = local_competvetsuivi\userdata::import_user_data_from_file($tempfile, $delimiter);
        if ($status === true) {
            /* @var $OUTPUT core_renderer Core renderer */
            $message = $OUTPUT->notification(get_string('userdataimported', 'local_competvetsuivi'), 'notifysuccess');
        } else {
            $errormsg = "";
            if (key_exists('errormsg', $status)) {
                $errormsg = $status['errormsg'];
            }

            $message = $OUTPUT->notification(get_string('importerror',
                'local_competvetsuivi',
                $errormsg),
                'notifyfailure'
            );
        }
        unlink($tempfile); // Remove temp file.
    }
}

$mform->set_data($defaultdt);

$renderer = $PAGE->get_renderer('core');
$renderable = new local_competvetsuivi\renderable\userdata_log();

echo $OUTPUT->header();
echo $message;
$mform->display();
echo $renderer->render($renderable);
echo $OUTPUT->footer();
