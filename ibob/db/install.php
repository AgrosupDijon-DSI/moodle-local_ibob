<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin local_ibob.
 *
 * @package     local_ibob
 * @category    upgrade
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install function.
 *
 * @return boolean
 **/
function xmldb_local_ibob_install() {
    global $CFG, $DB;

    // Set default backpack sources.
    $obackpackprovider = new stdClass();
    $obackpackprovider->apiurl = 'https://openbadgepassport.com/displayer/';
    $obackpackprovider->fullname = 'Open Badge Passport';
    $obackpackprovider->shortname = 'obp';
    $obackpackprovider->usermodified = '1';
    $obackpackprovider->timecreated = time();
    $DB->insert_record('local_ibob_providers', $obackpackprovider);
    unset($obackpackprovider);

    return true;
}
