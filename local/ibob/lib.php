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
const BADGE_IMAGE_SIZE_TINY = 22;
/**
 * @var int small badge image.
 */
const BADGE_IMAGE_SIZE_SMALL = 32;
/**
 * @var int normal badge image.
 */
const BADGE_IMAGE_SIZE_NORMAL = 50;

/**
 * Get user provider.
 *
 * @param int $userid
 * @return mixed
 */
function get_user_providers(int $userid) {
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
function get_user_provider_json(string $url, string $email) {
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
function print_badge(string $imgsize, string $img, string $name, string $badgeuniqueid, int $badgeid) {
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
        $ouserprovider = get_user_providers($userid);
        $abadges = [];

        if ($ouserprovider) { // User has a provider.
            $url = $ouserprovider->apiurl;
            $oapikey = json_decode($ouserprovider->key_field);
            $email = $oapikey->email;
            $acurl = get_user_provider_json($url, $email);

            if (is_null($acurl['json']) && $acurl['code'] != 200) {
                throw new Exception(get_string('testbackpackapiurlexception', 'local_ibob',
                        (object)['url' => $acurl['fullurl'], 'errorcode' => $acurl['code']])
                    , $acurl['code']);
            } else {
                if ($acurl['code'] === 200) {
                    if (!is_array($acurl['json']->userId)) {
                        $ajson = [$acurl['json']->userId];
                    } else {
                        $ajson = $acurl['json']->userId;
                    }
                    // Global Public/social Open Badges List (public group : 0 and social group : 1).
                    $agroupid = [0];
                    foreach ($ajson as $backpackitem) {
                        foreach ($agroupid as $groupid) {
                            $fullurl = $url . $backpackitem . '/group/' . $groupid . '.json';
                            $output = $acurl['curl']->get($fullurl);
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
                $content .= print_badge(
                    BADGE_IMAGE_SIZE_NORMAL,
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
