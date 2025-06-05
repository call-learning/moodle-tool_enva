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

use core_question\local\bank\question_version_status;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

$usage = "Show a list of the question bank and stats through the right 4.x API.

Usage:
    # php question_bank_stats.php

Options:
    -c --courseid=<courseid>    Course ID to delete question bank entries from.
    -a --allversions            Retrieve all versions of questions, not just the latest.
    -h --help                   Print this help.
";

list($options, $unrecognised) = cli_get_params([
    'courseid' => null,
    'list' => true, // This option is not used in this script but can be used for future enhancements.
    'allversions' => null, // Retrieve all versions of questions, not just the latest.
    'categoryid' => null, // Category ID to list questions from.
    'help' => false,
], [
    'c' => 'courseid',
    'l' => 'list',
    'a' => 'allversions',
    't' => 'categoryid',
    'h' => 'help',
]);
$courseid = $options['courseid'] ?? null;
$categoryid = $options['categoryid'] ?? null;
$allversions = $options['allversions'] ?? false;
// Prepare the query to select IDs for deletion.
if (!empty($courseid)) {
    $contextid = context_course::instance($courseid)->id;
    $questioncategories = \qbank_managecategories\helper::get_categories_for_contexts("$contextid");
} else if (!empty($categoryid)) {
    global $DB;
    $questioncategories = $DB->get_records('question_categories', ['id' => $categoryid]);
} else {
    cli_error("No course ID or category ID provided.");
}

if (empty($questioncategories)) {
    cli_writeln("No question categories found for course ID $courseid.");
    exit(0);
}
$finder = question_bank::get_finder();
$questioncount = 0;
$notreadycount = 0;
$allquesstionscount = 0;
foreach ($questioncategories as $category) {
    $qcparams = ['categoryid' => $category->id];
    if ($allversions) {
        $questionsid = \tool_enva\utils::get_questions_from_categories([$category->id]);
    } else {
        $questionsid = $finder->get_questions_from_categories([$category->id], "");
    }
    $questions = array_map(function($id) {
        return question_bank::load_question_data($id);
    }, $questionsid);
    $notquestions = array_filter($questions, function($question) {
        return $question->status !== question_version_status::QUESTION_STATUS_READY;
    });
    $allquestions = count(tool_enva\utils::get_questions_from_categories([$category->id], false));
    cli_writeln("{$category->id} ({$category->name}),". count($questions) . ", " . count($notquestions). ", $allquestions");
    $questioncount += count($questions);
    $notreadycount += count($notquestions);
    $allquesstionscount += $allquestions;
}
cli_writeln("Total questions listed: $questioncount");
cli_writeln("Total no ready questions: $notreadycount");
cli_writeln("Total questions in all categories (root and non root): $allquesstionscount");
