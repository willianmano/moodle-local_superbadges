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

/**
 * Badge external api class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge extends external_api {
    /**
     * Create badge parameters
     *
     * @return external_function_parameters
     */
    public static function create_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course id'),
            'name' => new external_value(PARAM_TEXT, 'The badge name'),
            'description' => new external_value(PARAM_RAW, 'The badge description'),
            'image' => new external_value(PARAM_RAW, 'The imagem tmp id'),
        ]);
    }

    /**
     * Create badge method
     *
     * @param string $jsonformdata
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function create($courseid, $name, $description, $image) {
        global $DB, $PAGE, $CFG, $USER, $SITE;

        $transaction = $DB->start_delegated_transaction();

        try {
            // We always must pass webservice params through validate_parameters.
            $params = (object) self::validate_parameters(self::create_parameters(), [
                'courseid' => $courseid,
                'name' => $name,
                'description' => $description,
                'image' => $image
            ]);

            $context = \core\context\course::instance($params->courseid);

            // We always must call validate_context in a webservice.
            self::validate_context($context);

            $now = time();

            // Creates Moodle Badge.
            require_once($CFG->libdir . '/badgeslib.php');

            $mdlbadge = new \stdClass();
            $mdlbadge->name = $params->name;
            $mdlbadge->description = $params->description;
            $mdlbadge->courseid = $params->courseid;
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

            $eventparams = array('objectid' => $mdlbadgeid, 'context' => $PAGE->context);
            $event = \core\event\badge_created::create($eventparams);
            $event->trigger();

            $newbadge = new \core_badges\badge($mdlbadgeid);

            badges_process_badge_image($newbadge, $params->image);

            $superbadge = new \stdClass();
            $superbadge->courseid = $params->courseid;
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

    /**
     * Create badge return fields
     *
     * @return external_single_structure
     */
    public static function create_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_RAW, 'Return message'),
            'data' => new external_value(PARAM_RAW, 'Return data')
        ]);
    }

    /**
     * Create badge parameters
     *
     * @return external_function_parameters
     */
    public static function edit_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course module'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the badge form, encoded as a json array')
        ]);
    }

    /**
     * Create badge method
     *
     * @param int $contextid
     * @param string $jsonformdata
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function edit($contextid, $jsonformdata) {
        global $DB;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::edit_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = [];
        parse_str($serialiseddata, $data);

        $mform = new badgeform($data, $data);

        $validateddata = $mform->get_data();

        if (!$validateddata) {
            throw new \moodle_exception('invalidformdata');
        }

        $badge = new \stdClass();
        $badge->id = $validateddata->id;
        $badge->name = $validateddata->name;
        $badge->type = $validateddata->type;
        $badge->highlight = $validateddata->highlight;
        $badge->timemodified = time();

        $DB->update_record('superbadges_badges', $badge);

        return [
            'status' => 'ok',
            'message' => get_string('editbadge_success', 'local_superbadges'),
            'data' => json_encode($badge)
        ];
    }

    /**
     * Create badge return fields
     *
     * @return external_single_structure
     */
    public static function edit_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Operation status'),
            'message' => new external_value(PARAM_RAW, 'Return message'),
            'data' => new external_value(PARAM_RAW, 'Return data')
        ]);
    }

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

        $badge = $DB->get_record_sql($sql, ['id' => $id]);

        $context = \context_course::instance($badge->courseid);
        $PAGE->set_context($context);

        $mdlbadge = new \core_badges\badge($badge->badgeid);
        $mdlbadge->delete();

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
            'badge' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The badge id', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Deliver badge method
     *
     * @param array $badge
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function deliver($badge) {
        global $DB, $PAGE;

        self::validate_parameters(self::delete_parameters(), ['badge' => $badge]);

        $badge = (object)$badge;

        $superbadge = $DB->get_record('superbadges_badges', ['id' => $badge->id], '*', MUST_EXIST);

        $badgecriterias = $DB->get_records('superbadges_badges_criterias', ['superbadgeid' => $superbadge->id]);

        if (!$badgecriterias) {
            throw new \Exception(get_string('deliverbadge_badgenocriterias', 'local_superbadges'));
        }

        $context = \context_course::instance($superbadge->courseid);
        $PAGE->set_context($context);

        $sql = 'SELECT DISTINCT u.id';

        $capjoin = get_enrolled_with_capabilities_join($context, '', 'moodle/course:viewparticipants');

        $sql .= ' FROM {user} u ' . $capjoin->joins . ' WHERE ' . $capjoin->wheres;

        $params = $capjoin->params;

        $users = $DB->get_records_sql($sql, $params);

        $counter = 0;
        foreach ($users as $user) {
            if (badgeissuer::user_already_have_badge($user->id, $superbadge->badgeid)) {
                continue;
            }

            if (!badgeissuer::check_if_user_can_receive_badge($user->id, $badgecriterias)) {
                continue;
            }

            badgeissuer::deliver_badge($user->id, $superbadge);

            $counter++;
        }

        return [
            'status' => 'ok',
            'message' => get_string('deliverbadge_success', 'local_superbadges', $counter)
        ];
    }

    /**
     * Deliver badge return fields
     *
     * @return external_single_structure
     */
    public static function deliver_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_TEXT, 'Return message')
            )
        );
    }
}
