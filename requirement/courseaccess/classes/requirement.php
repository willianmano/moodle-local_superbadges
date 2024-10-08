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
 * This file contains the superbadges element courseaccess's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace superbadgesrequirement_courseaccess;

defined('MOODLE_INTERNAL') || die();

/**
 * The superbadges element courseaccess's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirement extends \local_superbadges\requirement {
    public static $eventstoobserve = [
        [
            'eventname' => 'core\event\course_viewed',
            'callback' => '\superbadgesrequirement_courseaccess\observers\course::viewed'
        ]
    ];

    public function save($data): \stdClass {
        global $DB;

        $requirement = new \stdClass();
        $requirement->method = $data->method;
        $requirement->badgeid = $data->badgeid;
        $requirement->target = $data->courseid;
        $requirement->value = $data->value;
        $requirement->extras = null;
        $requirement->timecreated = time();
        $requirement->timemodified = time();

        $requirement->id = $DB->insert_record('local_superbadges_requirements', $requirement);

        return $requirement;
    }

    public function user_achieved_requirement(int $userid, \stdClass $requirement): bool {
        $totalaccessdays = $this->count_course_access_days($userid, $requirement->target);

        $requireddays = (int) $requirement->value;
        if ($totalaccessdays >= $requireddays) {
            return true;
        }

        return false;
    }

    public function get_user_requirement_progress(int $userid, \stdClass $requirement): int {
        $totalaccessdays = $this->count_course_access_days($userid, $requirement->target);

        if ($totalaccessdays == 0) {
            return 0;
        }

        $requireddays = (int) $requirement->value;
        if ($totalaccessdays >= $requireddays) {
            return 100;
        }

        return (int)($totalaccessdays * 100 / $requirement->value);
    }

    private function count_course_access_days(int $userid, int $courseid) {
        global $DB;

        // TODO: adicionar context para poder diminuir escopo da query e aumentar performance
        $sql = 'SELECT
                    id,
                    DATE(FROM_UNIXTIME(timecreated)) as date
                FROM {logstore_standard_log}
                WHERE userid = :userid AND courseid = :courseid AND target = :target
                GROUP BY date
                ORDER BY date';

        $records = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid, 'target' => 'course']);

        if (!$records) {
            return 0;
        }

        return count($records);
    }

    public function get_user_requirement_progress_data(int $userid, \stdClass $requirement): array {
        $pluginname = get_string('pluginname', 'superbadgesrequirement_courseaccess');

        $progress = $this->get_user_requirement_progress($userid, $requirement);

        $progresdesc = get_string('requirementprogresdesc', 'superbadgesrequirement_courseaccess', $requirement->value);

        return [
            'pluginname' => $pluginname,
            'progress' => $progress,
            'progresdesc' => $progresdesc,
        ];
    }
}
