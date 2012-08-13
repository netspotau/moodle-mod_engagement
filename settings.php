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
 * Administration settings definitions for the analytics module.
 *
 * @package    mod_analytics
 * @copyright  2012 NetSpot Pty Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $cachetimes = array(
        604800 => get_string('numdays', '', 7),
        86400 => get_string('numdays', '', 1),
        43200 => get_string('numhours', '', 12),
        10800 => get_string('numhours', '', 3),
        7200 => get_string('numhours', '', 2),
        3600 => get_string('numhours', '', 1),
        2700 => get_string('numminutes', '', 45),
        1800 => get_string('numminutes', '', 30),
        900 => get_string('numminutes', '', 15),
        600 => get_string('numminutes', '', 10),
        540 => get_string('numminutes', '', 9),
        480 => get_string('numminutes', '', 8),
        420 => get_string('numminutes', '', 7),
        360 => get_string('numminutes', '', 6),
        300 => get_string('numminutes', '', 5),
        240 => get_string('numminutes', '', 4),
        180 => get_string('numminutes', '', 3),
        120 => get_string('numminutes', '', 2),
        60 => get_string('numminutes', '', 1),
        0 => get_string('cachingdisabled', 'analytics')
    );

    $settings->add(new admin_setting_pickroles('analytics/roles', get_string('roles'),
                        get_string('roles_desc', 'analytics'), array('student')));
    $settings->add(new admin_setting_configselect('analytics/cachettl', get_string('cachettl', 'analytics'),
                        get_string('configcachettl', 'analytics'), 300, $cachetimes));
}
