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
 * This file defines functions used for the login indicator
 *
 * @package    engagementindicator_login
 * @author     Adam Olley <adam.olley@netspot.com.au>
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Process the edit form data, returning an array of config settings to store
 *
 * @param array $data
 * @return array
 */
function engagementindicator_login_process_edit_form($data) {
    $configdata = array();
    $elements = array('loginspastweek', 'loginsperweek', 'avgsessionlength', 'timesincelast');
    foreach ($elements as $element) {
        if (isset($data->{"login_e_$element"})) {
            $configdata["login_e_$element"] = $data->{"login_e_$element"};
        }
        if (isset($data->{"login_w_$element"})) {
            $configdata["login_w_$element"] = $data->{"login_w_$element"};
        }
    }
    if (isset($data->{"login_session_length"})) {
        $configdata["login_session_length"] = $data->{"login_session_length"};
    }

    return $configdata;
}
