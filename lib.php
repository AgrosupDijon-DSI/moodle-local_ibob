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
 * @package     local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds OB badges to profile pages.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param moodle_course $course
 */

/**
 * @var int tiny badge image.
 */
const LOCAL_IBOB_BADGE_IMAGE_SIZE_TINY = 22;
/**
 * @var int small badge image.
 */
const LOCAL_IBOB_BADGE_IMAGE_SIZE_SMALL = 32;
/**
 * @var int normal badge image.
 */
const LOCAL_IBOB_BADGE_IMAGE_SIZE_NORMAL = 50;

/**
 * Get user provider.
 *
 * @param int $userid
 * @return mixed
 */
function local_ibob_get_user_providers(int $userid) {
    global $DB;
    $sql = "SELECT {local_ibob_providers}.apiurl,{local_ibob_user_apikey}.key_field
              FROM {local_ibob_user_apikey} JOIN {local_ibob_providers}
                ON {local_ibob_providers}.id={local_ibob_user_apikey}.provider_id
             WHERE {local_ibob_user_apikey}.user_id=:userid";
    return $DB->get_record_sql(
        $sql,
        ["userid" => $userid]);
}

/**
 * Get json user provider.
 *
 * @param string $url
 * @param string $email
 * @return array
 */
function local_ibob_get_user_provider_json(string $url, string $email) {
    $curl = new curl();
    $fullurl = $url . 'convert/email';
    $output = $curl->post($fullurl,
        ['email' => $email]);
    $json = json_decode($output);
    $code = $curl->info['http_code'];
    return ['json' => $json, 'code' => $code, 'fullurl' => $fullurl, 'curl' => $curl];
}

/**
 * Print badge.
 *
 * @param string $imgsize
 * @param string $img
 * @param string $name
 * @param string $badgeuniqueid
 * @param int $badgeid
 * @return mixed
 */
function local_ibob_print_badge(string $imgsize, string $img, string $name, string $badgeuniqueid, int $badgeid) {
    $params = ["src" => $img, "alt" => $name, "width" => $imgsize];
    $badgeimage = html_writer::empty_tag("img", $params);
    $badgename = html_writer::tag('p', s($name), ['class' => 'badgename']);
    return html_writer::div($badgeimage . $badgename, "ibob-badge", ['id' => $badgeuniqueid, 'data-id' => $badgeid]);
}

/**
 * Myprofile navigation.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @return void
 * @throws Exception
 */
