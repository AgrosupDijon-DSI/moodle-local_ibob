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
 * @category    string
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

global $USER, $DB, $OUTPUT, $PAGE;
$content = '';
$action  = '';
$returnurl = optional_param('returnurl', '/local/ibob/userconfig.php', PARAM_LOCALURL);
$cancelurl = optional_param('returnurl', '/user/preferences.php', PARAM_LOCALURL);

$context = context_user::instance($USER->id);
$url = new moodle_url('/local/ibob/userconfig.php', ['action' => $action]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$mform = new \local_ibob\form\userconfig(null, ['returnurl' => $returnurl]);

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    redirect(new moodle_url($cancelurl));
} else if ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    if ($DB->record_exists('local_ibob_user_apikey', ['user_id' => $USER->id])) {
        $infoapiuser = get_info_user($USER->id);
        // Is the confirmation sequence initiated ?
        if ($infoapiuser->confirmation_needed === 0) {
            // Case 1 : verification : has the email changed ?
            if ($infoapiuser->key_field !== '') { // Email recorded.
                $jsondecoded = json_decode($infoapiuser->key_field);
                if ($jsondecoded->email !== $fromform->providerapikey) {
                    if ($fromform->providerapikey !== '') {
                        // Case 1 : email has changed, record the new email in database and initiate verification sequence.
                        if (!update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                            echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error1', 'local_ibob');exit;
                        }
                    } else {
                        delete_enrolments_user($USER->id);
                        delete_badges_user($USER->id);
                        delete_api_key_user($infoapiuser->id);
                    }
                }
            } else {
                // Case 2 : no old email, record the new email in database and initiate verification sequence.
                if (!update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                    echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error2', 'local_ibob');exit;
                }
            }
        } else {
            // Case 3 : waiting for the confirmation code.
            if ($fromform->providerapikey !== '') { // Email wanted typed, re-doing the verfication sequence.
                if (!update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                    echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error3', 'local_ibob');exit;
                }
            } else {
                // Email typed is empty, erasing provider and issued badges.
                delete_enrolments_user($USER->id);
                delete_badges_user($USER->id);
                delete_api_key_user($infoapiuser->id);
            }
        }
    } else {
        // Case 4 : no provider yet for the user.
        if ($fromform->providerapikey !== '') {
            // Case 4 ; create provider for the user.
            if ($apikeyid = insert_api_key_user($USER->id, $fromform->providerapikey)) {
                // Case 4 : initiate verification sequence.
                update_confirmation_sequence_init($USER->id, $apikeyid, $fromform->providerapikey);
            }
        }
    }
    redirect(new moodle_url($returnurl));
}

// Set default data.
$toform = [];
if ($ojsonapikey = get_info_user($USER->id)) { // User has a provider.
    if ($ojsonapikey->key_field !== '') {
        $jsondecoded = json_decode($ojsonapikey->key_field);
        if ($ojsonapikey->confirmation_needed == 1) { // Waiting for confirmation.
            $jsondecoded->email = $ojsonapikey->confirmation_email_wanted;
        }
        $toform = ['providerapikey' => $jsondecoded->email, 'hasprovider' => $ojsonapikey->provider_id];
    }
} else {
    $toform = ['providerapikey' => $USER->email, 'hasprovider' => '0'];
}

$mform->set_data($toform);
echo $OUTPUT->header();
$content .= $mform->render();
$content .= $OUTPUT->footer();
echo $content;


/**
 * Delete enrolments from user.
 *
 * @param int $userid
 * @return void
 */
function delete_enrolments_user(int $userid) {
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
 * Delete api key from user.
 * @param int $apiuserkey
 * @return void
 */
function delete_api_key_user(int $apiuserkey) {
    global $DB;
    $DB->delete_records('local_ibob_user_apikey', ['id' => $apiuserkey]);
}

/**
 * Delete badges from user.
 * @param int $userid
 * @return void
 */
function delete_badges_user(int $userid) {
    global $DB;
    $DB->delete_records('local_ibob_badge_issued', ['userid' => $userid]);
}

/**
 * Insert api key from user.
 * @param int $userid
 * @param string $email
 * @return mixed
 */
function insert_api_key_user(int $userid, string $email) {
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
 * Update api key from user.
 * @param int $userid
 * @param object $formdata
 * @return mixed
 */
function update_api_key_user(int $userid, $formdata) {
    global $DB;
    $ouserapikey = new stdClass();
    $ouserapikey->id = $userid;
    $ouserapikey->key_field = json_encode(['email' => $formdata->providerapikey]);
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
function send_email_confirmation(int $userid, string $newemail, $ouserapikey) {
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
 * Update confirmation sequence.
 * @param int $userid
 * @param int $apikeyid
 * @param string $newemail
 * @return mixed
 */
function update_confirmation_sequence_init(int $userid, int $apikeyid, string $newemail) {
    global $DB;
    $ouserapikey = new stdClass();
    $ouserapikey->id = $apikeyid;
    $ouserapikey->confirmation_needed = true;
    $expirationdate = time() + 86400;
    $ouserapikey->confirmation_code = generate_confirmation_code();
    $ouserapikey->confirmation_expiration_date = $expirationdate;
    $ouserapikey->confirmation_email_wanted  = $newemail;

    send_email_confirmation($userid, $newemail, $ouserapikey);
    $apikeyuser = $DB->update_record('local_ibob_user_apikey', $ouserapikey);
    return $apikeyuser;
}

/**
 * Get user infos.
 * @param int $userid
 * @return mixed
 */
function get_info_user(int $userid) {
    global $DB;
    return $DB->get_record_select('local_ibob_user_apikey', 'user_id = :user_id', ['user_id' => $userid], '*', IGNORE_MISSING);
}

/**
 * Generate confirmation code.
 * @return int
 */
function generate_confirmation_code() {
    return mt_rand(1000, 9999);
}
