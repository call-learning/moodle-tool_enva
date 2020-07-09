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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Class utils_tests
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_enva_base_test extends advanced_testcase {
    const USER_PER_COHORT = 10;
    public $users = array();

    public function setUp() {
        parent::setUp();
        global $DB;
        $this->resetAfterTest();
        // Setup custom profile fields.
        $dataset = $this->createCsvDataSet(array(
                'cohort' => __DIR__ . '/fixtures/cohort.csv',
                'course' => __DIR__ . '/fixtures/course.csv',
                'user_info_field' => __DIR__ . '/fixtures/user_info_field.csv',
                'role' => __DIR__ . '/fixtures/role.csv'
            )
        );
        $this->loadDataSet($dataset);

        $evecohorts = $DB->get_records('cohort');
        $i = 0;

        foreach ($evecohorts as $cohort) {
            for ($j = 0; $j < self::USER_PER_COHORT; $j++) { // 10 users in each cohort.
                $user = $this->getDataGenerator()->create_user();
                cohort_add_member($cohort->id, $user->id);
                $this->users[$i++] = $user;
            }
        }

    }

    public function tearDown() {
        parent::tearDown();
        $this->users = null;
    }
}