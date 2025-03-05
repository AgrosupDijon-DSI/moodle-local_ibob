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

use core\task\adhoc_task;

/**
 * Plugin local_ibob.
 *
 * @package     local_ibob
 * @category    task
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_send_emails_notifications  extends \core\task\adhoc_task {

    /**
     * Execute the task.
     *
     * Send email to selected users if conditions are met.
     *
     * Here, select users who own at least one the open badges defined by the course creator.
     *
     */
    public function execute() {
        global $CFG, $DB;

        $datastring = $this->get_custom_data_as_string();
        $data = json_decode($datastring);

        // Get the course information.
        $course = \get_course($data->courseid);

        // New message.
        $message = new \core\message\message();

        $message->component = 'local_ibob';
        $message->name = 'enrolcreatedupdated';
        $message->userfrom = \core_user::get_noreply_user();
        $message->contexturl = $CFG->wwwroot.'/my';
        $message->courseid = $data->courseid;
        $message->fullmessageformat = FORMAT_PLAIN;

        // Get the badge id from the enrolment method of the course created.
        $obadgesid = $this->get_badges_from_course($data->courseid);
        $ssearchbadges = str_replace('#', ',', $obadgesid->customtext1);
        $abadgesid = explode(",", $ssearchbadges);

        if ($data->action == 'updated') {
            // If update, launch verification to unenroll users without the new badges.
            // Get the enrolment id linked to our course for the filter.
            $oenrol = $this->get_enrolment_from_course($course->id);
            // Get the list of the users already enrolled.
            $sql = "SELECT u.id
                      FROM {user_enrolments} ue
                      JOIN {user} u
                        ON ue.userid = u.id
                     WHERE ue.enrolid = ".$oenrol->id;
            foreach ($DB->get_records_sql($sql) as $userid => $ousertemp) {
                $abadgesuser = $this->get_badges_from_user($userid);
                $hasbadge = false;
                foreach ($abadgesuser as $obadge) {
                    if (in_array($obadge->id, $abadgesid)) {
                        $hasbadge = true;
                    }
                }
                if (!$hasbadge) {
                    // Disenrol the user.
                    $instances = $DB->get_records('enrol', ['courseid' => $data->courseid]);
                    foreach ($instances as $instance) {
                        $plugin = enrol_get_plugin($instance->enrol);
                        $plugin->unenrol_user($instance, $userid);
                    }
                    // Message for a course disenrollment.
                    $message->subject .= get_string('emails_notifications:subject', 'local_ibob', $course->fullname);
                    $message->fullmessage .= get_string('emails_notifications:fullmess1', 'local_ibob');
                    $message->fullmessage .= get_string('emails_notifications:fullmess2', 'local_ibob', $course->fullname);
                    $message->fullmessage .= get_string('emails_notifications:fullmess3', 'local_ibob');
                    $message->fullmessage .= get_string('emails_notifications:fullmess4', 'local_ibob', $CFG->wwwroot);
                    $message->fullmessagehtml .= get_string('emails_notifications:fullmesshtml1', 'local_ibob');
                    $message->fullmessagehtml .= get_string('emails_notifications:fullmesshtml2', 'local_ibob', $course->fullname);
                    $message->fullmessagehtml .= get_string('emails_notifications:fullmesshtml3', 'local_ibob');
                    $message->fullmessagehtml .= get_string('emails_notifications:fullmesshtml4', 'local_ibob', $CFG->wwwroot);
                    $message->userto = \core_user::get_user($userid);
                    message_send($message);
                }
            }
        }
        // Default action : create.
        // Select new users who have at least one of the open badges defined in the enrolment method.
        $auserslisttoenrol = $this->get_users_from_badges($ssearchbadges);
        $context = \context_course::instance($data->courseid);
        // Message for a course creation.
        $message->subject = get_string('notifopmailsubject', 'local_ibob');
        $courselink = \html_writer::link(
            new \moodle_url($CFG->wwwroot.'/course/view.php?id='.$course->id),
                get_string('emails_notifications:click', 'local_ibob')
        );
        $courselinknotification = \html_writer::link(
            new \moodle_url($CFG->wwwroot.'/course/view.php?id='.$course->id),
            $course->fullname
        );
        $message->fullmessage .= get_string('notifopmailfullmessage', 'local_ibob');
        $message->fullmessagehtml .= get_string('notifopmailfullmessagehtml', 'local_ibob', $courselink);
        foreach ($auserslisttoenrol as $ouser) {
            // Send the message if the user is not already enrolled.
            if (!is_enrolled($context, $ouser->id, '', true)) {
                // Get the enrolmenet instance.
                $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'ibobenrol']);
                $enrolplugin = enrol_get_plugin($instance->enrol);
                if ($instance->customint1 == 1) {
                    $enrolplugin->enrol_user($instance, $ouser->id, $instance->roleid, time(), 0);
                }
                $message->userto = \core_user::get_user($ouser->id);
                // Message send only if the selected user has not yet received it.
                $idnotificationuser = false;
                $emailsend = false;

                $sql = "SELECT id
                          FROM {local_ibob_user_notification}
                         WHERE user_id=:userid and course_id=:courseid";
                $oidnotificationuser = $DB->get_record_sql($sql, ["courseid" => $course->id, "userid" => $ouser->id]);

                if ($oidnotificationuser->id) {
                    $sql = "SELECT id
                              FROM {local_ibob_user_notification}
                             WHERE email_send=1 and id=:idnotificationuser";
                    $oemailsend = $DB->get_record_sql($sql, ["idnotificationuser" => $oidnotificationuser->id]);
                }

                if (!$oemailsend->id) {
                    if (isset($oidnotificationuser)) {
                        if (!$oidnotificationuser->id) {
                            $idnotification = $this->add_notification_next_login ($ouser->id, $courselinknotification,
                                $course->id);
                        } else {
                            $idnotification = $oidnotificationuser->id;
                        }
                    } else {
                            $idnotification = $this->add_notification_next_login ($ouser->id, $courselinknotification,
                                $course->id);
                    }
                    message_send($message);

                    $onotifcationuser = new \stdClass();
                    $onotifcationuser->id = $idnotification;
                    $onotifcationuser->timemodified = time();
                    $onotifcationuser->email_send = 1;
                    $DB->update_record('local_ibob_user_notification', $onotifcationuser);
                }
            }
        }
    }

    /**
     * Notify user if exists.
     *
     * @param int $userid
     * @param int $courseid
     * @return void
     */
    protected function notification_user_exist(int $userid, int $courseid) {
        global $DB;

        $sql = "SELECT id
                  FROM {local_ibob_user_notification}
                 WHERE user_id=:userid and course_id=:courseid";
        return $DB->get_record_sql($sql, ["courseid" => $courseid, "userid" => $userid]);
    }

    /**
     * Add a notification for the next login.
     *
     * @param int $userid
     * @param string $courselinknotification
     * @param int $courseid
     * @return void
     */
    protected function add_notification_next_login(int $userid, string $courselinknotification, int $courseid) {
        global $DB;

        $onotifcation = new \stdClass();
        $onotifcation->timecreated = time();
        $onotifcation->timemodified = time();
        $onotifcation->course_link = $courselinknotification;
        $notifcationid = $DB->insert_record('local_ibob_notifications', $onotifcation);

        $onotifcationuser = new \stdClass();
        $onotifcationuser->timecreated = time();
        $onotifcationuser->timemodified = time();
        $onotifcationuser->user_id = $userid;
        $onotifcationuser->notification_id = $notifcationid;
        $onotifcationuser->notification_viewed = 0;
        $onotifcationuser->email_send = 0;
        $onotifcationuser->course_id = $courseid;
        return $DB->insert_record('local_ibob_user_notification', $onotifcationuser, true);
    }

    /**
     * Get the user badges from user id.
     *
     * @param int $userid
     * @return mixed
     */
    protected function get_badges_from_user(int $userid) {
        global $DB;
        $sql = "SELECT distinct(badgeid) as id
                  FROM {local_ibob_badge_issued}
                 WHERE userid = :userid";
        return $DB->get_records_sql($sql, ["userid" => $userid]);
    }

    /**
     * Get the users from the badge id.
     *
     * @param string $ssearchbadges
     * @param int $alreadyenrolled
     * @return mixed
     */
    protected function get_users_from_badges(string $ssearchbadges, ?int $alreadyenrolled = null) {
        global $DB;
        $sql = "SELECT userid as id
                  FROM {local_ibob_badge_issued}
                 WHERE badgeid in (:badgelist)
              GROUP BY id";
        mtrace('requete get_users_from_badges = '.$sql);
        return $DB->get_records_sql($sql, ["badgelist" => $ssearchbadges]);
    }

    /**
     * Get the badges associated to a course.
     *
     * @param int $courseid
     * @return mixed
     */
    protected function get_badges_from_course(int $courseid) {
        global $DB;
        $sql = "SELECT customtext1
                  FROM {enrol}
                 WHERE enrol='ibobenrol' and courseid=:courseid";
        return $DB->get_record_sql($sql, ["courseid" => $courseid]);
    }

    /**
     * Get enrolments from course.
     *
     * @param int $courseid
     * @return mixed
     */
    protected function get_enrolment_from_course(int $courseid) {
        global $DB;
        $sql = "SELECT id
                  FROM {enrol}
                 WHERE enrol='ibobenrol' and courseid=:courseid";
        return $DB->get_record_sql($sql, ["courseid" => $courseid]);
    }
}
