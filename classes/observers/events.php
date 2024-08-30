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
 * Event listener for dispatched event file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\observers;

defined('MOODLE_INTERNAL') || die;

use core\event\base as baseevent;

class events {
    public static function listen(baseevent $event) {
        $eventclass = $event::class;

        $events = self::get_subplugins_listeners();

        if (!in_array($eventclass, $events)) {
            return;
        }
    }

    private static function get_subplugins_listeners() {
        $installedmethods = \core_plugin_manager::instance()->get_plugins_of_type('superbadgesrequirement');

        $data = [];
        foreach ($installedmethods as $method) {
            $classname = "superbadgesrequirement_{$method->name}\\requirement";

            $data = array_merge($data, $classname::$eventstoobserve);
        }

        return $data;
    }
}