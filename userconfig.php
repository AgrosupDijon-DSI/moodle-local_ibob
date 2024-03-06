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
require_once('lib.php');

require_login();

global $USER, $DB, $OUTPUT, $PAGE;
$content = '';
$action  = '';
$returnurl = optional_param('returnurl', '/local/ibob/userconfig.php', PARAM_LOCALURL);
$cancelurl = optional_param('returnurl', '/user/preferences.php', PARAM_LOCALURL);

$context = context_user::instance($USER->id);
$url = new moodle_url('/local/ibob/userconfig.php', ['action' => $action, 'sesskey' => sesskey()]);

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
        $infoapiuser = local_ibob_get_info_user_by_id($USER->id);
        // Is the confirmation sequence initiated ?
        if ($infoapiuser->confirmation_needed === 0) {
            // Case 1 : verification : has the email changed ?
            if ($infoapiuser->key_field !== '') { // Email recorded.
                $jsondecoded = json_decode($infoapiuser->key_field);
                if ($jsondecoded->email !== $fromform->providerapikey) {
                    if ($fromform->providerapikey !== '') {
                        // Case 1 : email has changed, record the new email in database and initiate verification sequence.
                        if (!local_ibob_update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                            echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error1', 'local_ibob');
                            exit;
                        }
                    } else {
                        local_ibob_delete_enrolments_user($USER->id);
                        local_ibob_delete_badges_user($USER->id);
                        local_ibob_delete_api_key_user($infoapiuser->id);
                    }
                }
            } else {
                // Case 2 : no old email, record the new email in database and initiate verification sequence.
                if (!local_ibob_update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                    echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error2', 'local_ibob');exit;
                }
            }
        } else {
            // Case 3 : waiting for the confirmation code.
            if ($fromform->providerapikey !== '') { // Email wanted typed, re-doing the verfication sequence.
                if (!local_ibob_update_confirmation_sequence_init($USER->id, $infoapiuser->id, $fromform->providerapikey)) {
                    echo get_string('userconfig:errorgeneral', 'local_ibob').get_string('userconfig:error3', 'local_ibob');exit;
                }
            } else {
                // Email typed is empty, erasing provider and issued badges.
                local_ibob_delete_enrolments_user($USER->id);
                local_ibob_delete_badges_user($USER->id);
                local_ibob_delete_api_key_user($infoapiuser->id);
            }
        }
    } else {
        // Case 4 : no provider yet for the user.
        if ($fromform->providerapikey !== '') {
            // Case 4 ; create provider for the user.
            if ($apikeyid = local_ibob_insert_api_key_user($USER->id, $fromform->providerapikey)) {
                // Case 4 : initiate verification sequence.
                local_ibob_update_confirmation_sequence_init($USER->id, $apikeyid, $fromform->providerapikey);
            }
        }
    }
    redirect(new moodle_url($returnurl));
}

// Set default data.
$toform = [];
if ($ojsonapikey = local_ibob_get_info_user_by_id($USER->id)) { // User has a provider.
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
