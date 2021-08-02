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
 * Tools for ENVA
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'ENVA Utils/Tools';

// Tools.
$string['managesurvey'] = 'Cohort content';
$string['managecohortsync'] = 'Cohort Course Synchronisation';
$string['managegroupsync'] = 'Group Course Synchronisation';
$string['downloademptysurvey'] = 'Download Student with empty survey data';
$string['deletesurveyinfo'] = 'Delete Survey Data';
$string['deleteyearoneemptysurvey'] = 'Delete empty year one survey';
$string['deletesurveyinfoconfirm'] = 'Confirm Survey Data Deletion';
$string['deleteyearoneemptysurveyconfirm'] = 'Confirm Empty Survey Data for year one deletion';
$string['emptyyearonesurveydatatask'] = 'ENVA: Delete empty survey data Task';

$string['groupsyncfile:def'] = 'Group definition file';
$string['groupsyncfile:def_help'] = 'Configuration file that will match a course and a set of groups. Minimum of 2 columns 
courseid, groups. Groups contains a list of group names that if they don\'t exist will be created';
$string['cohortsyncfile:def'] = 'Cohort sync definition file';
$string['cohortsyncfile:def_help'] = 'Configuration file that will match a course, a cohort and a role. Minimum of 3 columns 
courseid, cohort_idnumber et role_shortname';
$string['tool/enva:managesurvey'] = 'Can manage cohort content';
$string['tool/enva:managecohortsync'] = 'Can manage cohort synchronisation';
$string['tool/enva:managegroupsync'] = 'Can manage group synchronisation';
$string['csvdelimiter'] = 'CSV Delimiter';
$string['encoding'] = 'CSV Encoding';
$string['import'] = 'Import';
$string['syncallcohortcourses'] = 'Sync all cohort course';
$string['invalidimportfile'] = 'Invalid import file ({$a})';
$string['headernotpresent'] = 'Header not present ({$a})';
$string['currentimportprogress'] = 'Current import progress';
$string['cannotopenimporter'] = 'Cannot open Importer';
$string['importgroupsync:error:cannotaddinstance'] = 'Cannot add group instance (Line:{$a})';
$string['importgroupsync:error:wrongcourse'] = 'Wrong course (Line:{$a})';
$string['importcohortsync:error:cannotaddinstance'] = 'Cannot add cohort sync instance  (Line:{$a})';
$string['importcohortsync:error:cannotupdateinstance'] = 'Cannot update cohort sync instance (Line:{$a})';
$string['importcohortsync:error:wrongcourse'] = 'Wrong course (Line:{$a})';
$string['importcohortsync:error:wrongcohort'] = 'Wrong cohort (Line:{$a})';
$string['importcohortsync:error:wrongrole'] = 'Wrong role (Line:{$a})';
$string['messageprovider:syncfinished'] = 'Synchronisation of cohorts finished';
$string['message:syncallcohortfailed:title'] = 'Synchronising all cohort failed';
$string['message:syncallcohortfailed'] = 'The process of synchronising all cohort failed. Please
check the output of php enrol/cohort/cli/sync.php. {$a->error} - ({$a->trace})';
$string['message:syncallcohortok:title'] = 'Synchronising all cohort succeed.';
$string['message:syncallcohortok'] = 'The process of synchronising all cohort was a success.';
$string['settings:additionalstudentcohorts'] = 'Additionnal cohort for survey';
$string['settings:additionalstudentcohorts_help'] = 'Numerical ID separated by comma, which allow additional cohort to be considered
when managing the entry survey';
$string['sync:enrolmentname'] = 'toolenva::{$a->cohortname}({$a->rolename})';
$string['surveyparameters'] = 'Survey parameters';
