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
 * Tools for ENVA
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    global $ADMIN, $CFG;

    $envatools = new admin_category(
        'tool_enva',
        get_string('pluginname', 'tool_enva')
    );

    // Manage survey parameters.
    $surveypage = new admin_settingpage(
        'envasurvey',
        get_string('surveyparameters', 'tool_enva'),
        'tool/enva:managesurvey');
    $envatools->add('tool_enva', $surveypage);

    // Replacement and patterns for group name (see preg_replace).
    $surveypage->add(
        new admin_setting_configtext(
            'tool_enva/additionalstudentcohorts',
            get_string('settings:additionalstudentcohorts', 'tool_enva'), // Label.
            get_string('settings:additionalstudentcohorts_help', 'tool_enva'), // Help.
            // 28 = Promo Thésards.
            // 6,24,25,26 = Mobilité.
            '6,28,24,25,26',
            PARAM_RAW
        ));

    $envatools->add('tool_enva', new admin_externalpage(
            'enva_manage_cohortcontent',
            get_string('managesurvey', 'tool_enva'),
            "$CFG->wwwroot/$CFG->admin/tool/enva/manage_survey.php",
            'tool/enva:managesurvey'
        )
    );

    $envatools->add('tool_enva', new admin_externalpage(
            'enva_manage_cohortsync',
            get_string('managecohortsync', 'tool_enva'),
            "$CFG->wwwroot/$CFG->admin/tool/enva/manage_cohort_sync.php",
            'tool/enva:managecohortsync'
        )
    );
    $envatools->add('tool_enva', new admin_externalpage(
            'enva_manage_groupsync',
            get_string('managegroupsync', 'tool_enva'),
            "$CFG->wwwroot/$CFG->admin/tool/enva/manage_group_sync.php",
            'tool/enva:managegroupsync'
        )
    );

    $ADMIN->add('accounts', $envatools);
}
