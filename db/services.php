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
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$functions = [
    'local_ibob_detail_badge_function' => [
        'classname' => 'local_ibob_external',
        'methodname' => 'detail_badge_function',
        'classpath' => 'local/ibob/externallib.php',
        'description' => 'Print badge detail',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => '',
    ],
];

$services = [
    'ibobwebservice' => [
        'functions' => ['local_ibob_detail_badge_function'],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'myibobwebservice',
    ],
];
