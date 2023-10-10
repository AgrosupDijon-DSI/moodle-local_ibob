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
 * Execute local_ibob upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_ibob_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2023051803) {
        // Define field email_send to be added to local_ibob_user_notification.
        $table = new xmldb_table('local_ibob_user_notification');
        $field = new xmldb_field('email_send', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'notification_viewed');

        // Conditionally launch add field email_send.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'email_send');

        // Conditionally launch add field course_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ibob savepoint reached.
        upgrade_plugin_savepoint(true, '2023051840', 'local', 'ibob');
    }
    return true;
}
