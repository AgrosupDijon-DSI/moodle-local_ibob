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

require_once($CFG->libdir."/externallib.php");

/**
 * External lib for ibob.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ibob_external extends external_api {
    /**
     * Get badge infos.
     *
     * @param int $badgeid
     * @return array
     */
    public static function get_badge(int $badgeid) {
        global $DB, $USER;

        $sql = "SELECT *,expirationdate as expirationformateddate
                  FROM {local_ibob_badges}
             LEFT JOIN {local_ibob_badge_issued}
                    ON {local_ibob_badges}.id = {local_ibob_badge_issued}.badgeid
                 WHERE {local_ibob_badges}.id = :id";
        $mybadge = $DB->get_record_sql($sql, ["id" => $badgeid]);
        $response = ['mybadge' => $mybadge];
        return json_encode($response);
    }

    /**
     * Badge detail function.
     *
     * @param int $badgeid
     * @return mixed
     */
    public static function detail_badge_function(int $badgeid) {
        self::validate_parameters(self::detail_badge_function_parameters(), ['badgeid' => $badgeid]);
        return self::get_badge($badgeid);
    }

    /**
     * Badge detail function return.
     *
     * @return external_value
     */
    public static function detail_badge_function_returns() {
        return new external_value(PARAM_RAW, 'Json returned');
    }

    /**
     * Badge detail function return.
     *
     * @return external_function_parameters
     */
    public static function detail_badge_function_parameters() {
        return new external_function_parameters(
            [
                'badgeid' => new external_value(PARAM_INT, 'badge id', VALUE_REQUIRED),
            ]
        );
    }
}
