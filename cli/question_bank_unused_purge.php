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

$usage = "Show a list of the question bank and stats through the right 4.x API.

Usage:
    # php question_bank_unused_purge.php

Options:
    -c --courseid=<courseid>    Course ID to delete question bank entries from.
    -t --categoryid=<categoryid> Category ID to list questions from.
    -o --olderthan=<timestamp>  Timestamp to filter questions older than this value (default: last 6 months).
    -a --allversions            Retrieve all versions of questions, not just the latest.
    -h --help                   Print this help.
    
";

list($options, $unrecognised) = cli_get_params([
    'courseid' => null,
    'categoryid' => null,
    'olderthan' => time() - YEARSECS / 2, // Default about last 6 month.
    'allversions' => false, // Retrieve all versions of questions, not just the latest.
    'help' => false,
], [
    'c' => 'courseid',
    't' => 'categoryid',
    'o' => 'olderthan',
    'a' => 'allversions',
    'h' => 'help',
]);
$courseid = $options['courseid'] ?? null;
$allversions = $options['allversions'] ?? false;

// Prepare the query to select IDs for deletion
if (!empty($courseid)) {
    $contextid = context_course::instance($courseid)->id;
    $questioncategories = \qbank_managecategories\helper::get_categories_for_contexts($contextid);
} else if (!empty($options['categoryid'])) {
    global $DB;
    $questioncategories = $DB->get_records('question_categories', ['id' => $options['categoryid']]);
} else {
    cli_error("No course ID or category ID provided.");
}

if (empty($questioncategories)) {
    cli_writeln("No question categories found for course ID $courseid.");
    exit(0);
}
$finder = question_bank::get_finder();
$notafter = $options['olderthan'] ?? (time() - (YEARSECS / 2)); // Default about last 6 months.
cli_writeln("Listing questions for course ID $courseid, older than " .
    date('d/m/Y H:i:s', $notafter) . ", ready for deletion.");

foreach ($questioncategories as $category) {
    if ($allversions) {
        $questionsid = \tool_enva\utils::get_questions_from_categories([$category->id]);
    } else {
        $questionsid = $finder->get_questions_from_categories([$category->id], "");
    }
    cli_writeln("Listing questions for category ID {$category->id} ({$category->name}) in course ID $courseid:" .
        count($questionsid) . " questions found.");
    foreach ($questionsid as $questionid) {
        ['question' => $question, 'usagecount' => $usagecount, 'status' => $status] =
            \tool_enva\utils::purge_question($questionid, $notafter);
        cli_writeln("Question ID: {$question->id}, Name: {$question->name}, Usage Count: $usagecount");
        if ($status == 'ok') {
            cli_writeln("Question ID: {$question->id} is not in use and older than " .
                date('d/m/Y H:i:s', $notafter) . ", ready for deletion.");
        } else {
            if ($status == 'toorecent') {
                $lastime = !empty($question->timemodified) ? date('d/m/Y H:i:s', $question->timemodified) : 'N/A';
                cli_writeln("Question ID: {$question->id} is not in use but too recent ($lastime), skipping deletion.");
            } else {
                cli_writeln("Question ID: {$question->id} is in use, skipping deletion ($status).");
            }

        }
    }
}
cli_writeln("Finished listing questions for course ID $courseid.");
