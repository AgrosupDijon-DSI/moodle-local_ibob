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

namespace local_ibob;
/**
 * Plugin local_ibob.
 *
 * @package     local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ibob_badges_issued extends \core\persistent {

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    public static function define_properties() {
        return [
            'userid' => [
                'type' => PARAM_INT,
            ],
            'badgeid' => [
                'type' => PARAM_INT,
            ],
            'expirationdate' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * @var
     */
    const TABLE = 'local_ibob_badge_issued';
}
