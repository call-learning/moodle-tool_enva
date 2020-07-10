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
$string['managecohortcontent'] = 'Cohort content';
$string['managecohortsync'] = 'Cohort Course Synchronisation';
$string['managegroupsync'] = 'Group Course Synchronisation';
$string['downloadcohortdata'] = 'Download Cohorts as CSV';
$string['downloademptysurvey'] = 'Download Student with empty survey data';
$string['deletesurveyinfo'] = 'Delete Survey Data';
$string['deleteyearoneemptysurvey'] = 'Delete empty year one survey';
$string['deletesurveyinfoconfirm'] = 'Confirm Survey Data Deletion';
$string['deleteyearoneemptysurveyconfirm'] = 'Confirm Empty Survey Data for year one deletion';
$string['emptyyearonesurveydatatask'] = 'ENVA: Delete empty survey data Task';

$string['groupsyncfile:def'] = 'Group definition file';
$string['cohortsyncfile:def'] = 'Cohort sync definition file';
$string['tool/enva:managecohortcontent'] = 'Can manage cohort content';
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
$string['importgroupsync:error:wronggroups'] = 'Wrong group (Line:{$a})';
$string['importcohortsync:error:cannotaddinstance'] = 'Cannot add cohort sync instance  (Line:{$a})';
$string['importcohortsync:error:cannotupdateinstance'] = 'Cannot update cohort sync instance (Line:{$a})';
$string['importcohortsync:error:wrongcourse'] = 'Wrong course (Line:{$a})';
$string['importcohortsync:error:wrongcohort'] = 'Wrong cohort (Line:{$a})';
$string['importcohortsync:error:wrongrole'] = 'Wrong role (Line:{$a})';
