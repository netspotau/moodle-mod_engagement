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
 * @package    engagementindicator_login
 * @copyright  2012 NetSpot Pty Ltd, 2015 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['dayslate'] = 'Days Late';
$string['dayslate_help'] = 'Number of days late that the assignment was
submitted.  This takes into account any overrides in place which effect the
user\'s due date..';
$string['localrisk'] = 'Local Risk';
$string['localrisk_help'] = 'The risk percentage of this assessment alone, out
of 100.  The local risk is multiplied by the assessment weighting to form the
Risk Contribution.';
$string['logic'] = 'Logic';
$string['logic_help'] = 'This field provides some insight into the logic used to
arrive at the Local Risk value.';

$string['pluginname'] = 'Assessment Activity';
$string['pluginname_help'] = 'This indicator calculates risk rating based on late or non submission of assessments.';
$string['mailer_column_header_help'] = 'Tick the checkbox(es) in this column to send messages to student(s) based on their assessment submission activity. Their assessment activity is outlined in a column to the right.';

$string['overduegracedays'] = 'Overdue Grace Days';
$string['overduemaximumdays'] = 'Overdue Maximum Days';
$string['overduesubmittedweighting'] = 'Overdue Submitted Weighting';
$string['overduenotsubmittedweighting'] = 'Overdue Not Submitted Weighting';
$string['override'] = 'Override';
$string['override_help'] = 'Some assessment activities (ie: quiz) contain a
feature for configuring alternate due dates for individual users or groups of
users.  This field indicates that this user\'s due date was effected by an
override.';
$string['riskcontribution'] = 'Risk Contribution';
$string['riskcontribution_help'] = 'The amount of risk this particular
assessment contributes to the overall risk returned for the Assessment
indicator.  This is formed by multiplying the Local Risk with the assessment
Weighting.  The Risk Contributions of each assessment are summed together to
form the overall risk for the indicator.';
$string['status'] = 'Status';
$string['status_help'] = 'Status indicates whether the user has submitted this
assessment or not.';
$string['weighting'] = 'Weighting';
$string['weighting_help'] = 'This figure shows the max grade of this assessment
as a percentage of total max grade for all assessments tracked by the Assessment
Indicator.  The local_weighting will be multiplied by this to form the risk
contribution.';

// thresholds form
$string['overduegracedays_help'] = 'The number of grace days a student has before a late or non-submitted assessment starts contributing to their risk rating for this indicator. Enter a whole number.';
$string['overduemaximumdays_help'] = 'The number of days after which a student receives the full weighting (specified below) from this indicator. Enter a whole number.';
$string['overduesubmittedweighting_help'] = 'The maximum weighting applied for each late submission. The weighting of late submissions on the risk rating for this indicator increases over time until reaching this maximum weighting when the overdue maximum days is reached. Enter a whole number between 0-100.';
$string['overduenotsubmittedweighting_help'] = 'The maximum weighting applied for each non-submission. The weighting of non-submissions on the risk rating for this indicator increases over time until reaching this maximum weighting when the overdue maximum days is reached.  Enter a whole number between 0-100.';

