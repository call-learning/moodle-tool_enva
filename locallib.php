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
 * @copyright  2019 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL' ) || die();

require_once( "{$CFG->libdir}/csvlib.class.php" );


define( 'ENVA_SURVEY_DUMMY_DATA', 'Autre' );


function export_cohorts_to_csv( $filename = "" ) {
	global $DB;
	$query = "SELECT  u.username, u.lastname, u.firstname, c.name, c.id
			  FROM {user} u, {cohort} c, {cohort_members} cm
		      WHERE cm.cohortid = c.id and cm.userid = u.id
		      ORDER BY c.id";
	
	$rs        = $DB->get_recordset_sql( $query );
	$csvexport = new csv_export_writer();
	$csvexport->set_filename( $filename ? $filename : 'cohorts.csv' );
	$csvexport->add_data( array( 'username', 'lastname', 'firstname', 'cohort', 'cohortid' ) );
	foreach ( $rs as $res ) {
		$csvexport->add_data( (array) $res );
	}
	
	return $csvexport;
}


function print_export_cohorts( $filename = "" ) {
	$csvexport = export_cohorts_to_csv( $filename );
	
	return $csvexport->print_csv_data();
}


function get_student_cohorts_id() {
	return [ 1, 2, 3, 4, 5, 6, 10, 11, 12, 17 ]; // Master + thesis + Interne
}

function get_master_student_cohort_ids() {
	global $DB;
	$rexp = $DB->sql_regex() . " '^A[0-9].*$'";
	
	return $DB->get_fieldset_select( 'cohort', 'id', 'name ' . $rexp );
}

function get_student_survey_nil_cohorts_id() {
	return [ 1, 2, 3, 4, 5, 6, 10, 11, 12, 17 ]; // Master + thesis + Interne
}

function delete_user_surveyinfo() {
	global $DB;
	try {
		$transaction = $DB->start_delegated_transaction();
		// First : delete all user fields which are named 'choix...'
		$selecteduserfields = $DB->get_fieldset_select( 'user_info_field', "id", "shortname LIKE 'choix%'" );
		$studentcohortsid = get_master_student_cohort_ids();
		// Delete all choice info fields for all users who belong to a cohort
		$DB->delete_records_select( 'user_info_data',
			'fieldid IN (' . implode( ',', $selecteduserfields ) . ')
			AND EXISTS (SELECT id
                FROM {cohort_members} cm WHERE {user_info_data}.userid = cm.userid AND cm.cohortid IN (' .
			implode( ',', $studentcohortsid )
			. ')  LIMIT 1)' );
		
		// Add dummy data ('Autre') into fields related to other users
		$allnonstudents   = $DB->get_fieldset_sql( 'SELECT DISTINCT userid FROM {cohort_members} WHERE cohortid NOT IN (' .
		                                           implode( ',', $studentcohortsid ) . ')' );
		$userdatafield    = new stdClass();
		foreach ( $allnonstudents as $userid ) {
			foreach ( $selecteduserfields as $fieldid ) {
				$userdatafield->userid     = $userid;
				$userdatafield->data       = ENVA_SURVEY_DUMMY_DATA;
				$userdatafield->dataformat = '0';
				$userdatafield->fieldid    = $fieldid;
				if (!$DB->record_exists('user_info_data', array ('userid'=>$userid, 'fieldid'=>$fieldid))) {
					$DB->insert_record( 'user_info_data', $userdatafield );
				}
			}
		}
		$transaction->allow_commit();
	} catch ( Exception $e ) {
		$transaction->rollback( $e );
	}
}