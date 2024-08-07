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
 * Manage survey
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_enva\local;

use coding_exception;
use csv_export_writer;
use dml_exception;
use dml_transaction_exception;
use moodle_exception;
use stdClass;

/**
 * Class manage_survey
 *
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_survey {

    /**
     * @var $ENVA_SURVEY_DUMMY_DATA
     */
    const ENVA_SURVEY_DUMMY_DATA = 'Autre';

    /**
     * Export cohorts as CSV, year one with empty data
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
     * Export cohorts as CSV, year one with empty data
     *
     * @param string $filename
     * @return csv_export_writer
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_yearone_users_with_empty_data($filename = "") {
        global $DB, $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        list($sql, $params) = self::get_sql_yearone_users_with_empty_data();
        $rs = $DB->get_recordset_sql($sql, $params);
        $csvexport = new csv_export_writer();
        $csvexport->set_filename($filename ? $filename : 'emptydata.csv');
        $csvexport->add_data([
            'userid',
            'username',
            'email',
            'lastname',
            'firstname',
            'cohort',
            'customfieldname',
            'userinfodataid',
        ]);
        foreach ($rs as $res) {
            $csvexport->add_data((array) $res);
        }

        return $csvexport;
    }

    /**
     * Get year one users with empty data
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

        return [$sqlquery, array_merge($paramsfieldid, ['yearonename' => 'A1'])];
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
        $studentcohortsid = self::get_survey_to_reset_cohorts_list();
        // Select all user fields which are named 'choix...'.
        $selecteduserfields = $DB->get_fieldset_select('user_info_field', "id", "shortname LIKE 'choix%'");
        list($sqlcohortid, $paramscohortid) = $DB->get_in_or_equal($studentcohortsid, SQL_PARAMS_NAMED, 'pcohort');
        list($sqlfieldid, $paramsfieldid) = $DB->get_in_or_equal($selecteduserfields, SQL_PARAMS_NAMED, 'pfield');

        return [$sqlcohortid, $paramscohortid, $sqlfieldid, $paramsfieldid];
    }

    // CLI Tools.

    /**
     * Get survey cohort list
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_survey_to_reset_cohorts_list(): array {
        static $allcohortsid = null;
        if (is_null($allcohortsid)) {
            global $DB;
            $allcohortsid = [];
            $cohortstoresetnames = get_config('tool_enva', 'cohortstoreset');
            if (!empty($cohortstoresetnames)) {
                $cohortstoresetarray = array_map('static::remove_spaces_lowercase',
                    explode(',', $cohortstoresetnames));
                // We need to match strings that can have been spaced out quite randomly, so no sql here.
                $allcohorts = array_map('static::remove_spaces_lowercase',
                    $DB->get_records_menu('cohort', [], '', 'id,idnumber'));
                $allcohortsid = array_intersect($allcohorts, $cohortstoresetarray);
            }
        }
        return array_keys($allcohortsid);
    }

    /**
     * Remove spaces and lowercase
     *
     * @param string $entry
     * @return string
     */
    private static function remove_spaces_lowercase(string $entry): string {
        return preg_replace("/\s+/", "", strtolower($entry));;
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
            $studentcohortsid = self::get_survey_to_reset_cohorts_list();
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
                    $exists = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid]);
                    if (!$exists) {
                        $DB->insert_record('user_info_data', $userdatafield);
                    } else {
                        if (empty($exists->data)) {
                            $userdatafield->id = $exists->id;
                            $DB->update_record('user_info_data', $userdatafield);
                        }
                    }
                }
            }
            $transaction->allow_commit();
        } catch (moodle_exception $e) {
            debugging($e->getMessage() . '-' . $e->getTraceAsString());
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
