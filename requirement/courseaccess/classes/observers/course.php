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
 * Event listener file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace superbadgesrequirement_courseaccess\observers;

/**
 * Event listener class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {
    public static function viewed(\core\event\course_viewed $event) {
        global $DB;

        $requirements = $DB->get_records('local_superbadges_requirements', [
            'method' => 'courseaccess', 'target' => $event->courseid
        ]);

        if (!$requirements) {
            return;
        }

        $issuer = new \local_superbadges\util\issuer();
        foreach ($requirements as $requirement) {
            $issuer->deliver($requirement->badgeid, $event->userid);
        }
    }
}
