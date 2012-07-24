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
 * @package    analyticsindicator_forum
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');

class indicator_forum extends indicator {
    private $currweek;

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

        // Get forum posts for each user in userids.
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $risks = array();
        $posts = array();

        // Table: mdl_forum_posts, fields: userid, created, discussion, parent
        // Table: mdl_forum_discussions, fields: userid, course, id
        // Table: mdl_forum_read, fields: userid, discussionid, postid, firstread
        $sql = "SELECT p.userid, p.created, p.parent
                FROM {forum_posts} p
                JOIN {forum_discussions} d ON (d.id = p.discussion)
                WHERE d.course = :courseid AND p.userid $insql
                    AND p.created > :startdate AND p.created < :enddate";
        $params['courseid'] = $courseid;
        $params['startdate'] = $startdate;
        $params['enddate'] = $enddate;
        if ($postrecs = $DB->get_records_sql($sql, $params)) {
            foreach ($postrecs as $post) {
                $week = date('W', $post->created);
                if (!isset($posts[$post->userid])) {
                    $posts[$post->userid] = array();
                    $posts[$post->userid]['total'] = 0;
                    $posts[$post->userid]['weeks'] = array();
                    $posts[$post->userid]['new'] = 0;
                    $posts[$post->userid]['replies'] = 0;
                    $posts[$post->userid]['read'] = 0;
                }
                if (!isset($posts[$post->userid]['weeks'][$week])) {
                    $posts[$post->userid]['weeks'][$week] = 0;
                }
                $posts[$post->userid]['total']++;
                $posts[$post->userid]['weeks'][$week]++;

                if ($post->parent == 0) {
                    $posts[$post->userid]['new']++;
                } else {
                    $posts[$post->userid]['replies']++;
                }
            }
        }

        $sql = "SELECT *
                FROM {forum_read} fr
                JOIN {forum_discussions} d ON (d.id = fr.discussionid)
                WHERE d.course = :courseid AND fr.userid $insql
                    AND fr.firstread > :startdate AND fr.firstread < :enddate";
        $params['courseid'] = $courseid;
        $params['startdate'] = $startdate;
        $params['enddate'] = $enddate;
        if ($readposts = $DB->get_records_sql($sql, $params)) {
            foreach ($readposts as $read) {
                if (!isset($posts[$read->userid])) {
                    $posts[$read->userid]['read'] = 0;
                }
                $posts[$read->userid]['read']++;
            }
        }

        $startweek = date('W', $startdate);
        $this->currweek = date('W') - $startweek + 1;
        foreach ($userids as $userid) {
            if (!isset($posts[$userid])) {
                // Max risk.
                $risks[$userid] = 1.0 * ($this->config['w_totalposts'] +
                                         $this->config['w_replies'] +
                                         $this->config['w_newposts'] +
                                         $this->config['w_readposts']);
                continue;
            }

            $risks[$userid] = $this->calculate('totalposts', $posts[$userid]['total']);
            $risks[$userid] += $this->calculate('replies', $posts[$userid]['replies']);
            $risks[$userid] += $this->calculate('newposts', $posts[$userid]['new']);
            $risks[$userid] += $this->calculate('readposts', $posts[$userid]['read']);
        }

        return $risks;
    }

    protected function calculate($type, $num) {
        $risk = 0;
        $maxrisk = $this->config["max_$type"];
        $norisk = $this->config["no_$type"];
        $weight = $this->config["w_$type"];
        if ($num / $this->currweek <= $maxrisk) {
            $risk = $weight;
        } else if ($num / $this->currweek < $norisk) {
            $num = $num / $this->currweek;
            $num -= $maxrisk;
            $num /= $norisk - $maxrisk;
            $risk = $num * $weight;
        }
        return $risk;
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

    public static function get_defaults() {
        $settings = array();
        $settings['w_totalposts'] = 0.56;
        $settings['w_replies'] = 0.2;
        $settings['w_newposts'] = 0.12;
        $settings['w_readposts'] = 0.12;

        $settings['no_totalposts'] = 1;
        $settings['no_replies'] = 1;
        $settings['no_newposts'] = 0.5;
        $settings['no_readposts'] = 1; // 100%

        $settings['max_totalposts'] = 0;
        $settings['max_replies'] = 0;
        $settings['max_newposts'] = 0;
        $settings['max_readposts'] = 0;
        return $settings;
    }
}
