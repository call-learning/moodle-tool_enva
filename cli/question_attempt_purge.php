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

$usage = "Delete question attempts for deleted users and question_attempt step data

Usage:
    # php question_attempt_purge.php --deletedusers
    # php question_attempt_purge.php [--help|-h]

Options:
    -h --help                   Print this help.
    --deletedusers              Delete question_attempt_steps for deleted users
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'deletedusers' => null,
], [
    'h' => 'help',
    'd' => 'deletedusers',
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

if (isset($options['deletedusers'])) {
    global $DB;
    $sql = "SELECT DISTINCT qas.id
             FROM {question_attempt_steps} qas
             LEFT JOIN {user} u ON u.id = qas.userid WHERE u.id IS NULL OR u.deleted = 1";
    $rs = $DB->get_recordset_sql($sql);
    cli_writeln('Deleting question_attempt_steps for deleted users');
    foreach ($rs as $record) {
        cli_write('.');
        $DB->delete_records('question_attempt_steps', ['id' => $record->id]);
        $DB->delete_records('question_attempt_step_data', ['attemptstepid' => $record->id]);
    }
    $rs->close();
}
