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
 * Tests for tools for ENVA
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_enva;
defined('MOODLE_INTERNAL') || die();

global $CFG;

use stdClass;
use tool_enva\local\csv\group_sync_importer;
use utils;

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/admin/tool/enva/tests/utils.php');

/**
 * Class group_sync
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_enva_groups_sync_test extends utils {
    /**
     * A simple import test
     */
    public function test_csv_import_simple() {
        $this->resetAfterTest(true);
        // Now do the group sync import.
        $importer = new group_sync_importer(file_get_contents(__DIR__ . '/fixtures/group_sync_example.csv'));

        $this->assertEquals('', $importer->get_error());

        $importer->process_import();

        $c502groups = array_values(array_map(function($g) {
            return $g->name;
        }, groups_get_all_groups(502)));
        foreach (["A1Gr4.1",
            "A1Gr4.2",
            "A1Gr4.3",
            "A1Gr4.4",
            "A1Gr8.1",
            "A1Gr8.2",
            "A1Gr8.3",
            "A1Gr8.4",
            "A1Gr8.5",
            "A1Gr8.6",
            "A1Gr8.7",
            "A1Gr8.8", ] as $key => $value) {
            $this->assertArrayHasKey($key, $c502groups);
            $this->assertSame($value, $c502groups[$key]);
        }

        $c604groups = array_values(array_map(function($g) {
            return $g->name;
        }, groups_get_all_groups(604)));

        foreach (["A3Gr4.1",
            "A3Gr4.2",
            "A3Gr4.3",
            "A3Gr4.4",
            "A3Gr8.1",
            "A3Gr8.2",
            "A3Gr8.3",
            "A3Gr8.4",
            "A3Gr8.5",
            "A3Gr8.6",
            "A3Gr8.7",
            "A3Gr8.8",
        ] as $key => $value) {
            $this->assertArrayHasKey($key, $c604groups);
            $this->assertSame($value, $c604groups[$key]);
        }

    }

    /**
     * A simple import with purged
     */
    public function test_csv_import_purged() {
        $this->resetAfterTest(true);

        // Create existing groups.
        $newgroupdata = new stdClass();
        $newgroupdata->name = 'existing group';
        $newgroupdata->courseid = 502;
        $newgroupdata->description = 'existing group';
        $gidpurged = groups_create_group($newgroupdata);

        $newgroupdata = new stdClass();
        $newgroupdata->name = 'existing group';
        $newgroupdata->courseid = 604;
        $newgroupdata->description = 'existing group';
        $gidnonpurged = groups_create_group($newgroupdata);

        // Now do the import.
        $importer = new group_sync_importer(file_get_contents(__DIR__ . '/fixtures/group_sync_example.csv'));

        $this->assertEquals('', $importer->get_error());

        $importer->process_import();

        $c502groupsid = array_values(array_map(function($g) {
            return $g->id;
        }, groups_get_all_groups(502)));

        $this->assertCount(12, $c502groupsid);
        $this->assertNotContains($gidpurged, $c502groupsid);

        $c604groupsid = array_values(array_map(function($g) {
            return $g->id;
        }, groups_get_all_groups(604)));
        $this->assertCount(13, $c604groupsid);
        $this->assertContains($gidnonpurged, $c604groupsid);
    }

    /**
     * Existing group modification
     */
    public function test_csv_import_purged_with_existing_modified() {
        global $DB;
        $this->resetAfterTest(true);

        // Create existing groups.
        $newgroupdata = new stdClass();
        $newgroupdata->name = 'A1Gr4.1';
        $newgroupdata->courseid = 502;
        $newgroupdata->description = 'existing group';
        $gidmodified = (int) groups_create_group($newgroupdata);

        // Add a user so we are sure the group is not purged.
        $user = $this->getDataGenerator()->create_user();
        $course = $DB->get_record('course', ['id' => 502]);
        // Create enrolment plugin.
        enrol_course_updated(true, $course, null);
        $this->getDataGenerator()->enrol_user($user->id, 502);
        groups_add_member($gidmodified, $user->id);

        $newgroupdata = new stdClass();
        $newgroupdata->name = 'existing group';
        $newgroupdata->courseid = 502;
        $newgroupdata->description = 'existing group';
        $gidpurged = (int) groups_create_group($newgroupdata);

        // Now do the import.
        $importer = new group_sync_importer(file_get_contents(__DIR__ . '/fixtures/group_sync_example.csv'));

        $this->assertEquals('', $importer->get_error());

        $importer->process_import();

        $allgroups = groups_get_all_groups(502);
        $c502groupsid = array_values(array_map(function($g) {
            return $g->id;
        }, $allgroups));

        $this->assertCount(12, $c502groupsid);
        $this->assertNotContains($gidpurged, $c502groupsid);
        $this->assertContainsEquals($gidmodified, $c502groupsid);
        $this->assertEquals('A1Gr4.1', $allgroups[$gidmodified]->name);
        $this->assertEquals('A1Gr4.1', $allgroups[$gidmodified]->idnumber);
    }
}
