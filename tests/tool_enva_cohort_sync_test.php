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

use tool_enva\csv\cohort_sync_importer;

defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/admin/tool/enva/tests/utils.php');

/**
 * Class utils_tests
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_enva_cohort_sync_test extends tool_enva_base_test {
    public function test_csv_import_simple() {
        global $DB;
        $this->resetAfterTest(true);

        $importer = new cohort_sync_importer(file_get_contents(__DIR__ . '/fixtures/cohort_sync_example.csv'));

        $this->assertEquals('', $importer->get_error());

        $importer->process_import();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $studentguestrole = $DB->get_record('role', array('shortname'=>'student_invite'));

        $cohortsync_assoc = array(
            '502' => array(
                'A1' => $studentrole->id,
                'A2' => $studentguestrole->id,
                'A3' => $studentguestrole->id,
                'A4' => $studentguestrole->id,
            ),
            '604' => array(
                'A5-Autre' => $studentrole->id,
                'A5-Bovine' => $studentguestrole->id,
                'A5-Canine' => $studentguestrole->id,
                'A5-Equine' => $studentguestrole->id,
            )
        );
        foreach($cohortsync_assoc as $courseid => $cohortassoc) {
            foreach($cohortassoc as $cohortidnumber => $roleid) {
                $cohortid = $DB->get_field('cohort','id', array('idnumber'=>$cohortidnumber));
                $enrolrecord =$DB->get_record('enrol', array(
                    'courseid' => $courseid,
                    'enrol' => cohort_sync_importer::COHORT_SYNC_ENROL_PLUGIN_NAME,
                    'customint1' => $cohortid,
                    'roleid' => $roleid), '*', MUST_EXIST);
                $this->assertTrue($enrolrecord);
                $this->assertStringStartsWith(cohort_sync_importer::COHORT_SYNC_ENROL_PREFIX, $enrolrecord->name);
                $this->assertEquals($cohortid, $enrolrecord->itemid);
            }
        }

    }
}


