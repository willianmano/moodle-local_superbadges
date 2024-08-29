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
 * Base requirement file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges;

/**
 * Abstract requirement class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class requirement {
    public function save($data): \stdClass {
        global $DB;

        $requirement = new \stdClass();
        $requirement->method = $data->method;
        $requirement->badgeid = $data->badgeid;
        $requirement->target = $data->target;
        $requirement->value = $data->value;
        $requirement->extras = $data->extras ?? null;
        $requirement->timecreated = time();
        $requirement->timemodified = time();

        $requirement->id = $DB->insert_record('local_superbadges_requirements', $requirement);

        return $requirement;
    }

    public abstract function user_achieved_requirement(int $userid, \stdClass $requirement): bool;

    public abstract function get_user_requirement_progress(int $userid, \stdClass $requirement): int;

    public abstract function get_user_requirement_progress_html(int $userid, \stdClass $requirement): string;
}
