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
    'allversions' => null, // Retrieve all versions of questions, not just the latest.
    'categoryid' => null, // Category ID to list questions from.
    'help' => false,
], [
    'c' => 'courseid',
    'a' => 'allversions',
    't' => 'categoryid',
    'h' => 'help',
]);
if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}
$courseid = $options['courseid'] ?? null;
$categoryid = $options['categoryid'] ?? null;
$allversions = $options['allversions'] ?? false;
global $DB;
// Prepare the query to select IDs for deletion.
if (!empty($courseid)) {
    $contextid = context_course::instance($courseid)->id;
    $questioncategories = \qbank_managecategories\helper::get_categories_for_contexts("$contextid");
} else if (!empty($categoryid)) {
    global $DB;
    $questioncategories = $DB->get_records('question_categories', ['id' => $categoryid]);
} else {
    $questioncategories = $DB->get_records('question_categories');
}

if (empty($questioncategories)) {
    cli_writeln("No question categories found for course ID $courseid.");
    exit(0);
}
$finder = question_bank::get_finder();
$questioncount = 0;
$notreadycount = 0;
$allquesstionscount = 0;
cli_writeln("courseid,categoryid,categoryname,questioncount,allquestionsincategories");
foreach ($questioncategories as $category) {
    $qcparams = ['categoryid' => $category->id];
    if ($allversions) {
        $questionsid = \tool_enva\utils::get_questions_from_categories([$category->id], false);
    } else {
        $questionsid = $finder->get_questions_from_categories([$category->id], "");
    }
    $ccount = tool_enva\utils::count_questions_from_categories([$category->id], false); // This will count even
    // random questions (as they have question.parent != 0).
    $questioncontext = context::instance_by_id($category->contextid);
    $coursecontext = $questioncontext->get_course_context(false);
    $courseid = $coursecontext ? $coursecontext->instanceid : SITEID;
    cli_writeln("{$courseid},{$category->id},\"{$category->name}\",". count($questionsid) . ", $ccount");
    $questioncount += count($questionsid);
    $allquesstionscount += $ccount;
}
// We write the total line.
cli_writeln("1,0,Total,$questioncount,$allquesstionscount");
