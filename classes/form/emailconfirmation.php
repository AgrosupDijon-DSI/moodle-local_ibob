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
 * User confirmation form.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ibob\form;

defined('MOODLE_INTERNAL') || die('');
global $CFG;

require_once($CFG->libdir.'/formslib.php');


/**
 * Email confirmation form.
 *
 * @package    local_ibob
 * @copyright  2023, frederic.grebot <frederic.grebot@agrosupdijon.fr>, L'Institut Agro Dijon, DSI, CNERTA-WEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emailconfirmation extends \moodleform {

    /**
     * Defines forms elements
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->addElement('html', '<div class="formheader"><h2>'.get_string('emailconfirmationdescription', 'local_ibob')
            .'</h2></div>');
        $mform->addElement('text', 'emailconfirmationcode', get_string('emailconfirmationcode', 'local_ibob'),
            ['size' => '6']);
        $mform->setType('emailconfirmationcode', PARAM_INT);
        $mform->addRule('emailconfirmationcode', get_string('invalidcode', 'local_ibob'), 'numeric', null, 'client');
        $mform->addRule('emailconfirmationcode', get_string('invalidcode', 'local_ibob'), 'minlength', 4, 'client');
        $mform->addRule('emailconfirmationcode', get_string('invalidcode', 'local_ibob'), 'maxlength', 4, 'client');
        $this->add_action_buttons();

        /**
         * Perform minimal validation on the settings form.
         *
         * @param array $data
         * @param array $files
         * @return array
         */
        function validation($data, $files) {
            return [];
        }
    }
}
