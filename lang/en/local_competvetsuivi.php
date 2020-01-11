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
 * Plugin strings are defined here.
 *
 * @package     local_competvetsuivi
 * @category    string
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Compet Vetsuivi Utils';

$string['competencies'] = 'Competencies';
$string['competencyfullname'] = 'Full Name';
$string['competvetmanagement'] = 'Compet Vetsuivi';
$string['competvetuserdatamgmt'] = 'Manage user data importation';
$string['competvetgeneralsettings'] = 'General Settings';
$string['contribution:title'] = 'Percentage the {$a} contributes to competencies and knowledge for the <strong>whole cursus</strong>';
$string['currentprogress'] = 'Current Progress';
$string['directorydoesnotexist'] = 'Le répertoire n\'existe pas';
$string['enablecompetvetsuivi'] = 'Enable Compet Vetsuivi Plugin';
$string['enablecompetvetsuivi_help'] = 'Enable Compet Vetsuivi Plugin';
$string['foundnomatchingue'] = 'No matching UE/UC found';
$string['foundtoomanymatchingue'] = 'Too many matching UE/UC found';
$string['foundnomatchingcompetency'] = 'No matching Competency found';
$string['foundtoomanymatchingcompetency'] = 'Too many matching Competencies found';
$string['graphtitle:level0'] = 'Root Competencies';
$string['graphtitle:level1'] = 'Competencies';
$string['graphtitle:level2'] = 'Abilities';
$string['importerror'] = 'Importation error: {$a}';
$string['legend'] = 'Legend';
$string['managematrix'] = 'Manage Competencies Matrix';
$string['matrix:add'] = 'Add matrix';
$string['matrix:assigncohorts'] = 'Assign cohorts';
$string['matrixadded'] = 'Matrix Added';
$string['matrixcomptype:knowledge'] = 'Knowledge';
$string['matrixcomptype:ability'] = 'Ability';
$string['matrixcomptype:objective'] = 'Objective';
$string['matrixcomptype:evaluation'] = 'Evaluation';
$string['matrix:delete']= "Delete Matrix";
$string['matrixdeleted'] = 'Matrix Deleted';
$string['matrix:edit']= "Edit Matrix";
$string['matrixfileadd'] = 'Matrix file';
$string['matrixfileadd_help'] = 'Add a new matrix file. This should be an Excel formatted file with specific data and structure.';
$string['matrix:list'] = 'All matrix';
$string['matrix'] = 'Matrix';
$string['matrixname'] = 'Fullname';
$string['matrixassignedcohorts'] =  'Cohorts';
$string['matrixcohortsassignment'] = 'Matrix Cohort Assignment';
$string['matrixcohortsassignment_help'] = 'A1low to assign one or several cohort of users to a matrix';
$string['matrixshortname'] = 'Shortname';
$string['matrixupdated'] = 'Matrix Updated';
$string['matrixviewdatatitle'] = 'Viewing Matrix Data {$a->matrixname} for user {$a->username}';
$string['matrix:viewdata'] = 'View matrix for user';
$string['matrixviewtitle'] = 'Viewing Matrix {$a}';
$string['matrixuevscomp:viewgraphs'] = 'View Matrix VS Competencies';
$string['matrixuevscomptitle'] = 'Contribution of {$a->uename} the competencies and acquired knowledge';
$string['matrixuevscompgraphtitle:global'] = 'Contribution of {$a->uename} to the general progression';
$string['matrixuevscompgraphtitle:semester'] = 'Contribution of {$a->uename} to semester';
$string['matrix:view'] = 'View matrix {$a}';
$string['matrix:viewtestresults'] = 'View test results';
$string['milestone'] = 'Target leve at the end of Semester(n)';
$string['nomatrixerror'] = 'No worksheet with the prefix ${a} in the file uploaded';
$string['questionbankcategoryname'] = 'Question bank category name';
$string['questionbankcategoryname_desc'] = 'Question bank category name to pick Autoevaluation questions from';
$string['cvsquestionbankcategoryname_help'] = 'All questions for autoevaluation must be in the same question bank.
This question bank must have a specific category 
(voir: https://docs.moodle.org/38/en/Question_categories#Category_Set_Up_and_Management).
Another prerequisite: the question idnumber must be the exact short name of the competency (for example
COPREV ou COPREV.1).
';
$string['repartition:title'] = 'Percentage of competences and knowledge in {$a}';
$string['readmore'] = 'more';
$string['readless'] = 'less';
$string['semester'] = 'Semester';
$string['semester:x'] = 'Semester {$a}';
$string['selfassessment'] = 'Self-Assessment';
$string['userdatacsvuploadtask'] = 'User Data CSV Uploading';
$string['userdataimported'] = 'User Data Imported';
$string['userdatafilepath_desc'] = 'Directory for User Data CSV files. Once uploaded the files will be deleted.';
$string['userdatafilepath_help'] = 'Directory for User Data CSV files. Once uploaded the files will be deleted.';
$string['userdatafilepath'] = 'Directory for User Data CSV files';
$string['userdatadirectupload'] = 'Immediate upload of User Data';
$string['userdatafile'] = 'User data CSV file';
$string['userdatafile_desc'] = 'File containing user data as CSV';
$string['userdatafile_help'] = 'File containing user data as CSV';
$string['userdatauploaddate'] = 'Date';
$string['userdatainsertednb'] = 'Number of inserted users';
$string['userdataupdatednb'] = 'Number of updated users';
$string['usertestresults'] = 'User test results';

