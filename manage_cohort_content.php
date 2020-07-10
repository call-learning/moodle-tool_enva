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
 * Manage cohort content
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_enva\locallib\manage_cohort_content;
use tool_enva\output\enva_menus;

define('NO_OUTPUT_BUFFERING', true); // Progress bar is used here.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login(null, false);

$action = optional_param('action', '', PARAM_ALPHA);
$step = optional_param('step', "", PARAM_ALPHA);

admin_externalpage_setup('enva_manage_cohortcontent');
// Pre-output actions.
switch ($action) {
    case 'downloadcohortdata':
        require_sesskey();
        $csvexport = manage_cohort_content::export_cohorts_to_csv();
        $csvexport->download_file();
        exit;
        break;
    case 'downloademptysurvey':
        require_sesskey();
        $csvexport = manage_cohort_content::export_yearone_users_with_empty_data();
        $csvexport->download_file();
        exit;
}

$output = $PAGE->get_renderer('tool_enva');
// Output starts here.
echo $output->header();
echo $output->heading(get_string('managecohortcontent', 'tool_enva'));
if (strpos($action, 'delete') === 0) {
    require_sesskey();
    if (!$step) {
        echo $output->confirm(get_string($action . 'confirm', 'tool_enva'),
            new moodle_url($PAGE->url, array('action' => $action, 'step' => "delete")),
            new moodle_url($PAGE->url));
        echo $output->footer();
        exit;

    } else if ($step == "delete") {
        switch ($action) {
            case 'deleteusurveyinfo':
                manage_cohort_content::delete_user_yearly_surveyinfo();
                break;
            case 'deleteyearoneemptysurvey':
                manage_cohort_content::delete_user_surveyinfo_yearone_when_empty();
                break;
        }
        echo get_string('success');
    }
}

echo $output->render(new enva_menus());
echo $output->footer();
