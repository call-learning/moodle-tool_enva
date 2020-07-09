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

namespace tool_enva\output;
defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Rendering methods
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders enva tool menu
     *
     * @return string HTML
     */
    protected function render_enva_menus(enva_menus $menu) {
        $output = '';
        foreach ($menu->export_for_template($this) as $item) {
            $output .= $this->single_button($item->url->out(), $item->title);
        }
        return $this->box($output, 'menu');
    }

}
