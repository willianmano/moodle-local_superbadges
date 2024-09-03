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
 * This file contains the superbadges element coursecompletion's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace superbadgesrequirement_coursecompletion;

defined('MOODLE_INTERNAL') || die();

/**
 * The superbadges element coursecompletion's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirement extends \local_superbadges\requirement {
    public static $eventstoobserve = [
        [
            'eventname' => 'core\event\course_completed',
            'callback' => '\superbadgesrequirement_coursecompletion\observers\course::completed'
        ]
    ];

    public function user_achieved_requirement(int $userid, \stdClass $requirement): bool {
        if ($this->is_course_complete($userid, $requirement->target)) {
            return true;
        }

        return false;
    }

    public function is_course_complete(int $userid, int $courseid): bool {
        global $DB;

        $completion = $DB->get_record('course_completions', ['course' => $courseid, 'userid' => $userid]);

        if ($completion) {
            return (bool) $completion->timecompleted;
        }

        return false;
    }

    public function get_user_requirement_progress(int $userid, \stdClass $requirement): int {
        $iscomplete = $this->is_course_complete($userid, $requirement->target);

        if ($iscomplete) {
            return 100;
        }

        return 0;
    }

    public function get_user_requirement_progress_data(int $userid, \stdClass $requirement): array {
        $pluginname = get_string('pluginname', 'superbadgesrequirement_coursecompletion');

        $progress = $this->get_user_requirement_progress($userid, $requirement);

        $coursename = $this->get_course_name($requirement->target);

        $progresdesc = get_string('requirementprogresdesc', 'superbadgesrequirement_coursecompletion', $coursename);

        return [
            'pluginname' => $pluginname,
            'progress' => $progress,
            'progresdesc' => $progresdesc,
        ];
    }

    private function get_course_name(int $courseid): string {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname', MUST_EXIST);

        return format_string($course->fullname);
    }
}
