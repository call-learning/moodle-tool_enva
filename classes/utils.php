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

namespace tool_enva;

use question_bank;

/**
 * Tools for ENVA - Sync all cohorts
 *
 * @package    tool_enva
 * @copyright  2022 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Name of the cohort to reset.
     */
    const DEFAULT_COHORTS_TO_RESET_NAMES
        = 'A2,A3,A4,A5,A6-Autres,A6-AP,A6-AC,A6-EQ,Internes-AP,Internes-EQ,Internes-AC,A6-AP-EQ,A6-AC-EQ';

    /**
     * A version of question_finder::get_questions_from_categories that also return hidden questions and not necessarily the last.
     *
     * @param array $categories
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_questions_from_categories(array $categories, bool $rootquestionly = true): array {
        global $DB;

        list($qcsql, $qcparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'qc');
        $sql = "SELECT q.id, q.id AS id2
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid {$qcsql}";

        if ($rootquestionly) {
            $sql .= " AND q.parent = 0";
        }
        return $DB->get_records_sql_menu($sql, $qcparams);
    }

    /**
     * Purge a question if it is not used in any question bank entry.
     *
     * @param int $questionid The ID of the question to purge.
     * @param int|null $olderthan Timestamp to check if the question was modified before this time.
     * @return array An array containing the status, question data, and usage count.
     */
    public static function purge_question(int $questionid, ?int $olderthan = null): array {
        $question = question_bank::load_question_data($questionid);
        $olderthan = $olderthan ?? (time() - YEARSECS / 2); // Default to about last 6 months.
        $usagecount = \qbank_usage\helper::get_question_entry_usage_count($question, true);
        $returnval = [
            'status' => 'notdeleted',
            'question' => $question,
            'usagecount' => $usagecount,
        ];
        if ($usagecount == 0) {
            $lastime = !empty($question->timemodified) ? $question->timemodified : $question->timecreated;
            if ($lastime < $olderthan) {
                question_delete_question($question->id);
                $returnval['status'] = 'ok';
            } else {
                $returnval['status'] = 'toorecent';
            }
        }
        return $returnval;
    }


}


