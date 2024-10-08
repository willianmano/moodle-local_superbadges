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
 * Cleanup utility file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\util;

/**
 * Cleanup utility class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup {
    public function delete_course_badges($courseid) {
        global $DB;

        $badges = $DB->get_records('local_superbadges_badges', ['courseid' => $courseid]);

        if (!$badges) {
            return true;
        }

        foreach ($badges as $badge) {
            $DB->delete_records('local_superbadges_requirements', ['badgeid' => $badge->id]);
        }

        $DB->delete_records('local_superbadges_badges', ['courseid' => $courseid]);

        return true;
    }
}
