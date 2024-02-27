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
 * Plugin local_ibob.
 *
 * @package    local_ibob
 * @category    task
 * @copyright  2023 Frédéric Grebot <frederic.grebot@agrosupdijon.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ibob\task;

use coding_exception;
use export_service;

/**
 * Plugin local_ibob.
 *
 * @package    local_ibob
 * @category    task
 * @copyright  2023 Frédéric Grebot <frederic.grebot@agrosupdijon.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class force_processing_task extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('forceprocessing', 'local_ibob');
    }

    /**
     * Execute the task.
     */
    public function execute() {

        global $CFG, $DB;

        require_once("{$CFG->libdir}/clilib.php");

        $records = $DB->get_records('task_adhoc');

        // Get the cron lock factory.
        $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
        $cronlock = null;

        foreach ($records as $record) {

            // Grab the task.
            $task = \core\task\manager::adhoc_task_from_record($record);
            if (!$task) {
                cli_problem("Task '{$record->id}' could not be loaded.");
                continue;
            }

            if ($task->is_blocking()) {
                continue;
            }

            // Set pre-task performance vars.
            $predbqueries = $DB->perf_get_queries();
            $pretime = microtime(true);

            try {
                // Grab a task lock.
                if (!$lock = $cronlockfactory->get_lock('adhoc_' . $record->id, 10)) {
                    cli_problem('Cannot obtain task lock');
                    continue;
                }

                // Set lock info on task.
                $task->set_lock($lock);
                if ($task->is_blocking()) {
                    if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
                        cli_problem('Cannot obtain cron lock');
                        continue;
                    }
                    $task->set_cron_lock($cronlock);
                }

                // Run the task.
                $task->execute();

                // Echo out performance info.
                if (isset($predbqueries)) {
                    cli_writeln("... used " . ($DB->perf_get_queries() - $predbqueries) . " dbqueries");
                    cli_writeln("... used " . (microtime(1) - $pretime) . " seconds");
                }
                cli_writeln("Task {$record->id} completed.");

                // Set the task as complete.
                \core\task\manager::adhoc_task_complete($task);
            } catch (\Exception $e) {
                if ($DB->is_transaction_started()) {
                    $DB->force_transaction_rollback();
                }

                cli_writeln("... used " . ($DB->perf_get_queries() - $predbqueries) . " dbqueries");
                cli_writeln("... used " . (microtime(true) - $pretime) . " seconds");
                cli_problem("Task failed: " . $e->getMessage());

                // We failed.
                \core\task\manager::adhoc_task_failed($task);

                // Release the global lock before we throw errors.
                if ($cronlock) {
                    $cronlock->release();
                    unset($cronlock);
                }

                throw $e;
            }
        }

        // Release the global lock.
        if ($cronlock) {
            $cronlock->release();
            unset($cronlock);
        }
    }


}
