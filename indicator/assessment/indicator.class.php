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
 * This file defines a class with assessment indicator logic
 *
 * @package    analyticsindicator_assessment
 * @copyright  2012 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');

class indicator_assessment extends indicator {
    public function __construct() {
        $this->calculator = new AssessmentRiskCalculator;
    }

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

        $settings = $this->get_settings();

        $risks = array();

        $sumgrades = 0;
        $activities = array();
        $grade_items = $DB->get_records_sql("
            SELECT      id, itemtype, itemmodule, iteminstance, grademax
            FROM        {grade_items}
            WHERE       courseid=?
        ", array($courseid));
        foreach ($grade_items as $gi) {
          if (in_array($gi->itemtype, array('mod', 'manual'))) {
            $sumgrades += $gi->grademax;
            if ($gi->itemtype == 'mod') {
              $activities[$gi->itemmodule][] = $gi;
            }
          }
        }

        foreach ($activities as $mod => $items) {
          switch ($mod) {
              case 'assignment':
                $this->add_assignments($items);
                break;
              case 'quiz':
                $this->add_quizzes($items);
                break;
          }
        }

        return $this->calculator->getRisks($userids, $sumgrades, $settings);
    }

    private function add_assignments($grade_items) {
      global $DB;

      $submissions = array();
      foreach ($grade_items as $gi) {
        $assignment_ids[$gi->iteminstance] = $gi;
        $submissions[$gi->iteminstance] = array();
      }
      list($insql, $params) = $DB->get_in_or_equal(array_keys($assignment_ids));
      $assignments = $DB->get_records_sql("
          SELECT        id, timedue
          FROM          {assignment}
          WHERE         id $insql
            AND         assignmenttype != 'offline'
      ", $params);
      # collect up the submissions
      $subs = $DB->get_records_sql("
          SELECT        sub.id, sub.assignment, sub.userid, sub.timemodified
          FROM          {assignment_submissions} sub, {assignment} a
          WHERE         a.id = sub.assignment
            AND         assignment $insql
            AND         (    (assignmenttype = 'upload' AND data2 = 'submitted')
                          OR (assignmenttype IN ('uploadsingle', 'online')))
      ", $params);
      foreach ($subs as $s) {
          $submissions[$s->assignment][$s->userid] = $s->timemodified;
      }
      # finally add the assessment details into the calculator
      foreach ($assignments as $a) {
        $grademax = $assignment_ids[$a->id]->grademax;
        $this->calculator->addAssessment($grademax, $a->timedue, $submissions[$a->id]);
      }
    }

    private function add_quizzes($grade_items) {
      global $DB;

      $submissions = array();
      foreach ($grade_items as $gi) {
          $quiz_ids[$gi->iteminstance] = $gi;
          $submissions[$gi->iteminstance] = array();
      }
      list($insql, $params) = $DB->get_in_or_equal(array_keys($quiz_ids));
      $quizzes = $DB->get_records_sql("
          SELECT      id, timeclose
          FROM        {quiz}
          WHERE       id $insql
      ", $params);
      # collect up the attempts
      $attempts = $DB->get_records_sql("
          SELECT        id, quiz, userid, timefinish
          FROM          {quiz_attempts}
          WHERE         quiz $insql
            AND         timefinish > 0
            AND         preview = 0
      ", $params);
      foreach ($attempts as $a) {
        $submissions[$a->quiz][$a->userid] = $a->timefinish;
      }
      foreach ($quizzes as $q) {
        $grademax = $quiz_ids[$q->id]->grademax;
        $this->calculator->addAssessment($grademax, $q->timeclose, $submissions[$q->id]);
      }
    }

    public function get_settings() {
        //TODO: CONFIG THESE!
        $settings = array();
        $settings['overduegracedays'] = 0;
        $settings['overduemaximumdays'] = 14;

        $settings['overduesubmittedweighting'] = 0.5;
        $settings['overduenotsubmittedweighting'] = 1.0;

        return $settings;
    }
}

class AssessmentRiskCalculator {
    // generic list of assessed activities
    private $assessments = array();

    // <submissions> = array( <uid> => tstamp, ...);
    public function addAssessment($maxscore, $duedate, $submissions) {
      $a = new stdClass;
      $a->maxscore = $maxscore;
      $a->due = $duedate;
      $a->submissions = $submissions;
      $this->assessments[] = $a;
    }

    public function getRisks($uids, $totalAssessmentValue, $settings) {
      $risks = array();

      foreach ($uids as $uid) {
          $risk = 0;
          foreach ($this->assessments as $a) {
              $submittime = isset($a->submissions[$uid]) ? $a->submissions[$uid] : PHP_INT_MAX;
              $numDaysLate = ($submittime - $a->due) / 86400;
              $daysLateWeighting = ($numDaysLate - $settings['overduegracedays']) /
                                   ($settings['overduemaximumdays'] - $settings['overduegracedays']);
              $daysLateWeighting = max(0, min(1, $daysLateWeighting));
              $assessmentValueWeighting = $a->maxscore / $totalAssessmentValue;
              if (isset($a->submissions[$uid])) {
                // assessment was submitted
                $risk += $daysLateWeighting * $assessmentValueWeighting * $settings['overduesubmittedweighting'];
              } else {
                $risk += $daysLateWeighting * $assessmentValueWeighting * $settings['overduenotsubmittedweighting'];
              }
          }
          $risks[$uid] = $risk;
      }

      return $risks;
    }
}
