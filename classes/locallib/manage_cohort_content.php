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
 * Manage cohort content
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_enva\locallib;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use csv_export_writer;
use dml_exception;
use dml_transaction_exception;
use moodle_exception;
use stdClass;

/**
 * Class manage_cohort_content
 *
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_cohort_content {

    const ENVA_SURVEY_DUMMY_DATA = 'Autre';

    /**
     *
     *
     * @param string $filename
     * @return string
     * @throws dml_exception
     */
    public static function print_export_cohorts($filename = "") {
        $csvexport = self::export_cohorts_to_csv($filename);

        return $csvexport->print_csv_data();
    }

    /**
     *
     *
     * @param string $filename
     * @return csv_export_writer
     * @throws dml_exception
     */
    public static function export_cohorts_to_csv($filename = "") {
        global $DB;
        $query = "SELECT  u.username, u.lastname, u.firstname, c.name, c.id
			  FROM {user} u, {cohort} c, {cohort_members} cm
		      WHERE cm.cohortid = c.id and cm.userid = u.id
		      ORDER BY c.id";

        $rs = $DB->get_recordset_sql($query);
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename ? $filename : 'cohorts.csv');
        $csvexport->add_data(array('username', 'lastname', 'firstname', 'cohort', 'cohortid'));
        foreach ($rs as $res) {
            $csvexport->add_data((array) $res);
        }

        return $csvexport;
    }

    /**
     *
     *
     * @param string $filename
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function print_yearone_users_with_empty_data($filename = "") {
        $csvexport = self::export_yearone_users_with_empty_data($filename);

        return $csvexport->print_csv_data();
    }

    // For admin UI options.

    /**
     *
     *
     * @param string $filename
     * @return csv_export_writer
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_yearone_users_with_empty_data($filename = "") {
        global $DB;
        list($sql, $params) = self::get_sql_yearone_users_with_empty_data();
        $rs = $DB->get_recordset_sql($sql, $params);
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename ? $filename : 'emptydata.csv');
        $csvexport->add_data(array(
            'userid',
            'username',
            'email',
            'lastname',
            'firstname',
            'cohort',
            'customfieldname',
            'userinfodataid'
        ));
        foreach ($rs as $res) {
            $csvexport->add_data((array) $res);
        }

        return $csvexport;
    }

    /**
     *
     *
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_sql_yearone_users_with_empty_data() {
        list($sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid) = self::get_sql_user_data_parts();
        // We ignore the sqlcohortid param as we just take A1 as a cohort.
        $sqlquery = "SELECT DISTINCT
				u.id, u.username, u.email, u.firstname, u.lastname, c.name, ufd.shortname AS ufdshortname , uid.id AS ufdid
				FROM {user_info_data} uid
				LEFT JOIN {user_info_field} ufd ON ufd.id = uid.fieldid
				LEFT JOIN {user} u ON uid.userid = u.id
				LEFT JOIN {cohort_members} cm ON cm.userid = u.id
				LEFT JOIN {cohort} c ON cm.cohortid = c.id
                WHERE uid.data=\"\" AND c.name = :yearonename AND ufd.id {$sqlfieldid}";

        return array($sqlquery, array_merge($paramsfieldid, array('yearonename' => 'A1')));
    }

    /**
     * Get SQL query to retrieve user info field data for the survey
     *
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_sql_user_data_parts() {
        global $DB;
        $studentcohortsid = self::get_survey_cohorts_list();
        // Select all user fields which are named 'choix...'.
        $selecteduserfields = $DB->get_fieldset_select('user_info_field', "id", "shortname LIKE 'choix%'");
        list($sqlcohortid, $paramscohortid) = $DB->get_in_or_equal($studentcohortsid, SQL_PARAMS_NAMED, 'pcohort');
        list($sqlfieldid, $paramsfieldid) = $DB->get_in_or_equal($selecteduserfields, SQL_PARAMS_NAMED, 'pfield');

        return array($sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid);
    }

    // CLI Tools.

    /**
     *
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_survey_cohorts_list() {
        global $CFG;

        $studentcohortsid = self::get_master_student_cohort_ids();
        if (!empty($CFG->additionalstudentcohorts)) {
            $studentcohortsid = array_merge($studentcohortsid, $CFG->additionalstudentcohorts);
        }

        return $studentcohortsid;
    }

    /**
     * Get master student cohort ID
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_master_student_cohort_ids() {
        global $DB;
        $rexp = $DB->sql_regex() . " '^A[0-9].*$'";

        return $DB->get_fieldset_select('cohort', 'id', 'name ' . $rexp);
    }

    // Field deletion.

    /**
     *
     * Delete all user info data for all involved cohort so we trigger the the form when user first logs in
     *
     * @throws dml_transaction_exception
     */
    public static function delete_user_yearly_surveyinfo() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            // First : delete all user fields which are named 'choix...'.
            list($sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid) = self::get_sql_user_data_parts();
            $sqlselect = "fieldid {$sqlfieldid}
			AND EXISTS (SELECT id
                FROM {cohort_members} cm
                WHERE {user_info_data}.userid = cm.userid AND cm.cohortid {$sqlcohortid}  LIMIT 1)";
            $DB->delete_records_select('user_info_data', $sqlselect, array_merge($paramscohortid, $paramsfieldid));

            // Then : add dummy data ('Autre') into fields related to other users.
            $studentcohortsid = self::get_survey_cohorts_list();
            $selecteduserfields = $DB->get_fieldset_select('user_info_field', "id", "shortname LIKE 'choix%'");
            $allnonstudents = $DB->get_fieldset_sql('SELECT DISTINCT userid FROM {cohort_members} WHERE cohortid NOT IN (' .
                implode(',', $studentcohortsid) . ')');
            $userdatafield = new stdClass();
            foreach ($allnonstudents as $userid) {
                foreach ($selecteduserfields as $fieldid) {
                    $userdatafield->userid = $userid;
                    $userdatafield->data = self::ENVA_SURVEY_DUMMY_DATA;
                    $userdatafield->dataformat = '0';
                    $userdatafield->fieldid = $fieldid;
                    $exists = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => $fieldid));
                    if (!$exists) {
                        $DB->insert_record('user_info_data', $userdatafield);
                    } else {
                        $userdatafield->id = $exists->id;
                        $DB->update_record('user_info_data', $userdatafield);
                    }
                }
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
        $transaction->dispose();
    }

    /**
     * Delete user info data only for year one user for data which is null or empty string (""), so we trigger the form for
     * newly created in year A1
     *
     */
    public static function delete_user_surveyinfo_yearone_when_empty() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            // Obviously here we could have done a delete_records_select, but we wanted to use the exact same query
            // as the export function.
            list($sql, $params) = self::get_sql_yearone_users_with_empty_data();
            $rs = $DB->get_recordset_sql($sql, $params);
            $uidatatodelete = [];
            foreach ($rs as $r) {
                $uidatatodelete[] = $r->ufdid;
            }
            $DB->delete_records_list('user_info_data', 'id', $uidatatodelete);
            $transaction->allow_commit();
        } catch (moodle_exception $e) {
            $transaction->rollback($e);
        }
        $transaction->dispose();
    }

}