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

require_once($CFG->libdir . "/externallib.php");

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
    public static function ibob_detail_badge_webservice_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters().
        // The external_function_parameters constructor expects an array of external_description.
        return new external_function_parameters(
            ['PARAM1' => new external_value(PARAM_TYPE, 'human description of PARAM1')]
        );
    }

    /**
     * The function itself
     * @param string $param1
     * @return string
     */
    public static function ibob_detail_badge_webservice(string $param1): void {

        // Parameters validation.
        $params = self::validate_parameters(self::ibob_detail_badge_webservice_parameters(),
            ['PARAM1' => $param1]);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function ibob_detail_badge_webservice_returns() {
        return new external_value(PARAM_TYPE, 'human description of the returned value');
    }
}
