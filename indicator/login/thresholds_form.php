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
 * This file defines a class with login indicator logic
 *
 * @package    engagementindicator_login
 * @author     Adam Olley <adam.olley@netspot.com.au>
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');
require_once(dirname(__FILE__).'/indicator.class.php');

class engagementindicator_login_thresholds_form {

    /**
     * Define the elements to be displayed in the form
     *
     * @param $mform
     * @access public
     * @return void
     */
    public function definition_inner(&$mform) {
        $defaults = indicator_login::get_defaults();
        $elements = array('loginspastweek', 'loginsperweek', 'avgsessionlength', 'timesincelast');
        foreach ($elements as $element) {
            $grouparray = array();
            $grouparray[] =& $mform->createElement('text', "login_e_$element", '', array('size' => 5));
            $grouparray[] =& $mform->createElement('static', '', '', get_string('weighting', 'report_engagement'));
            $grouparray[] =& $mform->createElement('text', "login_w_$element", '', array('size' => 3));
            $grouparray[] =& $mform->createElement('static', '', '', '%');
            $mform->addGroup($grouparray, "group_loginspastweek", get_string("e$element", "engagementindicator_login"), '&nbsp;',
                false);
            $mform->setDefault("login_e_$element", $defaults["e_$element"]);
            $mform->setDefault("login_w_$element", $defaults["w_$element"]*100);
            $mform->setType("login_e_$element", PARAM_FLOAT);
            $mform->setType("login_w_$element", PARAM_FLOAT);
        }

        $mform->addElement('text', 'login_session_length', get_string('sessionlength', 'engagementindicator_login'),
            array('size' => 5));
        $mform->setDefault("login_session_length", $defaults["session_length"]);
        $mform->setType('login_session_length', PARAM_INT);
    }
}
