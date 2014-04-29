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
 * Subplugin info class.
 *
 * @package   mod_engagement
 * @copyright 2014 NetSpot Pty Ltd
 * @author    Adam Olley <adam.olley@netspot.com.au>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_engagement\plugininfo;

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class engagementindicator extends base {

    public function is_enabled() {
        $enabled = self::get_enabled_engagementindicators();

        return isset($enabled[$this->name]) && $enabled[$this->name]->visible;
    }

    protected function get_enabled_engagementindicators($disablecache = false) {
        global $DB;
        static $indicators = null;

        if (is_null($indicators) or $disablecache) {
            $indicators = $DB->get_records('engagement_indicator', null, 'name', 'name,visible');
        }

        return $indicators;
    }

}
