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
 * User config form.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ibob\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * User config form.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userconfig extends \moodleform {

    /**
     * Defines forms elements
     */
    protected function definition() {

        $mform = $this->_form;
        $mform->addElement('html', '<div class="formheader"><h3>'.get_string('emaildescriptiontitle', 'local_ibob').'</h3></div>');
        $mform->addElement('html', '<div class="formheader"><p>'.get_string('emaildescription', 'local_ibob').'</p></div>');
        $mform->addElement('html', '<p>'.get_string('emailsequenceexplanation', 'local_ibob').'</p>');
        $mform->addElement('text', 'providerapikey', get_string('email'), ['size' => '35']);
        $mform->setType('providerapikey', PARAM_EMAIL);
        $mform->addRule('providerapikey', get_string('invalidemail', 'local_ibob'), 'email', null, 'client');
        if (self::printvalidationtext()) {
            $mform->addElement('html', '<div><p>'.get_string('emailvalidated', 'local_ibob').
                ' : <strong>'.get_string(self::gettextvalidatedemail(), 'local_ibob').'</strong> </p></div>');
            self::generatevalidationlink($mform);
        }
        $this->add_action_buttons();
        $mform->addElement('hidden', 'hasprovider', null);
        $mform->setType('hasprovider', PARAM_INT);

        /**
         * Perform minimal validation on the settings form.
         *
         * @param array $data
         * @param array $files
         * @return array
         */
        function validation($data, $files): array {
            return [];
        }
    }

    /**
     * Get the validation email string.
     *
     * @return string
     */
    public function gettextvalidatedemail(): string {
        global $DB, $USER;
        $confirmationneeded = $DB->get_record_select(
            'local_ibob_user_apikey',
            'user_id=:user_id',
            ['user_id' => $USER->id],
            'confirmation_needed',
            IGNORE_MISSING);
        $mystr = 'emailvalidatedno';
        if ($confirmationneeded) {
            if ($confirmationneeded->confirmation_needed == 0) {
                $mystr = 'emailvalidatedyes';
            }
        }
        return $mystr;
    }

    /**
     * Generate the validation link string.
     *
     * @param object $mform
     */
    public function generatevalidationlink($mform) {
        global $DB, $USER, $CFG;
        $confirmationneeded = $DB->get_record_select(
            'local_ibob_user_apikey',
            'user_id=:user_id',
            ['user_id' => $USER->id],
            'confirmation_needed',
            IGNORE_MISSING);
        if ($confirmationneeded) {
            if ($confirmationneeded->confirmation_needed == 1) {
                $mform->addElement('static', 'staticlink', '', '<p><a href="'
                    .$CFG->wwwroot.'/local/ibob/emailconfirmation.php" target="_self" title="'
                    .get_string('addvalidationcodelink', 'local_ibob').'">'.get_string('addvalidationcodelink', 'local_ibob')
                    .'</a></p>');
            }
        }
    }

    /**
     * Has provider ?
     */
    public function printvalidationtext() {
        global $DB, $USER;
        $hasprovider = $DB->get_record_select(
            'local_ibob_user_apikey',
            'user_id=:user_id',
            ['user_id' => $USER->id],
            'id',
            IGNORE_MISSING);
        if ($hasprovider) {
            return true;
        } else {
            return false;
        }
    }
}
