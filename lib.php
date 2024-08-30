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
 * Plugin lib file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_superbadges_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/local/superbadges/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_superbadges'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'superbadgesbadgesindex',
            new pix_icon('t/award', '')
        );
    } else {
        $url = new moodle_url('/local/superbadges/mybadges.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_superbadges'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'superbadgesbadgesmybadges',
            new pix_icon('t/award', '')
        );
    }
}

function local_superbadges_output_fragment_badge_form($args) {
    $args = (object) $args;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        $formdata = (array) $serialiseddata;
    }

    $mform = new \local_superbadges\forms\badge($formdata, [
        'id' => $serialiseddata->id,
        'name' => $serialiseddata->name,
        'description' => $serialiseddata->description,
        'courseid' => $serialiseddata->courseid,
        'badgeid' => $serialiseddata->badgeid,
    ]);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

/**
 * We run this hook when a course is deleted. Its purpose is to delete all game data.
 *
 * @param stdClass $course
 * @return void
 */
function local_superbadges_pre_course_delete(\stdClass $course) {
    $cleanup = new \local_superbadges\util\cleanup();

    $cleanup->delete_course_badges($course->id);
}