function local_ibob_myprofile_navigation(\core_user\output\myprofile\tree $tree) {
    global $CFG, $USER, $PAGE, $DB;

    require_once($CFG->libdir . '/filelib.php');
    $userid = optional_param('id', '', PARAM_INT);
    if ($userid == '') {
        $userid = $USER->id;
    }
    $show = 1; // Maybe modified later, if we add the possibility to print or not our open badges to myprofile.
    if ($show) {
        $category = new core_user\output\myprofile\category('local_ibob/badges', get_string('profilebadgelist', 'local_ibob'),
            null);
        $tree->add_category($category);

        // Open Badges list construction.
        $ouserprovider = local_ibob_get_user_providers($userid);
        $abadges = [];

        if ($ouserprovider) { // User has a provider.
            $url = $ouserprovider->apiurl;
            $oapikey = json_decode($ouserprovider->key_field);
            $email = $oapikey->email;
            $curlresult = local_ibob_get_user_provider_json($url, $email);

            if (is_null($curlresult['json']) && $curlresult['code'] != 200) {
                throw new Exception(get_string('testbackpackapiurlexception', 'local_ibob',
                        (object)['url' => $curlresult['fullurl'], 'errorcode' => $curlresult['code']])
                    , $curlresult['code']);
            } else {
                if ($curlresult['code'] === 200) {
                    if (!is_array($curlresult['json']->userId)) {
                        $ajson = [$curlresult['json']->userId];
                    } else {
                        $ajson = $curlresult['json']->userId;
                    }
                    // Global Public/social Open Badges List (public group : 0 and social group : 1).
                    $agroupid = [0];
                    foreach ($ajson as $backpackitem) {
                        foreach ($agroupid as $groupid) {
                            $fullurl = $url . $backpackitem . '/group/' . $groupid . '.json';
                            $output = $curlresult['curl']->get($fullurl);
                            $alistbadgesuser = json_decode($output, true);
                            // Badge suppression if expiration date > now.
                            if (count($alistbadgesuser) > 2) {
                                foreach ($alistbadgesuser['badges'] as $abadgetemp) {
                                    if (isset($abadgetemp['assertion']['expires'])) {
                                        if ($abadgetemp['assertion']['expires'] !== '') {
                                            if ($abadgetemp['assertion']['expires'] > time()) {
                                                $abadges[] = $abadgetemp;
                                            } else if ($idbadge = $DB->get_record_select(
                                                'local_ibob_badges',
                                                'name = :name',
                                                ['name' => $abadgetemp['name'] ?? ''],
                                                $fields = 'id',
                                                $strictness = IGNORE_MISSING)) {
                                                $DB->delete_records(
                                                    'local_ibob_badge_issued',
                                                    ['badgeid' => $idbadge, 'userid' => $userid]);
                                            }
                                        } else {
                                            $abadges[] = $abadgetemp;
                                        }
                                    } else {
                                        $abadgetemp['assertion']['expires'] = '';
                                        $abadges[] = $abadgetemp;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($abadges)) {
            // Print badges.
            $content = '';
            foreach ($abadges as $abadge) {
                $badge = $abadge['assertion']['badge'];
                // Insert in database if not already present.
                $badgeid = $DB->get_field_select(
                    'local_ibob_badges',
                    'id',
                    'name = :name and issuerurl = :issuerurl',
                    ['name' => $badge['name'], 'issuerurl' => $badge['issuer']['url']]);
                if (!$badgeid) {
                    $obadge = new stdClass();
                    $obadge->name = $badge['name'];
                    $obadge->description = substr($badge['description'], 0, 1000);
                    $obadge->issuername = $badge['issuer']['name'];
                    $obadge->issuerurl = $badge['issuer']['url'];
                    $obadge->issuercontact = $badge['issuer']['email'];
                    $obadge->image = $badge['image'];
                    $obadge->usermodified = $userid;
                    $obadge->timecreated = time();
                    $badgeid = $DB->insert_record('local_ibob_badges', $obadge);
                }
                if (!$DB->get_record_select(
                    'local_ibob_badge_issued',
                    'userid=:userid and badgeid=:badgeid',
                    ['userid' => $userid, 'badgeid' => $badgeid],
                    $fields = 'id',
                    $strictness = IGNORE_MISSING)) {
                    $obadgeissued = new stdClass();
                    $obadgeissued->userid = $userid;
                    $obadgeissued->badgeid = $badgeid;
                    if ($abadge['assertion']['expires']) {
                        $obadgeissued->expirationdate = $abadge['assertion']['expires'];
                    }
                    $DB->insert_record(
                        'local_ibob_badge_issued',
                        $obadgeissued);
                }

                $badgeuniqueid = 'badge_' . $badgeid;
                $content .= local_ibob_print_badge(
                    LOCAL_IBOB_BADGE_IMAGE_SIZE_NORMAL,
                    $badge['image'],
                    $badge['name'],
                    $badgeuniqueid,
                    $badgeid);
            }
            $PAGE->requires->js_call_amd('local_ibob/userbadgedisplayer', 'init');
        } else {
            $content = html_writer::tag('div', get_string('noBadgesFound', 'local_ibob'), ['class' => 'no-badges-found']);
        }

        $localnode = $mybadges = new core_user\output\myprofile\node('local_ibob/badges', 'ibobbadges',
            '', null, null, $content, null, 'local-ibob');
        $tree->add_node($localnode);
    }
}

/**
 * Adds the IBOB-links to Moodle's settings navigation.
 *
 * @param settings_navigation $navigation
 */
function local_ibob_extend_settings_navigation(settings_navigation $navigation) {
    if (dirname($_SERVER['PHP_SELF']).'/'.basename($_SERVER['PHP_SELF']) == '/user/preferences.php') {
        if (isloggedin() && !isguestuser()) {
            $branch = $navigation->find('usercurrentsettings', navigation_node::TYPE_CONTAINER);
            $ibobprefs = $branch->add(
                get_string('ibobprefs', 'local_ibob'),
                null,
                navigation_node::TYPE_CONTAINER,
                'Ibob pref',
                'ibobprefs');
            $node = navigation_node::create(get_string('ibobprefslink', 'local_ibob'),
                new moodle_url('/local/ibob/userconfig.php'));
            $ibobprefs->add_node($node);
        }
    }
}


/**
 * Delete user badge.
 *
 * @param int $userid
 * @return void
 */
function local_ibob_delete_badges_user(int $userid) {
    global $DB;
    $DB->delete_records('local_ibob_badge_issued', ['userid' => $userid]);
}

/**
 * Update api user key.
 *
 * @param object $infoapiuser
 * @return mixed
 */
function local_ibob_update_api_key_user($infoapiuser) {
    global $DB;
    $ouserapikey = new stdClass();
    $ouserapikey->id = $infoapiuser->id;
    $ouserapikey->timemodified = time();
    $ouserapikey->key_field = json_encode(['email' => $infoapiuser->confirmation_email_wanted]);
    $ouserapikey->confirmation_needed = 0;
    $ouserapikey->confirmation_code = '';
    $ouserapikey->confirmation_expiration_date = null;
    $ouserapikey->confirmation_email_wanted = '';
    $apikeyuser = $DB->update_record('local_ibob_user_apikey', $ouserapikey);
    return $apikeyuser;
}

/**
 * Get user info.
 *
 * @param int $code
 * @return mixed
 */
function local_ibob_get_info_user_by_code(int $code) {
    global $DB;
    $sql = "SELECT *
              FROM {local_ibob_user_apikey}
             WHERE confirmation_code = :confirmation_code";
    return $DB->get_record_sql($sql, ["confirmation_code" => $code]);
}

/**
 * Generate confirmation code.
 *
 * @return int
 */
function local_ibob_generate_confirmation_code() {
    return mt_rand(1000, 9999);
}

/**
 * Delete enrolments from user.
 *
 * @param int $userid
 * @return void
 */
function local_ibob_delete_enrolments_user(int $userid) {
    global $DB;
    $sql = "
        SELECT E.courseid
          FROM {enrol} E
          JOIN {user_enrolments} UE
            ON E.id = UE.enrolid
         WHERE E.enrol = 'ibobenrol'
           AND UE.userid = :userid
    ";
    foreach ($DB->get_records_sql($sql, ["userid" => $userid]) as $oenrolment) {
        // Disenrol the user.
        $instances = $DB->get_records('enrol', ['courseid' => $oenrolment->courseid]);
        foreach ($instances as $instance) {
            $plugin = \enrol_get_plugin($instance->enrol);
            $plugin->unenrol_user($instance, $userid);
        }
    }
}


/**
 * Update confirmation sequence.
 * @param int $userid
 * @param int $apikeyid
 * @param string $newemail
 * @return mixed
 */
function local_ibob_update_confirmation_sequence_init(int $userid, int $apikeyid, string $newemail) {
    global $DB;
    $ouserapikey = new stdClass();
    $ouserapikey->id = $apikeyid;
    $ouserapikey->confirmation_needed = true;
    $expirationdate = time() + 86400;
    $ouserapikey->confirmation_code = local_ibob_generate_confirmation_code();
    $ouserapikey->confirmation_expiration_date = $expirationdate;
    $ouserapikey->confirmation_email_wanted  = $newemail;

    local_ibob_send_email_confirmation($userid, $newemail, $ouserapikey);
    $apikeyuser = $DB->update_record('local_ibob_user_apikey', $ouserapikey);
    return $apikeyuser;
}

/**
 * Send email confirmation to user.
 * @param int $userid
 * @param string $newemail
 * @param object $ouserapikey
 * @return void
 */
function local_ibob_send_email_confirmation(int $userid, string $newemail, $ouserapikey) {
    global $CFG;
    $adateexpiration = getdate($ouserapikey->confirmation_expiration_date);
    $message = new \core\message\message();
    $message->component = 'local_ibob';
    $message->name = 'ibobemailchange';
    $message->userto = \core_user::get_user($userid);
    $message->userto->email = $newemail;
    $message->userfrom = \core_user::get_noreply_user();
    $message->subject = get_string('subjectconfirmchangeemail', 'local_ibob');
    $smoodlelink = html_writer::link(
            new moodle_url($CFG->wwwroot.'/local/ibob/emailconfirmation.php'),
            get_string('messconfirmchangeemaillinktext', 'local_ibob')
    );
    $timeformatemail = $adateexpiration['hours']."h".$adateexpiration['minutes'];
    $language = substr ($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    switch ($language) {
        case "fr" :
            $dateformatemail = $adateexpiration['mday']."/".$adateexpiration['mon']."/".$adateexpiration['year'];
        default :
            $dateformatemail = $adateexpiration['mon']."-".$adateexpiration['mday']."-".$adateexpiration['year'];
    }
    $avarsemail = ['wwwroot' => $CFG->wwwroot, 'link' => $smoodlelink, 'code' => $ouserapikey->confirmation_code,
            'date' => $dateformatemail, 'time' => $timeformatemail, ];
    $message->fullmessage .= get_string('messconfirmchangeemail', 'local_ibob', $avarsemail);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml .= get_string('messconfirmchangeemailhtml', 'local_ibob', $avarsemail);

    message_send($message);
}

/**
 * Insert api key from user.
 * @param int $userid
 * @param string $email
 * @return mixed
 */
function local_ibob_insert_api_key_user(int $userid, string $email) {
    global $DB;
    $ouserapikey = new stdClass();
    // For now, provider = 1 because we only deal with OBP.
    $ouserapikey->provider_id = 1;
    $ouserapikey->timecreated = time();
    $ouserapikey->key_field = json_encode(['email' => 'waiting@validation.com']);
    $ouserapikey->confirmation_email_wanted = $email;
    $ouserapikey->user_id = $userid;
    $apikeyuser = $DB->insert_record('local_ibob_user_apikey', $ouserapikey);
    return $apikeyuser;
}

/**
 * Delete api key from user.
 * @param int $apiuserkey
 * @return void
 */
function local_ibob_delete_api_key_user(int $apiuserkey) {
    global $DB;
    $DB->delete_records('local_ibob_user_apikey', ['id' => $apiuserkey]);
}

/**
 * Get user infos.
 * @param int $userid
 * @return mixed
 */
function local_ibob_get_info_user_by_id(int $userid) {
    global $DB;
    return $DB->get_record_select('local_ibob_user_apikey', 'user_id = :user_id', ['user_id' => $userid], '*', IGNORE_MISSING);
}
