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

// ****************************************************  Generic tools

function get_student_cohorts_id() {
	return [ 1, 2, 3, 4, 5, 6, 10, 11, 12, 17 ]; // Master + thesis + Interne
}

function get_master_student_cohort_ids() {
	global $DB;
	$rexp = $DB->sql_regex() . " '^A[0-9].*$'";
	
	return $DB->get_fieldset_select( 'cohort', 'id', 'name ' . $rexp );
}

function get_survey_cohorts_list() {
	global $CFG;
	
	$studentcohortsid = get_master_student_cohort_ids();
	if ( ! empty( $CFG->additionalstudentcohorts ) ) {
		$studentcohortsid = array_merge( $studentcohortsid, $CFG->additionalstudentcohorts );
	}
	
	return $studentcohortsid;
}

/**
 * Get SQL query to retrieve user info field data for the survey
 * @return array
 * @throws coding_exception
 * @throws dml_exception
 */
function get_sql_user_data_parts() {
	global $DB;
	$studentcohortsid = get_survey_cohorts_list();
	// Select all user fields which are named 'choix...'
	$selecteduserfields = $DB->get_fieldset_select( 'user_info_field', "id", "shortname LIKE 'choix%'" );
	list( $sqlcohortid, $paramscohortid ) = $DB->get_in_or_equal( $studentcohortsid, SQL_PARAMS_NAMED ,'pcohort');
	list( $sqlfieldid, $paramsfieldid ) = $DB->get_in_or_equal( $selecteduserfields, SQL_PARAMS_NAMED , 'pfield');
	
	return array( $sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid );
}

// ****************************************************  For admin UI options

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

function get_sql_yearone_users_with_empty_data() {
	global $DB;
	list( $sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid ) = get_sql_user_data_parts();
	// We ignore the sqlcohortid param as we just take A1 as a cohort
	$sqlquery = "SELECT DISTINCT
				u.id, u.username, u.email, u.firstname, u.lastname, c.name, ufd.shortname AS ufdshortname , uid.id AS ufdid
				FROM {user_info_data} uid
				LEFT JOIN {user_info_field} ufd ON ufd.id = uid.fieldid
				LEFT JOIN {user} u ON uid.userid = u.id
				LEFT JOIN {cohort_members} cm ON cm.userid = u.id
				LEFT JOIN {cohort} c ON cm.cohortid = c.id
                WHERE uid.data=\"\" AND c.name = :yearonename AND ufd.id {$sqlfieldid}";
	
	return array( $sqlquery, array_merge( $paramsfieldid, array( 'yearonename' => 'A1' ) ) );
}

function export_yearone_users_with_empty_data( $filename = "" ) {
	global $DB;
	list( $sql, $params ) = get_sql_yearone_users_with_empty_data();
	$rs        = $DB->get_recordset_sql( $sql, $params );
	$csvexport = new csv_export_writer();
	$csvexport->set_filename( $filename ? $filename : 'emptydata.csv' );
	$csvexport->add_data( array(
		'userid',
		'username',
		'email',
		'lastname',
		'firstname',
		'cohort',
		'customfieldname',
		'userinfodataid'
	) );
	foreach ( $rs as $res ) {
		$csvexport->add_data( (array) $res );
	}
	
	return $csvexport;
}

// ****************************************************  CLI Tools

function print_export_cohorts( $filename = "" ) {
	$csvexport = export_cohorts_to_csv( $filename );
	
	return $csvexport->print_csv_data();
}

function print_yearone_users_with_empty_data( $filename = "" ) {
	$csvexport = export_yearone_users_with_empty_data( $filename );
	
	return $csvexport->print_csv_data();
}

// ****************************************************  Field deletion

/**
 * Delete all user info data for all involved cohort so we trigger the the form when user first logs in
 * @throws dml_transaction_exception
 */
function delete_user_yearly_surveyinfo() {
	global $DB, $CFG;
	try {
		$transaction = $DB->start_delegated_transaction();
		
		// First : delete all user fields which are named 'choix...'
		list( $sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid ) = get_sql_user_data_parts();
		$sqlselect = "fieldid {$sqlfieldid}
			AND EXISTS (SELECT id
                FROM {cohort_members} cm
                WHERE {user_info_data}.userid = cm.userid AND cm.cohortid {$sqlcohortid}  LIMIT 1)";
		$DB->delete_records_select( 'user_info_data', $sqlselect, array_merge( $paramscohortid, $paramsfieldid ) );
		
		// Then : add dummy data ('Autre') into fields related to other users
		$studentcohortsid   = get_survey_cohorts_list();
		$selecteduserfields = $DB->get_fieldset_select( 'user_info_field', "id", "shortname LIKE 'choix%'" );
		$allnonstudents     = $DB->get_fieldset_sql( 'SELECT DISTINCT userid FROM {cohort_members} WHERE cohortid NOT IN (' .
		                                             implode( ',', $studentcohortsid ) . ')' );
		$userdatafield      = new stdClass();
		foreach ( $allnonstudents as $userid ) {
			foreach ( $selecteduserfields as $fieldid ) {
				$userdatafield->userid     = $userid;
				$userdatafield->data       = ENVA_SURVEY_DUMMY_DATA;
				$userdatafield->dataformat = '0';
				$userdatafield->fieldid    = $fieldid;
				$exists = $DB->get_record( 'user_info_data', array( 'userid' => $userid, 'fieldid' => $fieldid ));
				if ( ! $exists ) {
					$DB->insert_record( 'user_info_data', $userdatafield );
				} else {
					$userdatafield->id = $exists->id;
					$DB->update_record( 'user_info_data', $userdatafield );
				}
			}
		}
		$transaction->allow_commit();
	} catch ( Exception $e ) {
		$transaction->rollback( $e );
	}
}

/**
 * Delete user info data only for year one user for data which is null or empty string (""), so we trigger the form for
 * newly created in year A1
 * @throws dml_tr$studentcohortsidansaction_exception
 */
function delete_user_surveyinfo_yearone_when_empty() {
	global $DB;
	try {
		$transaction = $DB->start_delegated_transaction();
		// Obviously here we could have done a delete_records_select, but we wanted to use the exact same query
		// as the export function
		list( $sql, $params ) = get_sql_yearone_users_with_empty_data();
		$rs             = $DB->get_recordset_sql( $sql, $params );
		$uidatatodelete = [];
		foreach ( $rs as $r ) {
			$uidatatodelete[] = $r->ufdid;
		}
		$DB->delete_records_list( 'user_info_data', 'id', $uidatatodelete );
		$transaction->allow_commit();
	} catch ( Exception $e ) {
		$transaction->rollback( $e );
	}
}
