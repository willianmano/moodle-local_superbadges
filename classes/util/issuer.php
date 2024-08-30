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
 * Badge issuer utility file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\util;

defined('MOODLE_INTERNAL') || die;

/**
 * Badge issuer utility class.
 *
 * @package     local_evokegame
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class issuer {
    /**
     * @param int $badgeid The super badge id
     * @param int $userid The user id
     *
     * @return bool True if the badge was delivered otherwise false
     *
     * @throws \dml_exception
     */
    public function deliver(int $badgeid, int $userid): bool {
        global $DB;

        $badge = $DB->get_record('local_superbadges_badges', ['id' => $badgeid], '*', MUST_EXIST);

        $mdlbadge = $DB->get_record('badge', ['id' => $badge->badgeid], '*', MUST_EXIST);

        // Do nothing. User already have this badge.
        if ($this->user_already_have_badge($userid, $mdlbadge->id)) {
            return false;
        }

        $requirements = $DB->get_records('local_superbadges_requirements', ['badgeid' => $badge->id]);

        if (!$this->check_if_user_can_receive_badge($userid, $requirements)) {
            return false;
        }

        return $this->deliver_badge($userid, $mdlbadge->id);
    }

    public function user_already_have_badge(int $userid, int $badgeid): bool {
        $badge = new \core_badges\badge($badgeid);

        return $badge->is_issued($userid);
    }

    public function check_if_user_can_receive_badge($userid, $requirements): bool {
        foreach ($requirements as $requirement) {
            $requirementclass = "\superbadgesrequirement_{$requirement->method}\\requirement";

            if (!class_exists($requirementclass)) {
                return false;
            }

            $requirementobject = new $requirementclass();

            if (!$requirementobject->user_achieved_requirement($userid, $requirement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $userid User id
     * @param int $badgeid Moodle badge id
     *
     * @return bool True if the badge was delivered otherwise false
     *
     * @throws \moodle_exception
     */
    public function deliver_badge(int $userid, int $badgeid): bool {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');
        require_once($CFG->dirroot . '/badges/lib/awardlib.php');

        $admins = explode(',', $CFG->siteadmins);

        // First admin userid.
        $issuerid = current($admins);

        // Admin role.
        $issuerrole = 3;

        $badge = new \core_badges\badge($badgeid);

        if (process_manual_award($userid, $issuerid, $issuerrole, $badgeid)) {
            $badge->issue($userid);

            return true;
        }

        return false;
    }
}
