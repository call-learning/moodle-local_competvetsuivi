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
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $compvetmanagement = new admin_category(
            'competvetmanagement',
            get_string('competvetmanagement', 'local_competvetsuivi')
    );

    // General settings.
    $pagedesc = get_string('competvetgeneralsettings', 'local_competvetsuivi');
    $generalsettingspage = new admin_settingpage('competvetgeneral',
            $pagedesc,
            array('local/competvetsuivi:managesettings'),
            empty($CFG->enablecompetvetsuivi));

    $settingname = get_string('questionbankcategoryname', 'local_competvetsuivi');
    $settingdescription = get_string('questionbankcategoryname_desc', 'local_competvetsuivi');
    $settingdefault = 'Auto-evaluation_competences';

    $questionbankcategoryname = new admin_setting_configtext(
            'local_competvetsuivi/cvsquestionbankdefaultcategoryname',
            $settingname,
            $settingdescription,
            $settingdefault
    );
    $generalsettingspage->add($questionbankcategoryname);

    // Progress & Doghnut chart height.
    $settingname = get_string('progresschartheight', 'local_competvetsuivi');
    $settingdescription = get_string('progresschartheight_desc', 'local_competvetsuivi');
    $settingdefault = 108;

    $progresschartheight = new admin_setting_configtext(
            'local_competvetsuivi/progresschartheight',
            $settingname,
            $settingdescription,
            $settingdefault,
            PARAM_INT
    );
    $generalsettingspage->add($progresschartheight);

    $settingname = get_string('doghnutchartheight', 'local_competvetsuivi');
    $settingdescription = get_string('doghnutchartheight_desc', 'local_competvetsuivi');
    $settingdefault = 200;

    $doghnutchartheight = new admin_setting_configtext(
            'local_competvetsuivi/doghnutchartheight',
            $settingname,
            $settingdescription,
            $settingdefault,
            PARAM_INT
    );
    $generalsettingspage->add($doghnutchartheight);


    $compvetmanagement->add('competvetmanagement', $generalsettingspage);

    // Data management page.
    $pagedesc = get_string('competvetuserdatamgmt', 'local_competvetsuivi');
    $pageurl = new moodle_url($CFG->wwwroot . '/local/competvetsuivi/admin/userdata.php');
    $compvetmanagement->add('competvetmanagement',
            new admin_externalpage(
                    'userdatamgmt',
                    $pagedesc,
                    $pageurl,
                    array('local/competvetsuivi:managesettings'),
                    empty($CFG->enablecompetvetsuivi)
            )
    );

    // Matrix Management page.
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

    // Create a global Advanced Feature Toggle.
    $optionalsubsystems = $ADMIN->locate('optionalsubsystems');
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecompetvetsuivi',
                    new lang_string('enablecompetvetsuivi', 'local_competvetsuivi'),
                    new lang_string('enablecompetvetsuivi_help', 'local_competvetsuivi'),
                    1)
    );
}
