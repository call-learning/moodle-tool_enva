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
 * This script allows to do activity backup.
 *
 * @package    tool_enva
 * * @copyright  2020 CALL Learning
 * * @author     Laurent David <laurent@call-learning.fr>
 * * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__ . '/../../../../config.php');
global $CFG;
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'cmid' => false,
    'destination' => '',
    'help' => false,
), array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['cmid']) {
    $help = <<<EOL
Perform backup of the given course module activity.

Options:
--cmid=INTEGER              Course module ID for backup (required).
--destination=STRING        Path where to store backup file. If not set the backup
                            will be stored within the course backup file area.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/backup_activity.php --cmid=123 --destination=/moodle/backup/\n
EOL;

    echo $help;
    die;
}

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

// Do we need to store backup somewhere else?
$dir = rtrim($options['destination'], '/');
if (!empty($dir)) {
    if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
        mtrace("Destination directory does not exists or not writable.");
        die;
    }
}
global $DB;
// Check that the course module exists.
$cm = $DB->get_record('course_modules', ['id' => $options['cmid']], '*', MUST_EXIST);

cli_heading('Performing activity backup...');
cli_writeln("Backing up activity with cmid {$cm->id}");
// Write the current time as the time the backup was started.
cli_writeln("Backup started at " . userdate(time()));



$bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);

// Set the default filename.
$format = $bc->get_format();
$type = $bc->get_type();
$id = $bc->get_id();
$users = $bc->get_plan()->get_setting('users')->get_value();
$anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
$filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
$bc->get_plan()->get_setting('filename')->set_value($filename);

// Execution.
$bc->finish_ui();
$bc->execute_plan();
$results = $bc->get_results();
$file = $results['backup_destination'];
$timenow = time();
// Do we need to store backup somewhere else?
if (!empty($dir)) {
    if ($file) {
        mtrace("Writing " . $dir.'/'.$filename);
        if ($file->copy_content_to($dir.'/'.$filename)) {
            $file->delete();
            mtrace("Backup completed.");
        } else {
            mtrace("Destination directory does not exist or is not writable. Leaving the backup in the course backup file area.");
        }
    }
} else {
    mtrace("Backup completed, the new file is listed in the backup area of the given activity");
}
$bc->destroy();
$timeend = time();
cli_writeln("Backup finished at " . userdate(time()));
cli_writeln("Total time taken: " . ($timeend - $timenow) . " seconds.");
exit(0);
