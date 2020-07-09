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
require_once($CFG->dirroot . '/admin/tool/enva/locallib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once('./utils.php');

/**
 * Class utils_tests
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils_tests extends advanced_testcase {
    const USER_PER_COHORT = 10;
    public $users = array();

    public function setUp() {
        parent::setUp();
        global $DB;
        $this->resetAfterTest();
        // Setup custom profile fields.
        $dataset = $this->createCsvDataSet(array(
                'cohort' => __DIR__ . '/fixtures/cohort.csv',
                'user_info_field' => __DIR__ . '/fixtures/user_info_field.csv'
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

    public function test_delete_user_surveyinfo() {
        global $DB;
        $this->resetAfterTest(true);
        $useryearone = $this->users[0];
        $useryeartwo = $this->users[self::USER_PER_COHORT];
        $userpersonnel = $this->users[self::USER_PER_COHORT * 6];

        $this->set_user_profile_field($useryearone, array(
            'Provenance' => 'Etudiants Alfort',
            'choix1' => 'Vétérinaire praticien canin',
            'choix2' => 'Vétérinaire praticien canin',
            'choix3' => 'Vétérinaire praticien canin'
        ));
        $this->set_user_profile_field($useryeartwo, array(
            'Provenance' => 'Etudiants Alfort',
            'choix1' => 'Vétérinaire praticien canin',
            'choix2' => 'Vétérinaire praticien canin',
            'choix3' => 'Vétérinaire praticien canin'
        ));
        $this->set_user_profile_field($userpersonnel, array(
            'Provenance' => 'Personnels Alfort',
            'choix1' => 'Autre',
            'choix2' => 'Autre',
            'choix3' => 'Autre'
        ));

        delete_user_yearly_surveyinfo();

        $useryearonefields = profile_get_user_fields_with_data($useryearone->id);
        $useryeartwofields = profile_get_user_fields_with_data($useryeartwo->id);
        $userpersonnelfields = profile_get_user_fields_with_data($userpersonnel->id);

        $this->assertTrue(user_not_fully_set_up($useryearone));
        $this->assertTrue(user_not_fully_set_up($useryeartwo));
        $this->assertFalse(user_not_fully_set_up($userpersonnel));
        $this->assertTrue($useryearonefields[1]->data == "");
        $this->assertTrue($useryeartwofields[1]->data == "");
        $this->assertTrue($userpersonnelfields[1]->data == "Autre");

    }

    protected function set_user_profile_field($user, $fieldvalue) {
        static $evecustomprofilefields = null;
        if (!$evecustomprofilefields) {
            global $DB;
            $evecustomprofilefields = $DB->get_records('user_info_field');
        }
        foreach ($evecustomprofilefields as $cf) {
            profile_save_data((object) [
                'id' => $user->id,
                'profile_field_' . $cf->shortname => $fieldvalue [$cf->shortname],
            ]);
        }
    }

    public function test_delete_user_surveyinfo_yearone_when_empty() {
        global $DB;
        $this->resetAfterTest(true);
        $useryearone = $this->users[0];
        $useryearonewithresponse = $this->users[1];
        $useryeartwo = $this->users[self::USER_PER_COHORT];
        $userpersonnel = $this->users[self::USER_PER_COHORT * 6];

        $this->set_user_profile_field($useryearone, array(
            'Provenance' => 'Etudiants Alfort',
            'choix1' => '',
            'choix2' => '',
            'choix3' => ''
        ));
        $this->set_user_profile_field($useryearonewithresponse, array(
            'Provenance' => 'Etudiants Alfort',
            'choix1' => 'Vétérinaire praticien canin',
            'choix2' => 'Vétérinaire praticien canin',
            'choix3' => 'Vétérinaire praticien canin'
        ));
        $this->set_user_profile_field($useryeartwo, array(
            'Provenance' => 'Etudiants Alfort',
            'choix1' => 'Vétérinaire praticien canin',
            'choix2' => 'Vétérinaire praticien canin',
            'choix3' => 'Vétérinaire praticien canin'
        ));
        $this->set_user_profile_field($userpersonnel, array(
            'Provenance' => 'Personnels Alfort',
            'choix1' => 'Autre',
            'choix2' => 'Autre',
            'choix3' => 'Autre'
        ));

        delete_user_surveyinfo_yearone_when_empty();

        $useryearonefields = profile_get_user_fields_with_data($useryearone->id);
        $useryearonewithresponsefields = profile_get_user_fields_with_data($useryearonewithresponse->id);
        $useryeartwofields = profile_get_user_fields_with_data($useryeartwo->id);
        $userpersonnelfields = profile_get_user_fields_with_data($userpersonnel->id);

        $this->assertTrue(user_not_fully_set_up($useryearone));
        $this->assertFalse(user_not_fully_set_up($useryearonewithresponse));
        $this->assertFalse(user_not_fully_set_up($useryeartwo));
        $this->assertFalse(user_not_fully_set_up($userpersonnel));
        $this->assertTrue($useryearonefields[1]->data == ""); // This should be empty.
        $this->assertTrue($useryearonewithresponsefields[1]->data == "Vétérinaire praticien canin"); // We don't touch this field.
        $this->assertTrue($useryeartwofields[1]->data == "Vétérinaire praticien canin");// We don't touch this field.
        $this->assertTrue($userpersonnelfields[1]->data == "Autre"); // We don't touch this field.
    }

}


