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

defined('MOODLE_INTERNAL') || die;

/**
 * indicator base class
 *
 * @copyright 2012 NetSpot Pty Ltd
 * @author Adam Olley <adam.olley@netspot.com.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class indicator {
    protected $config = null;
    protected $courseid;
    protected $instance;

    public function __construct($courseid, array $_config = array()) {
        global $DB;
        $this->courseid = $courseid;

        if ($record = $DB->get_record('report_analytics', array('course' => $courseid, 'indicator' => self::get_name()))) {
            $this->instance = $record;
        } else {
            $record = new stdClass();
            $record->course = $courseid;
            $record->indicator = self::get_name();
            $record->weight = 0;
            $record->configdata = null;
            $DB->insert_record('report_analytics', $record);
            $this->instance = $record;
        }

        if (!empty($_config)) {
            $this->config = $_config;
        } else {
            $this->load_config();
        }
    }

    abstract public function get_risk($userid, $courseid, $startdate = null, $enddate = null);
    abstract public function get_course_risks($courseid, $startdate = null, $enddate = null);

    protected static function calculate_risk($actual, $expected, $weighting = 1) {
        $risk = 0;
        if ($actual < $expected) {
            $risk += (($expected - $actual) / $expected) * $weighting;
        }
        return $risk;
    }

    public function get_name() {
        $class = get_class($this);
        $indicatorname = substr($class, strlen('indicator_'));
        return $indicatorname;
    }

    /**
     * load_config  loads the configdata for this indicator in this course
     *
     * @access protected
     * @return void
     */
    protected function load_config() {
        if ($this->config == null && !is_null($this->instance->configdata)) {
            $this->config = unserialize(base64_decode($this->instance->configdata));
            foreach ($this->config as $key => $value) {
                $newkey = preg_replace('/^'.self::get_name().'_/', '', $key);
                if ($newkey != $key) {
                    $this->config[$newkey] = $value;
                    unset($this->config[$key]);
                }
            }
        }
    }

    public function save_config() {
        global $DB;
        if ($this->config != null) {
            $indicatorname = self::get_name();
            $configdata = base64_encode(serialize($this->config));
            $this->instance->configdata = $configdata;
            $DB->update_record('report_analytics', $this->instance);
        }
    }
}
