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
 * Manage cohort content
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_enva\csv;
defined('MOODLE_INTERNAL') || die();

/**
 * This file contains the abstract class to do csv import.
 * Based from lpimportcsv
 *
 * @package    tool_enva
 * @copyright  2020 CALL Learning
 * @author     Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_csv_importer {

    /** @var string $error The errors message from reading the xml */
    protected $error = '';

    /** @var int $importid CSV Import identifier */
    protected $importid;

    protected $importtype;

    /** @var array $header Array of headers (indexed by number so we can then find the column) */
    protected $headers;

    protected $currenttransaction = null;

    /**
     * Store an error message for display later and make sure the current importer is
     *
     * @param string $msg
     */
    public function fail($msg) {
        $this->error = $msg;
        return false;
    }

    /**
     * Get the CSV import id
     *
     * @return string The import id.
     */
    public function get_importid() {
        return $this->importid;
    }

    /**
     * Get the list of headers required for import.
     *
     * @return array The headers (lang strings)
     */
    public abstract function list_required_headers();

    /**
     * Get the a column from the imported data.
     *
     * @param array The imported raw row
     * @param $header The name of the column we want data from
     * @return string|null The column data .
     */
    protected function get_column_data($row, $header) {
        if ($this->headers) {
            if (($index = array_search($header, $this->headers) !== false)) {
                return isset($row[$index]) ? $row[$index] : '';
            }
        }
        return null;
    }

    /**
     * Constructor - parses the raw text for sanity.
     *
     * @param string $text The raw csv text.
     * @param string $encoding The encoding of the csv file.
     * @param string delimiter The specified delimiter for the file.
     * @param string importid The id of the csv import.
     * @param array mappingdata The mapping data from the import form.
     * @param bool $useprogressbar Whether progress bar should be displayed, to avoid html output on CLI.
     */
    public function __construct($text = null, $type = 'tool_enva_csv_import', $encoding = null, $delimiter = null, $importid = 0,
        $mappingdata = null,
        $useprogressbar = false) {

        global $CFG, $DB;

        // The format of our records is:
        // Parent ID number, ID number, Shortname, Description, Description format, Scale values, Scale configuration,
        // Rule type, Rule outcome, Rule config, Is framework, Taxonomy.

        // The idnumber is concatenated with the category names.
        require_once($CFG->libdir . '/csvlib.class.php');

        $this->importtype = $type;

        if (!$importid) {
            if ($text === null) {
                return;
            }
            $this->importid = \csv_import_reader::get_new_iid($type);

            $importer = new \csv_import_reader($this->importid, $this->importtype);

            if (!$importer->load_csv_content($text, $encoding, $delimiter)) {
                $this->fail(get_string('invalidimportfile', 'tool_enva'));
                $importer->cleanup();
                return;
            }

        } else {
            $this->importid = $importid;

            $importer = new \csv_import_reader($this->importid, $type);
        }

        if (!$importer->init()) {
            $this->fail(get_string('invalidimportfile', 'tool_enva'));
            $importer->cleanup();
            return;
        }

        $this->headers = $importer->get_columns();

        // Make sure that all required headers are present.
        foreach ($this->list_required_headers() as $requiredh) {
            if (!in_array($requiredh, $this->headers)) {
                $this->fail(get_string('headernotpresent', 'tool_enva', $requiredh));
                $importer->cleanup();
                return;
            }
        }

        $rowindex = 0;
        while ($row = $importer->next()) {
            if (!$this->validate_row($row, $rowindex)) {
                break;
            }
            $rowindex++;
        }
        $importer->close();
    }

    /**
     * Process import. Return false if import should be aborted due to error.
     *
     * @param int $rowindex
     * @param object $row
     * @return bool
     */
    public abstract function process_row($row, $rowindex);

    /**
     * Process import. Return false if import should be aborted due to error.
     *
     * @param int $rowindex
     * @param object $row
     * @return bool
     */
    public function validate_row($row, $rowindex) {
        return true;
    }

    /**
     * Cancel import process import.
     *
     * @param object $row
     * @return void
     * @throws \Throwable
     * @throws \coding_exception
     * @throws \dml_transaction_exception
     */
    public function cancel_import_process() {
        global $DB;
        $DB->rollback_delegated_transaction($this->currenttransaction, new importer_exception(
            $this->get_error()
        ));
    }

    /**
     * Process before doing import.
     *
     * @param $row
     * @return void
     */
    public function start_import_process() {
        global $DB;
        $this->currenttransaction = $DB->start_delegated_transaction();
    }

    /**
     * Finish import process import.
     *
     * @param object $row
     * @return void
     * @throws \dml_transaction_exception
     */
    public function end_import_process() {
        global $DB;
        $DB->commit_delegated_transaction($this->currenttransaction);
    }

    /**
     * Get parse errors.
     *
     * @return string error from parsing the xml.
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Process import
     *
     * @throws \Throwable
     * @throws \coding_exception
     * @throws \dml_transaction_exception
     * @throws importer_exception
     */
    public function process_import() {
        if ($this->error || !$this->importid) {
            throw new importer_exception($this->get_error());
        }
        $importer = new \csv_import_reader($this->importid, $this->importtype);
        $importer->init();
        if ($importer) {
            $this->start_import_process();

            $rowindex = 0;
            while ($row = $importer->next()) {
                if (!$this->process_row($row, $rowindex)) {
                    $this->cancel_import_process();
                    break;
                }
                $rowindex++;
            }
            $this->end_import_process();
            $importer->cleanup();
            $importer->close();
        } else {
            throw new importer_exception(get_string('cannotopenimporter', 'tool_enva'));
        }

    }
}
