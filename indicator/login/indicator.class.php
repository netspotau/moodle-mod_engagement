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
 * @package    analyticsindicator_login
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');

class indicator_login extends indicator {
    public function get_risk($userid, $courseid, $startdate = null, $enddate = null) {
        return $this->get_risk_for_users($userid, $courseid, $startdate, $enddate);
    }

    public function get_course_risks($courseid, $startdate = null, $enddate = null) {
        $users = array_keys(get_enrolled_users(context_course::instance($courseid)));
        return $this->get_risk_for_users($users, $courseid, $startdate, $enddate);
    }

    /**
     * get_risk_for_users_users
     *
     * @param mixed $userid     if userid is null, return risks for all users
     * @param mixed $courseid
     * @param mixed $startdate
     * @param mixed $enddate
     * @access protected
     * @return array            array of risk values, keyed on userid
     */
    protected function get_risk_for_users($userids, $courseid, $startdate, $enddate) {
        global $DB;

        if (empty($userids)) {
            //$userids = get_enrolled_users
            return array();
        } else if (is_int($userids)) {
            $userids = array($userids);
        }

        if ($startdate == null) {
            $startdate = $DB->get_field('course', 'startdate', array('id' => $courseid));
        }
        if ($enddate == null) {
            $enddate = time();
        }

        $risks = array();
        $sessions = array();

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;
        $params['startdate'] = $startdate;
        $params['enddate'] = $enddate;
        $sql = "SELECT id, userid, time
                FROM {log}
                WHERE course = :courseid AND userid $insql AND time >= :startdate AND time <= :enddate
                ORDER BY time ASC";
        if ($logs = $DB->get_records_sql($sql, $params)) {
            //need to calculate sessions, sessions are defined by time between consequtive logs not exceeding setting
            foreach ($logs as $log) {
                $increment = false;
                $week = date('W', $log->time);
                if (!isset($sessions[$log->userid])) {
                    $sessions[$log->userid] = array('total' => 0, 'weeks' => array(), 'pastweek' => 0, 'lengths' => array(),
                                                    'start' => 0);
                }
                if (!isset($sessions[$log->userid]['lastlogin'])) {
                    $increment = true;
                } else {
                    if (($log->time - $this->config['session_length']) > $sessions[$log->userid]['lastlogin']) {
                        $increment = true;
                    }
                }

                if ($increment) {
                    if ($sessions[$log->userid]['start'] > 0) {
                        $sessions[$log->userid]['lengths'][] =
                            $sessions[$log->userid]['lastlogin'] - $sessions[$log->userid]['start'];
                    }
                    $sessions[$log->userid]['total']++;
                    $sessions[$log->userid]['start'] = $log->time;
                    if (!isset($sessions[$log->userid]['weeks'][$week])) {
                        $sessions[$log->userid]['weeks'][$week] = 0;
                    }
                    $sessions[$log->userid]['weeks'][$week]++;

                    if ($log->time > ($enddate - 7*24*60*60)) { //session in past week
                        $sessions[$log->userid]['pastweek']++;
                    }
                }
                $sessions[$log->userid]['lastlogin'] = $log->time;
            }
        }

        foreach ($userids as $userid) {
            $risk = 0;

            if (!isset($sessions[$userid])) {
                $risks[$userid] = 1.0 * ($this->config['w_loginspastweek'] +
                                         $this->config['w_avgsessionlength'] +
                                         $this->config['w_loginsperweek'] +
                                         $this->config['w_timesincelast']);
                continue;
            }

            //logins past week
            $risk += self::calculate_risk($sessions[$userid]['pastweek'], $this->config['e_loginspastweek'],
                                          $this->config['w_loginspastweek']);

            //average session length
            if (($count = count($sessions[$userid]['lengths'])) > 0) {
                $average = array_sum($sessions[$userid]['lengths']) / $count;
            } else {
                $average = 0;
            }
            $risk += self::calculate_risk($average, $this->config['e_avgsessionlength'],
                                          $this->config['w_avgsessionlength']);

            //logins per week
            if (($count = count($sessions[$userid]['weeks'])) > 0) {
                $average = array_sum($sessions[$userid]['weeks']) / $count;
            } else {
                $average = 0;
            }
            $risk += self::calculate_risk($average, $this->config['e_loginsperweek'], $this->config['w_loginsperweek']);

            //time since last login
            $timediff = time() - $sessions[$userid]['lastlogin'];
            $risk += self::calculate_risk($this->config['e_timesincelast'], $timediff,
                                          $this->config['w_timesincelast']);

            $risks[$userid] = $risk;
        }

        return $risks;
    }

    protected function load_config() {
        parent::load_config();
        $defaults = $this->get_defaults();
        foreach ($defaults as $setting => $value) {
            if (!isset($this->config[$setting])) {
                $this->config[$setting] = $value;
            } else if (substr($setting, 0, 2) == 'w_') {
                $this->config[$setting] = $this->config[$setting] / 100;
            }
        }
    }

    public function get_defaults() {
        $settings = array();
        $settings['e_loginspastweek'] = 2;
        $settings['w_loginspastweek'] = 0.2;

        $settings['e_loginsperweek'] = 2;
        $settings['w_loginsperweek'] = 0.3;

        $settings['e_avgsessionlength'] = 10*60;
        $settings['w_avgsessionlength'] = 0.1;

        $settings['e_timesincelast'] = 7*24*60*60; //1 week
        $settings['w_timesincelast'] = 0.4;

        $settings['session_length'] = 60*60; //1 hour
        return $settings;
    }
}
