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

namespace tool_enva\csv;
defined('MOODLE_INTERNAL') || die();

/**
 * This file contains the abstract class to do csv import.
 * Based from lpimportcsv
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_sync_importer extends base_csv_importer {
    protected $cohort_sync_enrol_plugin = null;

    const COHORT_SYNC_ENROL_PLUGIN_NAME = 'cohort';
    const COHORT_SYNC_ENROL_PREFIX = 'tool_enva:';

    public function __construct($text = null, $type = 'tool_enva_cohort_sync_csv_import', $encoding = null, $delimiter = null, $importid = 0,
        $mappingdata = null, $useprogressbar = false) {
        parent::__construct($text, $type, $encoding, $delimiter, $importid, $mappingdata, $useprogressbar);
        $this->cohort_sync_enrol_plugin  = enrol_get_plugin(self::COHORT_SYNC_ENROL_PLUGIN_NAME);
    }

    /**
     * Process import. Return false if import should be aborted due to error.
     *
     * @param object $row
     * @return bool
     */
    public function process_row($row, $rowindex) {
        global $DB;
        $course = $this->get_course($row);
        if (!$course) {
            $this->fail(get_string('importcohortsync:error:wrongcourse', 'tool_enva', $rowindex));
            return false;
        }
        $cohort = $this->get_cohort($row);
        if (!$cohort) {
            $this->fail(get_string('importcohortsync:error:wrongcohort', 'tool_enva', $rowindex));
            return false;
        }
        $role = $this->get_role($row);
        if (!$role) {
            $this->fail(get_string('importcohortsync:error:wrongrole', 'tool_enva', $rowindex));
            return false;
        }
        // Get an enrolment instance if it exists
        $instance = $DB->get_record('enrol',
            array('courseid' => $course->id, 'enrol' => self::COHORT_SYNC_ENROL_PLUGIN_NAME, 'roleid' => $role->id,
                'customint1'=> $cohort->id));
        // TODO: remove instance when they are disabled.
        if (!$instance) {
            $instance = (object)$this->cohort_sync_enrol_plugin->get_instance_defaults();
            $instance->id       = null;
            $instance->courseid = $course->id;
            $instance->roleid = $role->id;
            $instance->name   = self::COHORT_SYNC_ENROL_PREFIX.$cohort->name;
            $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
            $instance->customint1 = $cohort->id;
            // This can be a very long process here.
            $this->cohort_sync_enrol_plugin->add_instance($course, array($instance));
        } else {
            // This can be a very long process here, so as we just change the name, we just update the database record.
            $instance->name = self::COHORT_SYNC_ENROL_PREFIX.$cohort->name;
            $DB->update_record('enrol', $instance);
        }
        return true;
    }

    protected function get_course($row) {
        global $DB;
        $courseid = $this->get_column_data($row, 'courseid');
        return $DB->get_record('cohort', array('id' => $courseid));
    }

    protected function get_cohort($row) {
        global $DB;
        $cohortidnumber = $this->get_column_data($row, 'cohort_idnumber');
        return $DB->get_record('cohort', array('idnumber' => $cohortidnumber));
    }

    protected function get_role($row) {
        global $DB;
        $roleshortname = $this->get_column_data($row, 'role_shortname');
        return $DB->get_record('role', array('shortname' => $roleshortname));
    }

    public function list_required_headers() {
        return array(
            'courseid', 'cohort_idnumber', 'role_shortname'
        );
    }
}
