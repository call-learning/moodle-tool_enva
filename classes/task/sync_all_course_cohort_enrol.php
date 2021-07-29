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
 * Tools for ENVA - Sync all cohorts
 *
 * @package    tool_enva
 * @copyright  2021 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_enva\task;
defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use moodle_exception;
use null_progress_trace;

/**
 * Class sync_all_course_cohort_enrol
 *
 * @package    tool_enva
 * @copyright  2021 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_all_course_cohort_enrol extends adhoc_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncallcohortcourses', 'tool_enva');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;
        $data = $this->get_custom_data();
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");

        if ($data && !empty($data->courses)) {
            $transaction = $DB->start_delegated_transaction();
            try {
                foreach ($data->courses as $courseid) {
                    $trace = new null_progress_trace();
                    enrol_cohort_sync($trace, $courseid);

                    $trace->finished();
                }
                $transaction->allow_commit();
            } catch (moodle_exception $e) {
                $transaction->rollback($e);
            }
            $transaction->dispose();
        }
    }
}
