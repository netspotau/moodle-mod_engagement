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
 * Strings
 *
 * @package    engagementindicator_forum
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['e_newposts'] = 'New posts per week';
$string['e_readposts'] = 'Read posts per week';
$string['e_replies'] = 'Replies per week';
$string['e_totalposts'] = 'Total posts per week';

$string['e_newposts_help'] = 'Numbers of new posts per week if a student is at no risk (first box) and maximum risk (second box) of disengagement. The weighting (number between 0-100) specifies how important this parameter is. The total of the weightings should add to 100.';
$string['e_readposts_help'] = 'Numbers of posts read per week if a student is at no risk (first box) and maximum risk (second box) of disengagement. The weighting (number between 0-100) specifies how important this parameter is. The total of the weightings should add to 100.';
$string['e_replies_help'] = 'Numbers of replies per week if a student is at no risk (first box) and maximum risk (second box) of disengagement. The weighting (number between 0-100) specifies how important this parameter is. The total of the weightings should add to 100.';
$string['e_totalposts_help'] = 'Numbers of posts (new posts and replies) per week if a student is at no risk (first box) and maximum risk (second box) of disengagement. The weighting (number between 0-100) specifies how important this parameter is. The total of the weightings should add to 100.';

$string['maxrisk'] = 'Max Risk';
$string['norisk'] = 'No Risk';
$string['pluginname'] = 'Forum Activity';
$string['pluginname_help'] = 'This indicator calculates risk rating based on student activity in forums.';
$string['mailer_column_header_help'] = 'Tick the checkbox(es) in this column to send messages to student(s) based on their forum activity. Their forum activity is outlined in a column to the right.';

// Other.
$string['localrisk'] = 'Local Risk';
$string['localrisk_help'] = 'The risk percentage of this alone, out
of 100.  The local risk is multiplied by the login weighting to form the
Risk Contribution.';
$string['logic'] = 'Logic';
$string['logic_help'] = 'This field provides some insight into the logic used to
arrive at the Local Risk value.';
$string['maxrisktitle'] = 'No forums read or contributed to';
$string['riskcontribution'] = 'Risk Contribution';
$string['riskcontribution_help'] = 'The amount of risk this particular
forum consideration contributes to the overall risk returned for the Forum
indicator.  This is formed by multiplying the Local Risk with the
Weighting.  The Risk Contributions of each forum item are summed together to
form the overall risk for the indicator.';
$string['weighting'] = 'Weighting';
$string['weighting_help'] = 'This figure shows the amount this item
contributes towards the overall risk for the Forum indicator.
The local risk will be multiplied by this to form the risk
contribution.';
