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
 * @copyright  2019 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define( 'NO_OUTPUT_BUFFERING', true ); // progress bar is used here

require( __DIR__ . '/../../../config.php' );
require_once( $CFG->dirroot . '/' . $CFG->admin . '/tool/enva/locallib.php' );
require_once( $CFG->libdir . '/adminlib.php' );

require_login( null, false );

$action = optional_param( 'action', '', PARAM_ALPHA );
$step   = optional_param( 'step', "", PARAM_ALPHA );

admin_externalpage_setup( 'tool_enva' );
// pre-output actions
switch ( $action ) {
	case 'downloadcohortdata':
		require_sesskey();
		$csvexport = export_cohorts_to_csv();
		$csvexport->download_file();
		exit;
		break;
	case 'downloademptysurvey':
		require_sesskey();
		$csvexport = export_yearone_users_with_empty_data();
		$csvexport->download_file();
		exit;
}

$output = $PAGE->get_renderer( 'tool_enva' );
// output starts here
echo $output->header();
echo $output->heading( get_string( 'pluginname', 'tool_enva' ) );
if ( strpos( $action, 'delete' ) === 0 ) {
	require_sesskey();
	if ( ! $step ) {
		echo $output->confirm( get_string( $action.'confirm', 'tool_enva' ),
			new moodle_url( $PAGE->url, array( 'action' => $action, 'step' => "delete" ) ),
			new moodle_url( $PAGE->url ) );
		echo $output->footer();
		exit;
		
	} else if ( $step == "delete" ) {
		switch ( $action ) {
			case 'deleteusurveyinfo':
				delete_user_yearly_surveyinfo();
				break;
			case 'deleteyearoneemptysurvey':
				delete_user_surveyinfo_yearone_when_empty();
				break;
		}
		echo get_string('success');
	}
	
}


echo $output->render( new \tool_enva\output\enva_menus() );
echo $output->footer();
