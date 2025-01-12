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

$usage = "Delete question bank that have random questions in it.

Usage:
    # php question_bank_purge.php

Options:
    -c --courseid=<courseid>    Course ID to delete question bank entries from.
    -h --help                   Print this help.
    
";

list($options, $unrecognised) = cli_get_params([
    'courseid' => null,
    'help' => false,
], [
    'c' => 'courseid',
    'h' => 'help',
]);
define('BATCH_SIZE', 100);
$courseid = $options['courseid'] ?? null;

if (empty($courseid)) {
    cli_error("Course ID is required.");
    exit(1);
}
// Prepare the query to select IDs for deletion
$sql = "SELECT DISTINCT qbe.id
        FROM {question_categories} qcat
        JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qcat.id
        JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
        JOIN {question} q ON q.id = qv.questionid
        LEFT JOIN {question_references} qr ON qbe.id = qr.questionbankentryid
        LEFT JOIN {context} c ON c.id = qcat.contextid AND c.contextlevel = 50
        WHERE q.qtype = :qtype AND qr.id IS NULL AND c.instanceid = :courseid LIMIT " . BATCH_SIZE;

$params = ['qtype' => 'random', 'courseid' => $courseid];

global $DB;
$totalDeleted = 0;

while (true) {
    // Fetch all IDs to delete.
    $ids = $DB->get_fieldset_sql($sql, $params);

    cli_writeln("Found " . count($ids) . " entries to delete.");

    if (empty($ids)) {
        cli_writeln("No entries to delete.");
        break;
    }
    // Process deletions in batches.

    list($inSql, $inParams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'id');
    $DB->delete_records_select('question_bank_entries', "id $inSql", $inParams);

    $totalDeleted += count($ids);
    cli_writeln("Deleted batch of " . count($ids) . " entries. Total deleted: $totalDeleted.");
}
cli_writeln("Deletion completed. Total entries deleted: $totalDeleted.");