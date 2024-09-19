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
 * Badge external api file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\external;

use core\context;
use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use local_superbadges\forms\badge as badgeform;
use local_superbadges\observers\badgeissuer;
use local_superbadges\util\issuer;

/**
 * Badge external api class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge extends external_api {
    /**
     * Delete badge parameters
     *
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The badge id', VALUE_REQUIRED)
        ]);
    }

    /**
     * Delete badge method
     *
     * @param int $id
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function delete($id) {
        global $DB, $PAGE;

        self::validate_parameters(self::delete_parameters(), ['id' => $id]);

        $sql = 'SELECT sb.id, sb.badgeid, b.courseid
                FROM {local_superbadges_badges} AS sb
                INNER JOIN {badge} b ON b.id = sb.badgeid
                WHERE sb.id = :id';

        $badge = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

        $context = \context_course::instance($badge->courseid);
        $PAGE->set_context($context);

        $mdlbadge = new \core_badges\badge($badge->badgeid);
        $mdlbadge->delete(false);

        $DB->delete_records('local_superbadges_requirements', ['badgeid' => $id]);

        $DB->delete_records('local_superbadges_badges', ['id' => $id]);

        return [
            'message' => get_string('deletebadge_success', 'local_superbadges'),
        ];
    }

    /**
     * Delete badge return fields
     *
     * @return external_single_structure
     */
    public static function delete_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Return message')
        ]);
    }

    /**
     * Deliver badge parameters
     *
     * @return external_function_parameters
     */
    public static function deliver_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The badge id', VALUE_REQUIRED)
        ]);
    }

    /**
     * Deliver badge method
     *
     * @param int $id
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function deliver($id) {
        global $DB, $PAGE;

        self::validate_parameters(self::delete_parameters(), ['id' => $id]);

        $sql = 'SELECT sb.id, sb.badgeid, b.courseid
                FROM {local_superbadges_badges} AS sb
                INNER JOIN {badge} b ON b.id = sb.badgeid
                WHERE sb.id = :id';

        $superbadge = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

        $requirements = $DB->get_records('local_superbadges_requirements', ['badgeid' => $superbadge->id]);

        if (!$requirements) {
            throw new \Exception(get_string('deliverbadge_badgenocriterias', 'local_superbadges'));
        }

        $context = \core\context\course::instance($superbadge->courseid);
        $PAGE->set_context($context);

        $sql = 'SELECT DISTINCT u.id';

        $capjoin = get_enrolled_with_capabilities_join($context, '', 'moodle/course:viewparticipants');

        $sql .= ' FROM {user} u ' . $capjoin->joins . ' WHERE ' . $capjoin->wheres;

        $params = $capjoin->params;

        $users = $DB->get_records_sql($sql, $params);

        $badgeissuer = new issuer();
        $counter = 0;
        foreach ($users as $user) {
            if ($badgeissuer->user_already_have_badge($user->id, $superbadge->badgeid)) {
                continue;
            }

            if (!$badgeissuer->check_if_user_can_receive_badge($user->id, $requirements)) {
                continue;
            }

            $badgeissuer->deliver_badge($user->id, $superbadge->badgeid);

            $counter++;
        }

        return [
            'message' => get_string('deliverbadge_success', 'local_superbadges', $counter)
        ];
    }

    /**
     * Deliver badge return fields
     *
     * @return external_single_structure
     */
    public static function deliver_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Return message')
        ]);
    }
}
