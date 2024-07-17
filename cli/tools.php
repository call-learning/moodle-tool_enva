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
 * CLI script allowing to run internal/ setup functions multiple times
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

$usage = "Run different setup script for testing purpose

Usage:
    # php tool.php --name=<functionname>
    # php tool.php [--help|-h]

Options:
    -h --help                   Print this help.
    --name=<frankenstyle>       Name of the function to test/run
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'name' => null,
], [
    'h' => 'help',
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}
$possiblefunctions = ['print_export_cohorts', 'print_yearone_users_with_empty_data'];

if ($options['name'] === null) {
    $options['name'] = $possiblefunctions[0];
}

if (in_array($options['name'], $possiblefunctions)) {
    call_user_func($options['name']);
} else {
    print ('Called function not in the list (' . implode(',', $possiblefunctions) . ')');
}
