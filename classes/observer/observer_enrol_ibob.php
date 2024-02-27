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
namespace local_ibob\observer;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/adminlib.php");

use local_ibob\task\adhoc_send_emails_notifications;

/**
 * Observer for ibob instance creation and ibob instance modification.
 *
 * Activate the adhok task to send email to selected users.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_enrol_ibob {

    /**
     * Observer for ibob instance creation and ibob instance modification.
     *
     * Activate the dashboard notification.
     *
     * @param \core\event\user_loggedin $event The user loggedin event.
     */
    public static function enrol_user_loggedin(\core\event\user_loggedin $event) {
        global $DB, $USER;
        if ($anotificationuser = $DB->get_records('local_ibob_user_notification',
            ['user_id'  => $USER->id, 'notification_viewed'  => 0])) {
            // Default : single notification.
            $hasmultiplenotifications = false;
            // Default : multiple notifications courses list.
            $courseslist = '';
            // Default : message.
            $message = '';
            foreach ($anotificationuser as $key => $onotificationusercleaner) {
                if (time() >= $onotificationusercleaner->timecreated + 2592000) {
                    // Notification is at least 1 month old, no display.
                    unset($anotificationuser[$key]);
                    $onotificationupdate = new \stdClass();
                    $onotificationupdate->id = $onotificationusercleaner->id;
                    $onotificationupdate->notification_viewed = 1;
                    $DB->update_record('local_ibob_user_notification', $onotificationupdate);
                }
            }
            $nbnotificationuser = count($anotificationuser);
            if ($nbnotificationuser > 0) {
                if ($nbnotificationuser > 1) {
                    // Multiple notifications.
                    $hasmultiplenotifications = true;
                }
                foreach ($anotificationuser as $onotificationusertemp) {
                    if ($notification = $DB->get_record('local_ibob_notifications',
                        ['id' => $onotificationusertemp->notification_id])) {
                        if ($hasmultiplenotifications) {
                            $courseslist .= $notification->course_link."<br>";
                            $DB->set_field('local_ibob_user_notification', 'notification_viewed', 1,
                                ['notification_id' => $onotificationusertemp->notification_id]);
                        } else {
                            $message = get_string('singlenotificationhtml', 'local_ibob', $notification->course_link);
                            $DB->set_field('local_ibob_user_notification', 'notification_viewed', 1,
                                ['notification_id' => $onotificationusertemp->notification_id]);
                            redirect(new \moodle_url('/my/'), $message, null, 'info');
                        }
                    }
                }
                if ($hasmultiplenotifications) {
                    $message = get_string('multiplenotificationhtml', 'local_ibob', $courseslist);
                    redirect(new \moodle_url('/my/'), $message, null, 'info');
                }
            }
        }
    }

    /**
     * Observer for ibob instance creation and ibob instance modification.
     *
     * Activate the adhok task to send email to selected users.
     *
     * @param \core\event\enrol_instance_created $event The enrolment instance created event.
     */
    public static function enrol_instance_created(\core\event\enrol_instance_created $event) {
        global $DB, $USER;
        $courseid = $event->courseid;
        $enrolid = $event->objectid;
        $contextid = $event->contextid;
        $action = $event->action;
        $other = $event->other;
        if (!empty($other)) {
            $enrol = $other['enrol'];
            if ($enrol === 'ibobenrol') {
                try {
                    $task = new adhoc_send_emails_notifications();
                    $task->set_custom_data([
                            'courseid' => $courseid,
                            'enrolid' => $enrolid,
                            'action' => $action,
                            'contextid' => $contextid,
                            'event' => $event, ]
                    );
                    \core\task\manager::queue_adhoc_task($task);
                } catch (\Exception $e) {
                    debugging("{$action} : course {$courseid} from method enrolment {$enrolid} problem " .
                        $e->getMessage(), DEBUG_NORMAL, $e->getTrace());
                }
            }
        }
    }

    /**
     * Observer for ibob instance creation and ibob instance modification.
     *
     * Activate the adhok task to send email to selected users.
     *
     * @param \core\event\enrol_instance_updated $event The enrolment instance updated event.
     */
    public static function enrol_instance_updated(\core\event\enrol_instance_updated $event) {
        $courseid = $event->courseid;
        $enrolid = $event->objectid;
        $contextid = $event->contextid;
        $action = $event->action;
        $other = $event->other;
        if (!empty($other)) {
            $enrol = $other['enrol'];
            if ($enrol === 'ibobenrol') {
                try {
                    $task = new adhoc_send_emails_notifications();
                    $task->set_custom_data([
                            'courseid' => $courseid,
                            'enrolid' => $enrolid,
                            'action' => $action,
                            'contextid' => $contextid,
                            'event' => $event, ]
                    );
                    \core\task\manager::queue_adhoc_task($task);
                } catch (\Exception $e) {
                    debugging("{$action} : course {$courseid} from method enrolment {$enrolid} problem " .
                        $e->getMessage(), DEBUG_NORMAL, $e->getTrace());
                }
            }
        }
    }
}
