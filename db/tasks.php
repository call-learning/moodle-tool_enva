<?php
/**
 * Tasks
 *
 * @package    tool_enva
 * @copyright  2019 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined( 'MOODLE_INTERNAL' ) || die();

$tasks = [
	[
		'classname' => 'tool_enva\task\empty_yearone_survey_data',
		'blocking'  => 0,
		'minute'    => '30',
		'hour'      => '23',
		'day'       => '*',
		'dayofweek' => '*',
		'month'     => '*'
	]
];