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

namespace tool_enva\local\csv;

use coding_exception;
use core\task\manager;
use dml_exception;
use dml_transaction_exception;
use ReflectionClass;
use stdClass;
use tool_enva\task\sync_all_course_cohort_enrol;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the implementation of the cohort importer. Based from lpimportcsv
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_sync_importer extends base_csv_importer {
    /**
     * Plugin name
     */
    const COHORT_SYNC_ENROL_PLUGIN_NAME = 'cohort';

    /**
     * @var \enrol_plugin|null $cohortsyncenrolplugin
     */
    protected $cohortsyncenrolplugin = null;
    /**
     * @var array $coursestosync course to synchronise
     */
    protected $coursestosync = [];

    /**
     * Cohort_sync_importer constructor.
     *
     * @param null $text
     * @param null $encoding
     * @param null $delimiter
     * @param int $importid
     * @param string $type
     * @throws coding_exception
     */
    public function __construct($text = null, $encoding = null, $delimiter = null,
        $importid = 0, $type = 'tool_enva_cohort_sync_csv_import') {
        parent::__construct($text, $encoding, $delimiter, $importid, $type);
        $this->cohortsyncenrolplugin = enrol_get_plugin(self::COHORT_SYNC_ENROL_PLUGIN_NAME);
    }

    /**
     * Process import. Return false if import should be aborted due to error.
     *
     * @param array $row
     * @param int $rowindex
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function process_row($row, $rowindex) {
        global $DB;
        list($course, $cohort, $role) = $this->get_components($row, $rowindex);
        if (!$course || !$cohort || !$role) {
            return false;
        }
        // Get an enrolment instance if it exists.
        $instance = $DB->get_record('enrol',
            array('courseid' => $course->id, 'enrol' => self::COHORT_SYNC_ENROL_PLUGIN_NAME, 'roleid' => $role->id,
                'customint1' => $cohort->id));
        // TODO: remove instance when they are disabled.
        if (!$instance) {
            $instance = (object) $this->cohortsyncenrolplugin->get_instance_defaults();
            $instance->id = null;
            $instance->courseid = $course->id;
            $instance->roleid = $role->id;
            $instance->name = self::create_enrolmnent_name($cohort->name, $role->name);
            $instance->status = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
            $instance->customint1 = $cohort->id;
            if (!$this->add_enrol_instance($course, (array) $instance)) {
                $this->fail(get_string('importcohortsync:error:cannotaddinstance', 'tool_enva', $rowindex));
                return false;
            }
        } else {
            if (!$this->update_enrol_instance($instance,
                (object) ['name' => self::create_enrolmnent_name($cohort->name, $role->name),
                    'status' => ENROL_INSTANCE_ENABLED])) {
                $this->fail(get_string('importcohortsync:error:cannotupdateinstance', 'tool_enva', $rowindex));
                return false;
            }
        }

        return true;
    }

    /**
     * Get all martching components
     *
     * @param array $row
     * @param int $rowindex
     * @return array|null
     * @throws coding_exception
     */
    protected function get_components($row, $rowindex) {
        $course = $this->get_course($row);
        if (!$course) {
            $this->fail(get_string('importcohortsync:error:wrongcourse', 'tool_enva', $rowindex));
            return null;
        }
        $cohort = $this->get_cohort($row);
        if (!$cohort) {
            $this->fail(get_string('importcohortsync:error:wrongcohort', 'tool_enva', $rowindex));
            return null;
        }
        $role = $this->get_role($row);
        if (!$role) {
            $this->fail(get_string('importcohortsync:error:wrongrole', 'tool_enva', $rowindex));
            return null;
        }
        return array($course, $cohort, $role);
    }

    /**
     * Get course
     *
     * @param array $row
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_course($row) {
        global $DB;
        $courseid = $this->get_column_data($row, 'courseid');
        return $DB->get_record('course', array('id' => $courseid));
    }

    /**
     * Get cohort
     *
     * @param array $row
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_cohort($row) {
        global $DB;
        $cohortidnumber = $this->get_column_data($row, 'cohort_idnumber');
        return $DB->get_record('cohort', array('idnumber' => $cohortidnumber));
    }

    /**
     * Get role
     *
     * @param array $row
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_role($row) {
        global $DB;
        $roleshortname = $this->get_column_data($row, 'role_shortname');
        return $DB->get_record('role', array('shortname' => $roleshortname));
    }

    /**
     * Create a human readable name for the enrolment name
     *
     * @param string $cohortname
     * @param string $rolename
     */
    public static function create_enrolmnent_name($cohortname, $rolename) {
        return get_string('sync:enrolmentname', 'tool_enva',
            (object) (compact('cohortname', 'rolename')));
    }

    /**
     * Add new instance of enrol plugin.
     * This a a partial copy of the equivalent for the cohort enrol plugin without
     * a call to enrol cohort sync. This is making the process too slow, so we do it once
     * everything is setup.
     *
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    protected function add_enrol_instance($course, array $fields = null) {
        // Here we just create the new the plugin data. We will course enrolment later.
        $parentpluginclass = (new ReflectionClass($this->cohortsyncenrolplugin))->getParentClass();
        $addinstance = $parentpluginclass->getMethod('add_instance');
        $result =
            $addinstance->invokeArgs($this->cohortsyncenrolplugin, [$course, $fields]);

        $this->coursestosync[$course->id] = true; // Mak it as to be synced.

        return $result;
    }

    /**
     * Update instance of enrol plugin.
     *
     * @param object $instance
     * @param object $data modified instance fields
     * @return boolean
     */
    protected function update_enrol_instance($instance, $data) {
        // Here we just update the plugin data. We will course enrolment later.
        $parentpluginclass = (new ReflectionClass($this->cohortsyncenrolplugin))->getParentClass();
        $addinstance = $parentpluginclass->getMethod('update_instance');
        $result =
            $addinstance->invokeArgs($this->cohortsyncenrolplugin, [$instance, $data]);
        // We just add the plugin instance.
        $this->coursestosync[$instance->courseid] = true; // Mak it as to be synced.

        return $result;
    }

    /**
     * Validate import. Return false if import should be aborted due to error.
     *
     * @param array $row
     * @param int $rowindex
     * @return bool
     */
    public function validate_row($row, $rowindex) {
        list($course, $cohort, $role) = $this->get_components($row, $rowindex);
        if (!$course || !$cohort || !$role) {
            return false;
        }
        return true;
    }

    /**
     * List headers
     *
     * @return array|string[]
     */
    public function list_required_headers() {
        return array(
            'courseid', 'cohort_idnumber', 'role_shortname'
        );
    }

    /**
     * Finish import process import.
     *
     * @return void
     * @throws dml_transaction_exception
     */
    public function end_import_process() {
        global $DB;
        $DB->commit_delegated_transaction($this->currenttransaction);
        // Now launch update for course sync.
        $cohortsync = new sync_all_course_cohort_enrol();
        $cohortsync->set_blocking(true);
        $cohortsync->set_custom_data(array('courses' => array_keys($this->coursestosync)));
        manager::queue_adhoc_task($cohortsync);
    }
}
