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

    public function create($courseid, $name, $description, $image) {
        global $DB, $CFG, $USER, $SITE;

        $transaction = $DB->start_delegated_transaction();

        try {
            $context = \core\context\course::instance($courseid);

            $now = time();

            $mdlbadge = new \stdClass();
            $mdlbadge->name = $name;
            $mdlbadge->description = $description;
            $mdlbadge->courseid = $courseid;
            $mdlbadge->usercreated = $USER->id;
            $mdlbadge->usermodified = $USER->id;
            $mdlbadge->version = '';
            $mdlbadge->type = BADGE_TYPE_COURSE;
            $mdlbadge->imageauthorname = '';
            $mdlbadge->imageauthoremail = '';
            $mdlbadge->imageauthorurl = '';
            $mdlbadge->imagecaption = '';
            $mdlbadge->timecreated = $now;
            $mdlbadge->timemodified = $now;
            $mdlbadge->issuerurl = $CFG->wwwroot;
            $mdlbadge->issuername = $SITE->fullname;
            $mdlbadge->issuercontact = $CFG->badges_defaultissuercontact;

            $mdlbadge->messagesubject = get_string('messagesubject', 'badges');
            $mdlbadge->message = get_string('messagebody', 'badges',
                \html_writer::link($CFG->wwwroot . '/badges/mybadges.php', get_string('managebadges', 'badges')));
            $mdlbadge->attachment = 1;
            $mdlbadge->notification = BADGE_MESSAGE_NEVER;
            $mdlbadge->status = BADGE_STATUS_ACTIVE;

            $mdlbadgeid = $DB->insert_record('badge', $mdlbadge);

            // Add badges criterias.
            $badgecriteria = new \stdClass();

            $badgecriteria->badgeid = $mdlbadgeid;
            $badgecriteria->criteriatype = 0;
            $badgecriteria->method = 1;
            $badgecriteria->description = '';
            $badgecriteria->descriptionformat = 1;

            $DB->insert_record('badge_criteria', $badgecriteria);

            $badgecriteria->criteriatype = 2;
            $badgecriteria->method = 2;

            $DB->insert_record('badge_criteria', $badgecriteria);

            $eventparams = array('objectid' => $mdlbadgeid, 'context' => $context);
            $event = \core\event\badge_created::create($eventparams);
            $event->trigger();

            $newbadge = new \core_badges\badge($mdlbadgeid);

            badges_process_badge_image($newbadge, $image);

            $superbadge = new \stdClass();
            $superbadge->courseid = $courseid;
            $superbadge->badgeid = $mdlbadgeid;
            $superbadge->timecreated = time();
            $superbadge->timemodified = time();

            $superbadgeid = $DB->insert_record('local_superbadges_badges', $superbadge);

            $superbadge->id = $superbadgeid;
            $superbadge->name = $mdlbadge->name;

            $transaction->allow_commit();

            return [
                'message' => get_string('createbadge_success', 'local_superbadges'),
                'data' => json_encode($superbadge)
            ];
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
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
        foreach ($coursebadges as $badge) {
            $badges[] = [
                'id' => $badge->id,
                'badgeid' => $badge->badgeid,
                'name' => $badge->name,
                'description' => $badge->description,
                'badgeimage' => $this->get_badge_image_url($contextid, $badge->badgeid),
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

    public function get_user_course_badges(int $userid, int $courseid) {
        // Get badges fro badgelib.
        $userbadges = badges_get_user_badges($userid, $courseid);

        if ($userbadges) {
            return $userbadges;
        }

        return false;
    }

    public function get_badge_image_url(int $contextid, int $badgeid) {
        $imageurl = moodle_url::make_pluginfile_url($contextid, 'badges', 'badgeimage', $badgeid, '/', 'f1', false);

        $imageurl->param('refresh', rand(1, 10000));

        return $imageurl;
    }

    public function get_course_badges($courseid) {
        global $DB;

        $sql = 'SELECT sb.*, b.name, b.description
                FROM {local_superbadges_badges} sb
                INNER JOIN {badge} b ON b.id = sb.badgeid
                WHERE b.courseid = :courseid';

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        if (!$records) {
            return false;
        }

        return array_values($records);
    }

    public function get_user_course_badges_with_requirements(int $userid, int $courseid, int $contextid) {
        $badgerequirement = new requirement();

        $badges = $this->get_course_badges_with_user_award($userid, $courseid, $contextid);

        if (!$badges) {
            return [];
        }

        foreach ($badges as $key => $badge) {
            $badgerequirements = $badgerequirement->get_badge_requirements($badge['id']);

            if (!$badgerequirements) {
                unset($badges[$key]);

                continue;
            }

            $badges[$key]['requirements'] = [];
            foreach ($badgerequirements as $requirement) {
                $requirementclass = '\superbadgesrequirement_' . $requirement->method . '\requirement';

                if (!class_exists($requirementclass)) {
                    continue;
                }

                $requirementmethod = new $requirementclass($userid, $requirement);

                $requirement->progressdata = $requirementmethod->get_user_requirement_progress_data($userid, $requirement);

                $badges[$key]['requirements'][] = $requirement;
            }
        }

        return array_values($badges);
    }
}
