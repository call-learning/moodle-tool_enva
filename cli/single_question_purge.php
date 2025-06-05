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
require_once($CFG->dirroot . '/question/engine/bank.php');

$usage = "Purge a question if unused.

Usage:
    # php single_question_purge.php

Options:
    -q --questionid=<courseid>    Course ID to delete question bank entries from.
    -h --help                   Print this help.
    
";

list($options, $unrecognised) = cli_get_params([
    'questionid' => null,
    'help' => false,
], [
    'q' => 'questionid',
    'o' => 'olderthan',
    'h' => 'help',
]);
$courseid = $options['courseid'] ?? null;

// Prepare the query to select IDs for deletion
if (!empty($options['questionid'])) {
    $questionid = $options['questionid'];
    $notafter = $options['olderthan'] ?? (time() - (YEARSECS / 2)); // Default about last 6 months.
    ['question' => $question, 'usagecount' => $usageCount, 'status' => $status] =
        \tool_enva\utils::purge_question($questionid, $notafter);
    cli_writeln("Question ID: {$question->id}, Name: {$question->name}, Usage Count: $usageCount, Status: $question->status, " .
        "Last Modified: " . date('d/m/Y H:i:s', $question->timemodified ?? 0));
    if ($status == 'ok') {
        cli_writeln("Question ID: {$question->id} is not in use and older than " .
            date('d/m/Y H:i:s', $notafter) . ", ready for deletion.");
    } else {
        $lastime = !empty($question->timemodified) ? date('d/m/Y H:i:s', $question->timemodified) : 'N/A';
        cli_writeln("Question ID: {$question->id} is not in use but too recent ($lastime), skipping deletion.");
    }
} else {
    cli_error("You must specify either a course ID or a category ID to list questions from.");
}
