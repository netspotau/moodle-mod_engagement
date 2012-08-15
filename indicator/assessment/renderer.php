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
 * Output rendering of engagement report
 *
 * @package    mod_engagement
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class engagementindicator_assessment_renderer extends engagementindicator_renderer {
    public function user_report($data) {
        $html = '';
        foreach ($data->info as $i) {
            $html .= html_writer::start_tag('strong');
            $html .= html_writer::tag('span', $i->assessment);
            $html .= html_writer::end_tag('strong');
            $html .= html_writer::empty_tag('br');
            $html .= $this->output->help_icon('status', 'engagementindicator_assessment');
            $html .= html_writer::tag('span', 'Status: ' . $i->submitted);
            if (isset($i->override)) {
                $html .= html_writer::empty_tag('br');
                $html .= $this->output->help_icon('override', 'engagementindicator_assessment');
                $html .= html_writer::tag('span', 'Override: A ' . $i->override . ' override on due date is in place.');
            }
            $html .= html_writer::empty_tag('br');
            $html .= $this->output->help_icon('weighting', 'engagementindicator_assessment');
            $html .= html_writer::tag('span', 'Weighting: ' . $i->assessmentweighting);
            if (isset($i->dayslate)) {
                $html .= html_writer::empty_tag('br');
                $html .= $this->output->help_icon('dayslate', 'engagementindicator_assessment');
                $html .= html_writer::tag('span', 'Days late: ' .  $i->dayslate);
            }
            $html .= html_writer::empty_tag('br');
            $html .= $this->output->help_icon('localrisk', 'engagementindicator_assessment');
            $html .= html_writer::tag('span', 'Local risk: ' . $i->localrisk);
            $html .= html_writer::empty_tag('br');
            $html .= $this->output->help_icon('riskcontribution', 'engagementindicator_assessment');
            $html .= html_writer::tag('span', 'Risk Contribution: ' .  $i->riskcontribution);
            $html .= html_writer::empty_tag('br');
            $html .= $this->output->help_icon('logic', 'engagementindicator_assessment');
            $html .= html_writer::tag('span', 'Logic: ' .  $i->logic);
            $html .= html_writer::empty_tag('br');
            $html .= html_writer::empty_tag('br');
        }
        return $html;
    }
}
