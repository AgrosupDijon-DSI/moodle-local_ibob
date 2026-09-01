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
 * PLUGIN local_ibob
 *
 * @package    local_ibob
 * @category   external
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ibob\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * PLUGIN local_ibob
 *
 * @package    local_ibob
 * @category   external
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_detail_badge extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function detail_badge_function_parameters(): external_function_parameters {
        return new external_function_parameters(
            ['badgeid' => new external_value(PARAM_INT, 'badge id', VALUE_REQUIRED)]
        );
    }

    /**
     * Get badge details.
     * @param int $badgeid
     * @return string
     */
    public static function detail_badge_function(int $badgeid): string {
        global $DB;
        self::validate_parameters(self::detail_badge_function_parameters(), ['badgeid' => $badgeid]);
        $sql = "SELECT *, expirationdate as expirationformateddate
                  FROM {local_ibob_badges}
             LEFT JOIN {local_ibob_badge_issued}
                    ON {local_ibob_badges}.id = {local_ibob_badge_issued}.badgeid
                 WHERE {local_ibob_badges}.id = :id";
        $mybadge = $DB->get_record_sql($sql, ['id' => $badgeid]);
        return json_encode(['mybadge' => $mybadge]);
    }

    /**
     * Returns description of method result value
     * @return external_value
     */
    public static function detail_badge_function_returns(): external_value {
        return new external_value(PARAM_RAW, 'Json returned');
    }
}
