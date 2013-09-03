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
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');

class indicator_forum extends indicator {
    private $currweek;

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
    protected function get_rawdata($startdate, $enddate) {
        global $DB;

        $posts = array();

        // Table: mdl_forum_posts, fields: userid, created, discussion, parent.
        // Table: mdl_forum_discussions, fields: userid, course, id.
        // Table: mdl_forum_read, fields: userid, discussionid, postid, firstread.
        $sql = "SELECT p.id, p.userid, p.created, p.parent
                FROM {forum_posts} p
                JOIN {forum_discussions} d ON (d.id = p.discussion)
                WHERE d.course = :courseid
                    AND p.created > :startdate AND p.created < :enddate";
        $params['courseid'] = $this->courseid;
        $params['startdate'] = $startdate;
        $params['enddate'] = $enddate;
        if ($postrecs = $DB->get_recordset_sql($sql, $params)) {
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
            $postrecs->close();
        }

        $sql = "SELECT *
                FROM {forum_read} fr
                JOIN {forum} f ON (f.id = fr.forumid)
                WHERE f.course = :courseid
                    AND fr.firstread > :startdate AND fr.firstread < :enddate";
        $params['courseid'] = $this->courseid;
        $params['startdate'] = $startdate;
        $params['enddate'] = $enddate;
        if ($readposts = $DB->get_recordset_sql($sql, $params)) {
            foreach ($readposts as $read) {
                if (!isset($posts[$read->userid])) {
                    $posts[$read->userid]['read'] = 0;
                }
                $posts[$read->userid]['read']++;
            }
            $readposts->close();
        }

        $rawdata = new stdClass();
        $rawdata->posts = $posts;
        return $rawdata;
    }

    protected function calculate_risks(array $userids) {
        $risks = array();

        $strtotalposts = get_string('e_totalposts', 'engagementindicator_forum');
        $strreplies = get_string('e_replies', 'engagementindicator_forum');
        $strreadposts = get_string('e_readposts', 'engagementindicator_forum');
        $strnewposts = get_string('e_newposts', 'engagementindicator_forum');
        $strmaxrisktitle = get_string('maxrisktitle', 'engagementindicator_forum');

        $startweek = date('W', $this->startdate);
        $this->currweek = date('W') - $startweek + 1;
        foreach ($userids as $userid) {
            $risk = 0;
            $reasons = array();
            if (!isset($this->rawdata->posts[$userid])) {
                // Max risk.
                $info = new stdClass();
                $info->risk = 1.0 * ($this->config['w_totalposts'] +
                                               $this->config['w_replies'] +
                                               $this->config['w_newposts'] +
                                               $this->config['w_readposts']);
                $reason = new stdClass();
                $reason->weighting = '100%';
                $reason->localrisk = '100%';
                $reason->logic = "This user has never made a post or had tracked read posts in the ".
                                 "course and so is at the maximum 100% risk.";
                $reason->riskcontribution = '100%';
                $reason->title = $strmaxrisktitle;
                $info->info = array($reason);
                $risks[$userid] = $info;
                continue;
            }

            $local_risk = $this->calculate('totalposts', $this->rawdata->posts[$userid]['total']);
            $risk_contribution = $local_risk * $this->config['w_totalposts'];
            $reason = new stdClass();
            $reason->weighting = intval($this->config['w_totalposts']*100).'%';
            $reason->localrisk = intval($local_risk*100).'%';
            $reason->logic = "0% risk for more than {$this->config['no_totalposts']} posts a week. ".
                             "100% for {$this->config['max_totalposts']} posts a week.";
            $reason->riskcontribution = intval($risk_contribution*100).'%';
            $reason->title = $strtotalposts;
            $reasons[] = $reason;
            $risk += $risk_contribution;

            $local_risk += $this->calculate('replies', $this->rawdata->posts[$userid]['replies']);
            $risk_contribution = $local_risk * $this->config['w_replies'];
            $reason = new stdClass();
            $reason->weighting = intval($this->config['w_replies']*100).'%';
            $reason->localrisk = intval($local_risk*100).'%';
            $reason->logic = "0% risk for more than {$this->config['no_replies']} replies a week. ".
                             "100% for {$this->config['max_replies']} replies a week.";
            $reason->riskcontribution = intval($risk_contribution*100).'%';
            $reason->title = $strreplies;
            $reasons[] = $reason;
            $risk += $risk_contribution;

            $local_risk += $this->calculate('newposts', $this->rawdata->posts[$userid]['new']);
            $risk_contribution = $local_risk * $this->config['w_newposts'];
            $reason = new stdClass();
            $reason->weighting = intval($this->config['w_newposts']*100).'%';
            $reason->localrisk = intval($local_risk*100).'%';
            $reason->logic = "0% risk for more than {$this->config['no_newposts']} replies a week. ".
                             "100% for {$this->config['max_newposts']} new posts a week.";
            $reason->riskcontribution = intval($risk_contribution*100).'%';
            $reason->title = $strnewposts;
            $reasons[] = $reason;
            $risk += $risk_contribution;

            $local_risk += $this->calculate('readposts', $this->rawdata->posts[$userid]['read']);
            $risk_contribution = $local_risk * $this->config['w_readposts'];
            $reason = new stdClass();
            $reason->weighting = intval($this->config['w_readposts']*100).'%';
            $reason->localrisk = intval($local_risk*100).'%';
            $reason->logic = "0% risk for more than {$this->config['no_readposts']} read posts a week. ".
                             "100% for {$this->config['max_readposts']} read posts a week.";
            $reason->riskcontribution = intval($risk_contribution*100).'%';
            $reason->title = $strreadposts;
            $reasons[] = $reason;
            $risk += $risk_contribution;

            $info = new stdClass();
            $info->risk = $risk;
            $info->info = $reasons;
            $risks[$userid] = $info;
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
        $settings['no_readposts'] = 1; // 100%.

        $settings['max_totalposts'] = 0;
        $settings['max_replies'] = 0;
        $settings['max_newposts'] = 0;
        $settings['max_readposts'] = 0;
        return $settings;
    }
}
