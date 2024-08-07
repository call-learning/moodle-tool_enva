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
use dml_exception;
use stdClass;

/**
 * This is the implementation of the group importer. Based from lpimportcsv
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_sync_importer extends base_csv_importer {

    /**
     * Prefix for updated cohort syncs
     */
    const GROUP_SYNC_ENROL_SUFFIX = ' (auto)';

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
        list($course, $groups) = $this->get_components($row, $rowindex);
        if (!$course) {
            return false;
        }
        if (!$groups) {
            return true; // No group. We carry on.
        }
        // Purge all groups from the course if specified.
        $shouldpurge = $this->get_column_data($row, 'purge_groups');
        if ($shouldpurge) {
            foreach (groups_get_all_groups($course->id) as $group) {
                if (!in_array($group->name, $groups) && !in_array($group->idnumber, $groups)) {
                    groups_delete_group($group->id);
                }
            }
        }
        foreach ($groups as $g) {
            if ($shouldpurge) {
                // Purge groups with the same names that do not have any user in it.
                $othergroupssamename = $DB->get_records('groups', ['courseid' => $course->id, 'name' => $g]);
                $othergroupssameid = $DB->get_records('groups', ['courseid' => $course->id, 'idnumber' => $g]);
                $allgroups = array_merge($othergroupssamename, $othergroupssameid);
                foreach ($allgroups as $grouptodelete) {
                    $gmember = groups_get_members($grouptodelete->id);
                    if (!$gmember || !count($gmember)) {
                        groups_delete_group($grouptodelete->id);
                    }
                }
            }
            $newgroupdata = new stdClass();
            $newgroupdata->name = $g;
            $newgroupdata->idnumber = $g;
            $newgroupdata->courseid = $course->id;
            $newgroupdata->description = $g . self::GROUP_SYNC_ENROL_SUFFIX;
            $prevgroup = $DB->get_record('groups', ['courseid' => $course->id, 'name' => $g]);
            if (!$prevgroup) {
                $prevgroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $g]);
            }
            if ($prevgroup) {
                $newgroupdata = (object) array_merge((array) $prevgroup, (array) $newgroupdata);
                groups_update_group($newgroupdata);
            } else {
                $gid = groups_create_group($newgroupdata);
                if (!$gid) {
                    $this->fail(get_string('importgroupsync:error:cannotaddinstance', 'tool_enva', $rowindex));
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get all matching components
     *
     * @param array $row
     * @param int $rowindex
     * @return array|null
     * @throws coding_exception
     */
    protected function get_components($row, $rowindex) {
        $course = $this->get_course($row);
        if (!$course) {
            $this->fail(get_string('importgroupsync:error:wrongcourse', 'tool_enva', $rowindex));
            return null;
        }
        $groups = $this->get_groups($row);
        return [$course, $groups];
    }

    /**
     * Get course
     *
     * @param object $row
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_course($row) {
        global $DB;
        $courseid = $this->get_column_data($row, 'courseid');
        return $DB->get_record('course', ['id' => $courseid]);
    }

    /**
     * Get groups
     *
     * @param array $row
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_groups($row) {
        $grouplist = $this->get_column_data($row, 'groups');
        if ($grouplist) {
            $groups = explode(',', $grouplist);
            return $groups;
        }
        return false;
    }

    /**
     * Validate import. Return false if import should be aborted due to error.
     *
     * @param array $row
     * @param int $rowindex
     * @return bool
     */
    public function validate_row($row, $rowindex) {
        list($course, $groups) = $this->get_components($row, $rowindex);
        if (!$course) {
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
        return [
            'courseid', 'groups',
        ];
    }

}
