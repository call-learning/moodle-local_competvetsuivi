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
 * Upload user data from a file
 *
 * @package     local_competvetsuivi
 * @category    upload user data
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognised) = cli_get_params([
        'help' => false,
        'filename' => null,
], [
        'h' => 'help'
]);

$usage = "Upload user data manually. Same process as the scheduled task

Usage:
    # php uploaduserdata.php --filename=<filepath>
    # php uploaduserdata.php [--help|-h]

Options:
    -h --help                   Print this help.
    --filename=<filepath></filepath>                 Filename of the file to upload (full path)
";

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

if ($options['filename'] === null || !file_exists($options['filename'])) {
    $a = (object) array('option' => 'filename', 'value' => $options['filename']);
    cli_error(get_string('cliincorrectvalueerror', 'admin', $a));
}

$status = local_competvetsuivi\userdata::import_user_data_from_file($options['filename']);
if ($status === true) {
    cli_writeln(get_string('success'));
} else {
    cli_writeln(get_string('failed'));
    foreach ($status as $error => $params) {
        cli_writeln(get_string($error, 'local_competvetsuivi', $params));
    }
}
