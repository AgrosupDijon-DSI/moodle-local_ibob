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

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\enrol_instance_created',
        'callback' => 'local_ibob\observer\observer_enrol_ibob::enrol_instance_created',
        'priority' => 9999,
    ],
    [
        'eventname' => '\core\event\enrol_instance_updated',
        'callback' => 'local_ibob\observer\observer_enrol_ibob::enrol_instance_updated',
        'priority' => 9999,
    ],
    [
        'eventname' => 'core\event\user_loggedin',
        'callback' => 'local_ibob\observer\observer_enrol_ibob::enrol_user_loggedin',
        'priority' => 9999,
    ],
];
