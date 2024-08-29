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
 * Badge utility file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\util;

defined('MOODLE_INTERNAL') || die;

use local_superbadges\util\requirement;
use moodle_url;

/**
 * Badge utility class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge {
    public function __construct() {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');
    }

    public function get_awarded_course_badges($userid, $courseid, $contextid) {
        $badges = $this->get_course_badges_with_user_award($userid, $courseid, $contextid);

        if (!$badges) {
            return false;
        }

        $mybadges = [];
        foreach ($badges as $badge) {
            if ($badge['awarded']) {
                $mybadges[] = $badge;
            }
        }

        return $mybadges;
    }

    public function get_course_badges_with_user_award($userid, $courseid, $contextid) {
        $coursebadges = $this->get_course_badges($courseid);

        if (!$coursebadges) {
            return false;
        }

        $badges = [];
        foreach ($coursebadges as $coursebadge) {
            $badges[] = [
                'id' => $coursebadge->id,
                'badgeid' => $coursebadge->badgeid,
                'name' => $coursebadge->name,
                'description' => $coursebadge->description,
                'badgeimage' => $this->get_badge_image_url($contextid, $coursebadge->badgeid),
                'awarded' => false
            ];
        }

        $userbadges = $this->get_user_course_badges($userid, $courseid);

        if (!$userbadges) {
            return $badges;
        }

        foreach ($badges as $key => $badge) {
            foreach ($userbadges as $userbadge) {
                if ($badge['badgeid'] == $userbadge->id) {
                    $badges[$key]['awarded'] = true;
                    continue 2;
                }
            }
        }

        return $badges;
    }

    public function get_course_badges($courseid) {
        global $DB;

        $sql = 'SELECT eb.*, b.description
                FROM {local_superbadges_badges} eb
                INNER JOIN {badge} b ON b.id = eb.badgeid
                WHERE b.courseid = :courseid';

        $params = ['courseid' => $courseid];

        $records = $DB->get_records_sql($sql, $params);

        if (!$records) {
            return false;
        }

        return array_values($records);
    }

    public function get_user_course_badges($userid, $courseid) {
        // Get badges fro badgelib.
        $userbadges = badges_get_user_badges($userid, $courseid);

        if ($userbadges) {
            return $userbadges;
        }

        return false;
    }

    public function get_badge_image_url($contextid, $badgeid) {
        $imageurl = moodle_url::make_pluginfile_url($contextid, 'badges', 'badgeimage', $badgeid, '/', 'f1', false);

        $imageurl->param('refresh', rand(1, 10000));

        return $imageurl;
    }

    public function get_superbadges($courseid) {
        global $DB;

        $sql = 'SELECT sb.id, b.name FROM {local_superbadges_badges} sb
                INNER JOIN {badge} b ON b.id = sb.badgeid
                WHERE b.courseid = :courseid';

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        if (!$records) {
            return false;
        }

        return array_values($records);
    }

    public function get_user_course_badges_with_requirements($userid, $courseid, $contextid) {
        $badgecriteria = new requirement();

        $badges = $this->get_course_badges_with_user_award($userid, $courseid, $contextid);

        if (!$badges) {
            return [];
        }

        foreach ($badges as $key => $badge) {
            $badgecriterias = $badgecriteria->get_badge_requirements($badge['id']);

            if (!$badgecriterias) {
                unset($badges[$key]);

                continue;
            }

            $criteriasprogress = [];
            foreach ($badgecriterias as $criteria) {
                $criteriaclass = '\superbadgesrequirement_' . $criteria->method . '\requirement';

                if (!class_exists($criteriaclass)) {
                    continue;
                }

                $criteriamethod = new $criteriaclass($userid, $criteria);

                $criteriasprogress[] = $criteriamethod->get_user_criteria_progress_html();
            }

            $badges[$key]['criteriasprogress'] = $criteriasprogress;
        }

        return array_values($badges);
    }
}
