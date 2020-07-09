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

use tool_enva\output\enva_menus;

define('NO_OUTPUT_BUFFERING', true); // Progress bar is used here.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login(null, false);

$action = optional_param('action', '', PARAM_ALPHA);
$step = optional_param('step', "", PARAM_ALPHA);

admin_externalpage_setup('enva_manage_cohortsync');

$output = $PAGE->get_renderer('tool_enva');
$form = new \tool_enva\forms\cohort_sync_form();

// Output starts here.
echo $output->header();
echo $output->heading(get_string('managecohortsync', 'tool_enva'));
echo $form->render();
echo $output->footer();
