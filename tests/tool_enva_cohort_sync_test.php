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
use context_course;
use tool_enva\local\csv\cohort_sync_importer;
use tool_enva_base_test;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/admin/tool/enva/tests/utils.php');

/**
 * Class tool_enva_cohort_sync_test
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_enva_cohort_sync_test extends tool_enva_base_test {
    /**
     * Simple import
     */
    public function test_csv_import_simple() {
        global $DB;
        $this->resetAfterTest(true);
        $messagesink = $this->redirectMessages();

        // Create three test users before we do the import.
        // We expect them not to be enrolled until we trigger the adhoc task.
        // If we added the student after the import, they would be added automatically via the usual trigger.
        $usera1 = $this->create_user_in_cohort('A1');
        $usera2 = $this->create_user_in_cohort('A2');
        $usera5 = $this->create_user_in_cohort('A5-Bovine');

        // Now do the cohort sync import.
        $importer = new cohort_sync_importer(file_get_contents(__DIR__ . '/fixtures/cohort_sync_example.csv'));

        $this->assertEquals('', $importer->get_error());

        $importer->process_import();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $studentguestrole = $DB->get_record('role', array('shortname' => 'student_invite'));

        $cohortsyncassoc = array(
            '502' => array(
                'A1' => $studentrole->id,
                'A2' => $studentguestrole->id,
                'A3' => $studentguestrole->id,
                'A4' => $studentguestrole->id,
            ),
            '604' => array(
                'A5-Autre' => $studentrole->id,
                'A5-Bovine' => $studentrole->id,
                'A5-Canine' => $studentrole->id,
                'A5-Equine' => $studentrole->id,
            )
        );

        foreach ($cohortsyncassoc as $courseid => $cohortassoc) {
            foreach ($cohortassoc as $cohortidnumber => $roleid) {
                $cohort = $DB->get_record('cohort', array('idnumber' => $cohortidnumber));
                $role = $DB->get_record('role', array('id' => $roleid));
                $enrolrecord = $DB->get_record('enrol', array(
                    'courseid' => $courseid,
                    'enrol' => cohort_sync_importer::COHORT_SYNC_ENROL_PLUGIN_NAME,
                    'customint1' => $cohort->id,
                    'roleid' => $roleid), '*', MUST_EXIST);
                $this->assertNotEmpty($enrolrecord);
                $this->assertEquals(cohort_sync_importer::create_enrolmnent_name($cohort->name, $role->name), $enrolrecord->name);
                $this->assertEquals($cohort->id, $enrolrecord->customint1);
            }
        }

        $contextcourse502 = context_course::instance(502);
        $contextcourse604 = context_course::instance(604);

        // Cohort are not yet synced.
        $this->assertFalse(is_enrolled($contextcourse502, $usera1));
        $this->assertFalse(is_enrolled($contextcourse502, $usera2));
        $this->assertFalse(is_enrolled($contextcourse604, $usera5));
        // Now sync them.
        $this->runAdhocTasks();

        // Now asserts that all student are enrolled where they should.
        $this->assertTrue(is_enrolled($contextcourse502, $usera1));
        $this->assertTrue(is_enrolled($contextcourse502, $usera2));
        $this->assertTrue(is_enrolled($contextcourse604, $usera5));
        $this->assertTrue(user_has_role_assignment(
            $usera5->id,
            $studentrole->id,
            $contextcourse604->id));
        $this->assertTrue(user_has_role_assignment(
            $usera5->id,
            $studentrole->id,
            $contextcourse604->id));
        $this->assertTrue(user_has_role_assignment(
            $usera2->id,
            $studentguestrole->id,
            $contextcourse502->id));
        $this->assertEquals(1, $messagesink->count());
        $firstmessage = $messagesink->get_messages()[0];
        $this->assertEquals(get_string('message:syncallcohortok:title', 'tool_enva'),
            $firstmessage->subject);
    }
}
