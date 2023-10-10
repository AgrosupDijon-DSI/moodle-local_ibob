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

namespace local_ibob\task;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib.php');
require_once(__DIR__ . '/../../../../lib/filelib.php');
/**
 * Plugin tasks.
 *
 * @package     local_ibob
 * @category    task
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_old_notifications extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scheduleddeletenotifications', 'local_ibob');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $ouseroldnotification = $DB->get_records('local_ibob_user_notification', ['notification_viewed'  => 1]);
        foreach ($ouseroldnotification as $ooldnotification) {
            $DB->delete_records(
                'local_ibob_notifications',
                [
                    'id' => $ooldnotification->notification_id,
                ]);
            $DB->delete_records(
                'local_ibob_user_notification',
                [
                    'id' => $ooldnotification->id,
                ]);
        }
    }
}
