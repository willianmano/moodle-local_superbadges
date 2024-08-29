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
 * This file contains the superbadges element activitycompletion's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace superbadgesrequirement_activitycompletion;

defined('MOODLE_INTERNAL') || die();

/**
 * The superbadges element activitycompletion's core interaction API.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badgerequirement extends \local_superbadges\requirement {
    public function user_achieved_requirement(int $userid, \stdClass $requirement): bool {
        $totalaccessdays = $this->count_course_access_days();

        $requireddays = (int) $this->badgerequirement->value;
        if ($totalaccessdays >= $requireddays) {
            return true;
        }

        return false;
    }

    public function get_user_requirement_progress(int $userid, \stdClass $requirement): int {
        $totalaccessdays = $this->count_course_access_days();

        if ($totalaccessdays == 0) {
            return 0;
        }

        $requireddays = (int) $this->badgerequirement->value;
        if ($totalaccessdays >= $requireddays) {
            return 100;
        }

        return (int)($totalaccessdays * 100 / $this->badgerequirement->value);
    }

    private function count_course_access_days() {
        global $DB;

        // TODO: adicionar context para poder diminuir escopo da query e aumentar performance
        $sql = 'SELECT
                    id,
                    DATE(FROM_UNIXTIME(timecreated)) as date
                FROM {logstore_standard_log}
                WHERE userid = :userid AND courseid = :courseid AND target = :target
                GROUP BY date
                ORDER BY date';

        $records = $DB->get_records_sql($sql, ['userid' => $this->userid, 'courseid' => $this->badgerequirement->courseid, 'target' => 'course']);

        if (!$records) {
            return 0;
        }

        return count($records);
    }

    public function get_user_requirement_progress_html(int $userid, \stdClass $requirement): string {
        $pluginname = get_string('pluginname', 'superbadgesrequirement_courseaccess');

        $progress = $this->get_user_requirement_progress();

        $requirementprogresdesc = get_string('requirementprogresdesc', 'superbadgesrequirement_courseaccess', $this->badgerequirement->value);

        return '<p class="mb-0">'.$pluginname.'
                        <a class="btn btn-link p-0"
                           role="button"
                           data-container="body"
                           data-toggle="popover"
                           data-placement="right"
                           data-html="true"
                           tabindex="0"
                           data-trigger="focus"
                           data-content="<div class=\'no-overflow\'><p>'.$requirementprogresdesc.'</p></div>">
                            <i class="icon fa fa-info-circle text-info fa-fw " title="'.$pluginname.'" role="img" aria-label="'.$pluginname.'"></i>
                        </a>
                    </p>
                    <div class="progress ml-0">
                        <div class="progress-bar" role="progressbar" style="width: '.$progress.'%" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100">'.$progress.'%</div>
                    </div>';
    }
}
