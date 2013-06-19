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
 * @author Ashley Holman <ashley.holman@netspot.com.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class indicator {
    protected $config = null;
    protected $context;
    protected $courseid;
    protected $instance;
    protected $rawdata;

    public function __construct($courseid, array $_config = array()) {
        global $DB;
        $this->courseid = $courseid;
        $this->context = context_course::instance($courseid);

        if ($record = $DB->get_record('report_engagement', array('course' => $courseid, 'indicator' => self::get_name()))) {
            $this->instance = $record;
        } else {
            $record = new stdClass();
            $record->course = $courseid;
            $record->indicator = self::get_name();
            $record->weight = 0;
            $record->configdata = null;
            $DB->insert_record('report_engagement', $record);
            $this->instance = $record;
        }

        if (!empty($_config)) {
            $this->config = $_config;
        } else {
            $this->load_config();
        }
    }

    /**
     * get_risk - provide risk data for a given user for this indicator
     *
     * @param int $userid       userid of the user
     * @param int $courseid     courseid of the course
     * @param int $startdate    start of range in seconds
     * @param int $enddate      end of range in seconds
     * @access public
     * @return array            list of risk values, key'd on userid
     */
    public function get_risk($userid, $courseid, $startdate = null, $enddate = null) {
        return self::get_risk_for_users($userid, $startdate, $enddate);
    }

    /**
     * get_course_risks
     *
     * @param int $courseid     courseid of the course
     * @param int $startdate    start of range in seconds
     * @param int $enddate      end of range in seconds
     * @access public
     * @return array            list of risk values, key'd on userid
     */
    public function get_course_risks($startdate = null, $enddate = null) {
        global $DB;

        // TODO: Move the user list building somewhere static, otherwise its done for every indicator.
        $pluginconfig = get_config('engagement');
        if (!isset($pluginconfig->roles)) {
            $roleids = array_keys(get_archetype_roles('student'));
        } else {
            $roles = $pluginconfig->roles;
            $roleids = explode(',', $roles);
        }

        list($esql, $eparams) = get_enrolled_sql($this->context, '', 0, true); // Only active users.
        list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roles');
        $sql = "SELECT ".$DB->sql_concat_join("'_'", array('je.id', 'ra.id')).", je.id as jeid
                FROM ($esql) je
                JOIN {role_assignments} ra ON (ra.userid = je.id)
                WHERE ra.contextid = :contextid AND ra.roleid $rsql";
        $params = array_merge($eparams, $rparams);
        $params['contextid'] = $this->context->id;

        $result = $DB->get_records_sql($sql, $params);
        $users = array();
        foreach ($result as $key => $row) {
            if (!isset($users[$row->jeid])) {
                $users[$row->jeid] = true;
            }
        }
        $users = array_keys($users);

        return self::get_risk_for_users($users, $startdate, $enddate);
    }

    private function get_risk_for_users($userids, $startdate, $enddate) {
        global $DB;

        if (empty($userids)) {
            return array();
        } else if (is_int($userids)) {
            $userids = array($userids);
        }

        if ($startdate == null) {
            $this->startdate = $DB->get_field('course', 'startdate', array('id' => $this->courseid));
        }
        if ($enddate == null) {
            $this->enddate = time();
        }

        $this->cachettl = get_config('engagement', 'cachettl');
        // If caching is enabled and cache data exists, use that, otherwise call function to fetch live.
        if ($this->cachettl && $rawdata = $this->get_cache()) { // TODO: Try to fetch from cache here.
            $this->rawdata = $rawdata;
        } else {
            $this->rawdata = $this->get_rawdata($this->startdate, $this->enddate);
            if ($this->cachettl) {
                $this->set_cache();
            }
        }

        return $this->calculate_risks($userids);
    }

    final private function get_cache() {
        global $DB;

        $params = array($this->get_name(), $this->courseid, $this->startdate, time() - $this->cachettl);
        $rawdata = $DB->get_field_sql('
                          SELECT      rawdata
                          FROM        {engagement_cache}
                          WHERE       indicator = ?
                            AND       courseid = ?
                            AND       timestart = ?
                            AND       timemodified > ?
                          ORDER BY    timemodified DESC
                          LIMIT 1', $params);
        if ($rawdata) {
            return unserialize(base64_decode($rawdata));
        }
    }

    final private function set_cache() {
        global $DB;

        $cacheobj = new stdClass();
        $cacheobj->indicator = $this->get_name();
        $cacheobj->courseid = $this->courseid;
        $cacheobj->timestart = $this->startdate;
        $cacheobj->timeend = $this->enddate;
        $cacheobj->timemodified = time();
        $cacheobj->rawdata = base64_encode(serialize($this->rawdata));
        return $DB->insert_record('engagement_cache', $cacheobj);
    }

    /**
     * get_rawdata - fetch all relevant data
     *
     * @abstract
     * @access protected
     * @return void
     */
    abstract protected function get_rawdata($startdate, $enddate);

    /**
     * calculate_risks - apply the current thresholds/settings to the rawdata
     *
     * @param mixed $userids
     * @abstract
     * @access protected
     * @return array    return array of objects keyed on userid
     */
    abstract protected function calculate_risks(array $userids);

    /**
     * get_name - get the name of the indicator (i.e. indicator_login => login)
     *
     * @access public
     * @return string   the indicators name
     */
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
        if ($this->config == null && (isset($this->instance->configdata) && !is_null($this->instance->configdata))) {
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

    /**
     * save_config - save the config stored in $this->config into the db
     *
     * @access public
     * @return void
     */
    public function save_config() {
        global $DB;
        if ($this->config != null) {
            $indicatorname = self::get_name();
            $configdata = base64_encode(serialize($this->config));
            $this->instance->configdata = $configdata;
            $DB->update_record('report_engagement', $this->instance);
        }
    }
}
