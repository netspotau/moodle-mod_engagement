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
 * This file defines a class with forum indicator logic
 *
 * @package    engagementindicator_forum
 * @author     Adam Olley <adam.olley@netspot.com.au>
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');
require_once(dirname(__FILE__).'/indicator.class.php');

class engagementindicator_forum_thresholds_form {

    /**
     * Define the elements to be displayed in the form
     *
     * @param $mform
     * @access public
     * @return void
     */
    public function definition_inner(&$mform) {

        $strmaxrisk = get_string('maxrisk', 'engagementindicator_forum');
        $strnorisk = get_string('norisk', 'engagementindicator_forum');

        $defaults = indicator_forum::get_defaults();

        $elements = array('newposts', 'readposts', 'replies', 'totalposts');
        foreach ($elements as $element) {
            $grouparray = array();
            $grouparray[] =& $mform->createElement('static', '', '', "&nbsp;$strnorisk");
            $grouparray[] =& $mform->createElement('text', "forum_no_$element", '', array('size' => 5));
            $mform->setDefault("forum_no_$element", $defaults["no_$element"]);
            $mform->setType("forum_no_$element", PARAM_FLOAT);

            $grouparray[] =& $mform->createElement('static', '', '', $strmaxrisk);
            $grouparray[] =& $mform->createElement('text', "forum_max_$element", '', array('size' => 5));
            $mform->setDefault("forum_max_$element", $defaults["max_$element"]);
            $mform->setType("forum_max_$element", PARAM_FLOAT);

            $grouparray[] =& $mform->createElement('static', '', '', get_string('weighting', 'report_engagement'));
            $grouparray[] =& $mform->createElement('text', "forum_w_$element", '', array('size' => 3));
            $mform->setDefault("forum_w_$element", $defaults["w_$element"]*100);
            $mform->setType("forum_w_$element", PARAM_FLOAT);

            $grouparray[] =& $mform->createElement('static', '', '', '%');
            $mform->addGroup($grouparray, "group_forum_$element", get_string("e_$element", "engagementindicator_forum"), '&nbsp;',
                false);
        }
    }
}
