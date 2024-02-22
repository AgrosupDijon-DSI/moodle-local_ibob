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
 * Plugin local_ibob.
 *
 * @package     local_ibob
 * @category    task
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_users_badges extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scheduledupdateusersbadgesname', 'local_ibob');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        // List of all users whith a provider and a key.
        $alistuserwithissuedbadgeslocal = $this->get_users_obp();
        foreach ($alistuserwithissuedbadgeslocal as $ouser) {
            // List of local open badges.
            $alistlocaluserwithissuedbadges = $this->get_local_badges_from_user($ouser->user_id);

            // Get provider info.
            $provider = $this->get_providers_info(1);

            // List of online badges.
            $ousername = json_decode($ouser->key_field);
            $url = $provider[1]->apiurl;
            $acurl = get_user_provider_json($url, $ousername->email);

            if (is_null($acurl['json']) && $acurl['code'] != 200) {
                throw new \Exception(get_string('testbackpackapiurlexception', 'local_ibob',
                        (object)['url' => $acurl['fullurl'], 'errorcode' => $acurl['code']])
                    , $acurl['code']);
            } else {
                if ($acurl['code'] === 200) {
                    $auseronlinebadgesfinal = [];
                    if (!is_array($acurl['json']->userId)) {
                        $ajson = [$acurl['json']->userId];
                    } else {
                        $ajson = $acurl['json']->userId;
                    }
                    // Global Public Open Badges List (public group : 0, social group : 1 ).
                    $agroupid = [0];
                    $aglobalonlinebadgesfinal = [];
                    foreach ($ajson as $backpackitem) {
                        foreach ($agroupid as $groupid) {
                            $fullurl = $url . $backpackitem . '/group/' . $groupid . '.json';
                            $output = $acurl['curl']->get($fullurl);
                            $alistbadgesuser = json_decode($output, true);
                            // Fomating results for further comparaison.
                            if (count($alistbadgesuser) > 2) {
                                foreach ($alistbadgesuser['badges'] as $abadgetemp) {
                                    if (!isset($abadgetemp['assertion']['expires'])) {
                                        $abadgetemp['assertion']['expires'] = '';
                                    }
                                    $auseronlinebadgesfinal[] = ['name' => $abadgetemp['assertion']['badge']['name'],
                                        'description' => $abadgetemp['assertion']['badge']['description'],
                                        'issuername' => $abadgetemp['assertion']['badge']['issuer']['name'],
                                        'issuerurl' => $abadgetemp['assertion']['badge']['issuer']['url'],
                                        'issuercontact' => $abadgetemp['assertion']['badge']['issuer']['email'],
                                        'expiredate' => $abadgetemp['assertion']['expires'],
                                        'image' => $abadgetemp['imageUrl'], ];
                                    $aglobalonlinebadgesfinal[] = $auseronlinebadgesfinal;
                                }
                            }
                        }
                    }
                }
                $auseronlinebadgespurged = $auseronlinebadgesfinal;

                foreach ($auseronlinebadgespurged as $key => $abadge) {
                    // Verification : is the online badge already stored locally ?
                    $isstoredinlocal = $this->is_stored_in_local($abadge['name'], $abadge['issuername']);

                    if ( !is_object($isstoredinlocal) || count(get_object_vars($isstoredinlocal)) == 0 ) {
                        // Insert into local_ibob_badges.
                            $obadgeinsert = new \stdClass();
                            $obadgeinsert->name = $abadge['name'];
                            $obadgeinsert->description = substr($abadge['description'], 0, 1000);
                            $obadgeinsert->issuerurl = $abadge['issuerurl'];
                            $obadgeinsert->issuername = $abadge['issuername'];
                            $obadgeinsert->issuercontact = $abadge['issuercontact'];
                            $obadgeinsert->image = $abadge['image'];
                            $obadgeinsert->usermodified = 1;
                            $obadgeinsert->timecreated = time();
                            $badgeidinserted = $DB->insert_record('local_ibob_badges', $obadgeinsert);

                        // Insert into local_ibob_badge_issued.
                        if (!$DB->get_record_select(
                            'local_ibob_badge_issued',
                            'userid=:userid and badgeid=:badgeid',
                            ['userid' => $ouser->user_id, 'badgeid' => $badgeidinserted],
                            $fields = 'id',
                            $strictness = IGNORE_MISSING)) {
                            $obadgeissued = new \stdClass();
                            $obadgeissued->userid = $ouser->user_id;
                            $obadgeissued->badgeid = $badgeidinserted;
                            $obadgeissued->expirationdate = $abadge['expiredate'];
                            $DB->insert_record(
                                'local_ibob_badge_issued',
                                $obadgeissued);
                        }
                    } else {
                        // Badge stored in local.
                        // Expiration check.
                        if ($abadge['expiredate'] != '') {
                            if ($abadge['expiredate'] < time()) { // Badge expired online.
                                // Unsubscribe the user if necessary.
                                // Courses list with the Ibob enrolment method and the selected badge.
                                $alistbadges = $this->get_courses_from_enrolment_badge($isstoredinlocal->id, $ouser->user_id);
                                if (!empty($alistbadges)) {
                                    foreach ($alistbadges as $olistbadges) {
                                        // Disenrol the user.
                                        $instances = $DB->get_records('enrol', ['courseid' => $olistbadges->courseid]);
                                        foreach ($instances as $instance) {
                                            $plugin = \enrol_get_plugin($instance->enrol);
                                            $plugin->unenrol_user($instance, $ouser->user_id);
                                        }
                                    }
                                }
                                // Delete the issued badge from database for the user.
                                if (!$this->delete_expired_badge_issued($isstoredinlocal->id, $ouser->user_id)) {
                                    echo "badge issued ".$isstoredinlocal->id." du user ".$ouser->user_id.
                                        " : erreur dans la suppression !\n";
                                }
                                // Delete the badge from $auseronlinebadgespurged.
                                unset($auseronlinebadgespurged[$key]);
                            }
                        }

                        if ($abadge['expiredate'] == '' || $abadge['expiredate'] >= time()) {
                            // Issued chek.
                            $isissuedforuser = $this->is_badge_issued($ouser->user_id, $isstoredinlocal->id);
                            if ( !is_object($isissuedforuser) || count(get_object_vars($isissuedforuser)) == 0 ) {
                                $obadgeissued = new \stdClass();
                                $obadgeissued->userid = $ouser->user_id;
                                $obadgeissued->badgeid = $isstoredinlocal->id;
                                $obadgeissued->expirationdate = $abadge['expiredate'];
                                $DB->insert_record(
                                    'local_ibob_badge_issued',
                                    $obadgeissued);
                            }
                        }
                    }

                    $isstoredinlocal = $this->is_stored_in_local($abadge['name'], $abadge['issuername']);

                    // Check if the badge opens to new inscriptions.
                    $acourseenrolment = $this->get_all_courses_from_enrolment_badge($isstoredinlocal->id);

                    if ($acourseenrolment) {
                        foreach ($acourseenrolment as $ocourseid) {
                            $oenrolid = $this->get_enrolid_by_course($ocourseid->courseid);
                            if (!$this->is_user_enrolled($ouser->user_id, $oenrolid->id)) {
                                // New message.
                                $message = new \core\message\message();
                                $message->component = 'local_ibob';
                                $message->name = 'enrolcreatedupdated';
                                $message->userfrom = \core_user::get_noreply_user();
                                $message->contexturl = $CFG->wwwroot.'/my';
                                $message->courseid = $ocourseid->courseid;
                                $message->fullmessageformat = FORMAT_PLAIN;

                                $message->subject = get_string('notifopmailsubject', 'local_ibob');
                                $courselink = \html_writer::link(
                                    new \moodle_url($CFG->wwwroot.'/course/view.php?id='.$ocourseid->courseid),
                                    "Cliquer ici"
                                );
                                $courselinknotification = \html_writer::link(
                                    new \moodle_url($CFG->wwwroot.'/course/view.php?id='.$ocourseid->courseid),
                                    $ocourseid->fullname
                                );
                                $message->fullmessage .= get_string('notifopmailfullmessage', 'local_ibob');
                                $message->fullmessagehtml .= get_string('notifopmailfullmessagehtml', 'local_ibob', $courselink);

                                $context = \context_course::instance($ocourseid->courseid);
                                // Send the message if the user is not already enrolled.
                                if (!is_enrolled($context, $ouser->user_id, '', true)) {

                                    // Message send only if the selected user has not yet received it.
                                    $idnotificationuser = false;
                                    $emailsend = false;

                                    $sql = "SELECT id
                                              FROM {local_ibob_user_notification}
                                             WHERE user_id=:userid and course_id=:courseid";
                                    $oidnotificationuser = $DB->get_record_sql($sql, ["courseid" => $ocourseid->courseid,
                                        "userid" => $ouser->user_id, ]);
                                    if ($oidnotificationuser->id) {
                                        $sql = "SELECT id
                                                  FROM {local_ibob_user_notification}
                                                 WHERE email_send=1 and id=:idnotificationuser";
                                        $oemailsend = $DB->get_record_sql($sql,
                                            ["idnotificationuser" => $oidnotificationuser->id]);
                                    }
                                    if (!$oemailsend->id) {
                                        // Get the enrolment instance.
                                        $instance = $DB->get_record('enrol', ['courseid' => $ocourseid->courseid,
                                            'enrol' => 'ibobenrol', ]);
                                        $enrolplugin = enrol_get_plugin($instance->enrol);
                                        if ($instance->customint1 == 1) {
                                            $enrolplugin->enrol_user($instance, $ouser->user_id, $instance->roleid, time(), 0);
                                        }
                                        $message->userto = \core_user::get_user($ouser->user_id);

                                        if (isset($oidnotificationuser)) {
                                            if (!$oidnotificationuser->id) {
                                                $idnotification = $this->add_notification_next_login($ouser->user_id,
                                                    $courselinknotification, $ocourseid->courseid);
                                            } else {
                                                $idnotification = $oidnotificationuser->id;
                                            }
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
                    }
                }
            }

            $anameofonlinebadges = array_column($auseronlinebadgespurged, 'name');
            // Check if the local badge is still online (any change of group, or suppression).
            foreach ($alistlocaluserwithissuedbadges as $obadgelocalfromuser) {
                reset($anameofonlinebadges);
                $keyinarray = array_search($obadgelocalfromuser->name, $anameofonlinebadges);
                $isinarray = true;
                if ($keyinarray == false || $keyinarray == 0 || !$keyinarray) {
                    $isinarray = false;
                }
                if (!$isinarray) {
                    // Local badge is no longer present online.
                    // Unsubscribe the user if necessary.
                    // Courses list with the Ibob enrolment method and the selected badge.
                    $alistbadges = $this->get_courses_from_enrolment_badge($obadgelocalfromuser->badgeid, $ouser->user_id);
                    if (!empty($alistbadges)) {
                        foreach ($alistbadges as $olistbadges) {
                            // Disenrol the user.
                            $instances = $DB->get_records('enrol', ['courseid' => $olistbadges->courseid]);
                            foreach ($instances as $instance) {
                                $plugin = \enrol_get_plugin($instance->enrol);
                                $plugin->unenrol_user($instance, $ouser->user_id);
                            }
                        }
                    }
                    // Delete the issued badge from database for the user.
                    if ($this->delete_expired_badge_issued($obadgelocalfromuser->badgeid, $ouser->user_id)) {
                        echo "badge issued ".$obadgelocalfromuser->badgeid." du user ".$ouser->user_id." supprimé !\n";
                    } else {
                        echo "badge issued ".$obadgelocalfromuser->badgeid." du user ".$ouser->user_id.
                            " : erreur dans la suppression !\n";
                    }
                }
            }
        }
    }

    /**
     * Add a notification for the next login.
     *
     * @param int $userid
     * @param string $courselinknotification
     * @param int $courseid
     * @return mixed
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
        return $DB->insert_record('local_ibob_user_notification', $onotifcationuser);
    }

    /**
     * Get the enroll id from course id.
     *
     * @param int $couseid
     * @return object
     */
    protected function get_enrolid_by_course (int $couseid) {
        global $DB;
        $sql = "SELECT e.id
                  FROM {enrol} e
                 WHERE e.enrol = 'ibobenrol'
                   AND e.courseid = :couseid";
        return $DB->get_record_sql($sql, ["couseid" => $couseid]);
    }

    /**
     * User is enrolled ?
     *
     * @param int $userid
     * @param int $enrolid
     * @return mixed
     */
    protected function is_user_enrolled (int $userid, int $enrolid) {
        global $DB;
        $sql = "SELECT u.id
                  FROM {user_enrolments} ue
                  JOIN {user} u
                    ON ue.userid = u.id
                 WHERE ue.enrolid = :enrolid
                   AND ue.userid = :userid";
        return $DB->get_records_sql($sql, ["userid" => $userid, "enrolid" => $enrolid]);
    }

    /**
     * Delete expired badges.
     *
     * @param int $badgeid
     * @param int $userid
     * @return mixed
     */
    protected function delete_expired_badge_issued (int $badgeid, int $userid) {
        global $DB;
        return $DB->delete_records('local_ibob_badge_issued', ['badgeid' => $badgeid, 'userid' => $userid]);
    }

    /**
     * Return array valid badges
     *
     * @param int $userid
     * @param int $badgeid
     * @return object
     */
    protected function is_badge_issued (int $userid, int $badgeid) {
        global $DB;
        return $DB->get_record('local_ibob_badge_issued', ["userid" => $userid, "badgeid" => $badgeid], $fields = 'id',
            $strictness = IGNORE_MISSING);
    }

    /**
     * Return array valid badges
     *
     * @param int $userid
     * @return array
     */
    protected function get_local_badges_from_user (int $userid) {
        global $DB;
        $sql = "SELECT BI.id,BI.badgeid,B.name,BI.expirationdate
                  FROM {local_ibob_badge_issued} BI
            INNER JOIN {local_ibob_badges} B
                    ON BI.badgeid=B.id
                 WHERE BI.userid=:userid";
        return $DB->get_records_sql($sql, ["userid" => $userid]);
    }

    /**
     * Get the user odp.
     *
     * @return array
     */
    protected function get_users_obp (): array {
        global $DB;
        $sql = "SELECT user_id,key_field
                  FROM {local_ibob_user_apikey}
                 WHERE provider_id=1 and confirmation_needed=0";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get providers info.
     *
     * @param int $providerid
     * @return array
     */
    protected function get_providers_info (int $providerid): array {
        global $DB;
        $sql = "SELECT id,apiurl
                  FROM {local_ibob_providers}
                 WHERE id = :providerid";
        return $DB->get_records_sql($sql, ["providerid" => $providerid]);
    }

    /**
     * Badge already stored locally ?
     *
     * @param string $badgename
     * @param string $issuername
     * @return object
     */
    protected function is_stored_in_local (string $badgename, string $issuername) {
        global $DB;
        return $DB->get_record('local_ibob_badges', ["name" => $badgename, 'issuername' => $issuername], $fields = 'id',
             $strictness = IGNORE_MISSING);
    }

    /**
     * Get all the courses from an enrolment badge id.
     *
     * @param int $badgeid
     * @return array
     */
    protected function get_all_courses_from_enrolment_badge (int $badgeid): array {
        global $DB;
        $likecustom1 = $DB->sql_like('E.customtext1', ':badgeid1');
        $likecustom2 = $DB->sql_like('E.customtext1', ':badgeid2');
        $likecustom3 = $DB->sql_like('E.customtext1', ':badgeid3');
        $likecustom4 = $DB->sql_like('E.customtext1', ':badgeid4');
        $sql = "SELECT E.courseid, C.fullname
                  FROM {enrol} E
                  JOIN {course} C
                    ON C.id = E.courseid
                 WHERE E.enrol = 'ibobenrol'
                   AND ($likecustom1
                    OR $likecustom2
                    OR $likecustom3
                    OR $likecustom4)";
        return $DB->get_records_sql($sql, ['badgeid1' => $badgeid, 'badgeid2' => '%#'. $badgeid, 'badgeid3' => $badgeid. '#%',
        'badgeid4' => '%#'. $badgeid .'#%', ]);
    }


    /**
     * Get courses from an enrolment bade id and user id.
     *
     * @param int $badgeid
     * @param int $userid
     * @return array
     */
    protected function get_courses_from_enrolment_badge (int $badgeid, int $userid): array {
        global $DB;

        $likecustom1 = $DB->sql_like('E.customtext1', ':badgeid1');
        $likecustom2 = $DB->sql_like('E.customtext1', ':badgeid2');
        $likecustom3 = $DB->sql_like('E.customtext1', ':badgeid3');
        $likecustom4 = $DB->sql_like('E.customtext1', ':badgeid4');
        $sql = "SELECT E.courseid, UE.id as enrolmentid
                  FROM {enrol} E
             LEFT JOIN {user_enrolments} UE
                    ON E.id = UE.enrolid
                 WHERE E.enrol = 'ibobenrol'
                   AND UE.userid = :userid
                   AND ($likecustom1
                    OR $likecustom2
                    OR $likecustom3
                    OR $likecustom4)";
        return $DB->get_records_sql($sql, ['userid' => $userid, 'badgeid1' => $badgeid, 'badgeid2' => '%#'. $badgeid,
        'badgeid3' => $badgeid. '#%', 'badgeid4' => '%#'. $badgeid .'#%', ]);
    }

    /**
     * Get all users with a provider.
     *
     * @return array
     */
    protected function get_users_with_provider (): array {
        global $DB;
        $providerid = 1;
        $sql = "SELECT {local_ibob_user_apikey}.id,{local_ibob_user_apikey}.key_field,{local_ibob_user_apikey}.user_id,
                       {local_ibob_providers}.apiurl
                  FROM {local_ibob_user_apikey}
                  JOIN {local_ibob_providers}
                    ON {local_ibob_providers}.id = {local_ibob_user_apikey}.provider_id
                 WHERE confirmation_needed = 0 and provider_id = :providerid";
        return $DB->get_records_sql($sql, ["providerid" => $providerid]);
    }
}
