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

namespace tool_enva\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use moodle_url;
use renderer_base;
use stdClass;
use templatable;


/**
 * Rendering methods
 */
class enva_menus implements renderable {
	/**
	 * Export the data.
	 *
	 * @param renderer_base $output
	 *
	 * @return stdClass
	 */
	public function export_for_template( renderer_base $output ) {
		global $CFG;
		$rooturl = "$CFG->wwwroot/$CFG->admin/tool/enva/index.php";
		return [
			(object) [
				'url' => new moodle_url($rooturl,array('action'=>'downloadcohortdata')),
				'title' => get_string('downloadcohortdata', 'tool_enva'),
			],
			(object) [
				'url' => new moodle_url($rooturl,array('action'=>'deletesurveyinfo')),
				'title' => get_string('deletesurveyinfo', 'tool_enva'),
			]
		];
	}
}
