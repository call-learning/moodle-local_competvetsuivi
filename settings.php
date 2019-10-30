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
 * Plugin administration pages are defined here.
 *
 * @package     local_competvetsuivi
 * @category    admin
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $compvetmanagement = new admin_category(
            'competvetmanagement',
            get_string('competvetmanagement', 'local_competvetsuivi')
    );

    $globalsettings = new admin_settingpage('competvetgeneralsettings',
            new lang_string('competvetgeneralsettings', 'local_competvetsuivi'),
            array('local/competvetsuivi:managesettings'),
            empty($CFG->enablecompetvetsuivi));

    $globalsettings->add(new admin_setting_configfile('local_competvetsuivi/userdatafilepath',
                    new lang_string('userdatafilepath', 'local_competvetsuivi'),
                    new lang_string('userdatafilepath_desc', 'local_competvetsuivi'),
                    '/tmp/usermatrix/')
    );

    $compvetmanagement->add('competvetmanagement', $globalsettings);

    $pagedesc = get_string('managematrix', 'local_competvetsuivi');
    $pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/matrix/list.php');

    $compvetmanagement->add('competvetmanagement',
            new admin_externalpage(
                    'managematrix',
                    $pagedesc,
                    $pageurl,
                    array('local/competvetsuivi:managesettings'),
                    empty($CFG->enablecompetvetsuivi)
            )
    );

    if (!empty($CFG->enablecompetvetsuivi)) {
        $ADMIN->add('root', $compvetmanagement);
    }

    // Create a global Advanced Feature Toggle
    $optionalsubsystems = $ADMIN->locate('optionalsubsystems');
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecompetvetsuivi',
                    new lang_string('enablecompetvetsuivi', 'local_competvetsuivi'),
                    new lang_string('enablecompetvetsuivi_help', 'local_competvetsuivi'),
                    1)
    );
}
