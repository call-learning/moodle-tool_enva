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
    global $ADMIN;

    $envatools = new admin_category(
        'tool_enva',
        get_string('pluginname', 'tool_enva')
    );
    $envatools->add('tool_enva', new admin_externalpage(
            'enva_manage_cohortcontent',
            get_string('managecohortcontent', 'tool_enva'),
            "$CFG->wwwroot/$CFG->admin/tool/enva/manage_cohort_content.php",
            'tool/enva:managecohortcontent'
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

    $ADMIN->add('courses', $envatools);
}
