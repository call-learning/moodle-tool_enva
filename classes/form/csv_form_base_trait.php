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

namespace tool_enva\form;

use core_text;
use csv_import_reader;

/**
 * Trait csv_form_base_trait
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait csv_form_base_trait {

    /**
     * Setup basic definition
     *
     * @param string $syncfieldname
     */
    protected function setup_base_definition($syncfieldname) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $mform = $this->_form;
        $element = $mform->createElement('filepicker', $syncfieldname, get_string($syncfieldname . ':def', 'tool_enva'));
        $mform->addElement($element);
        $mform->addHelpButton($syncfieldname, $syncfieldname . ':def', 'tool_enva');
        $mform->addRule($syncfieldname, null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_enva'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_enva'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(false, get_string('import', 'tool_enva'));
    }
}
