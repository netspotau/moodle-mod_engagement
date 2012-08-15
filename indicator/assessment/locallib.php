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
 * This file defines functions used for the assessment indicator
 *
 * @package    engagementindicator_assessment
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
function engagementindicator_assessment_process_edit_form($data) {
    $configdata = array();
    $configdata['assessment_overduegracedays'] = $data->assessment_overduegracedays;
    $configdata['assessment_overduemaximumdays'] = $data->assessment_overduemaximumdays;
    $configdata['assessment_overduesubmittedweighting'] = $data->assessment_overduesubmittedweighting;
    $configdata['assessment_overduenotsubmittedweighting'] = $data->assessment_overduenotsubmittedweighting;

    return $configdata;
}
