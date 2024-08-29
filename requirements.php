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
 * Badge requirements config page
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

// Course module id.
$id = required_param('id', PARAM_INT);

$sql = 'SELECT sb.*, b.courseid, b.name FROM {local_superbadges_badges} sb
        INNER JOIN {badge} b ON sb.badgeid = b.id
        WHERE sb.id = :id';
$superbadge = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
$course = $DB->get_record('course', ['id' => $superbadge->courseid], '*', MUST_EXIST);

require_course_login($course);

$context = context_course::instance($course->id);

if (!has_capability('moodle/course:update', $context)) {
    redirect(new moodle_url('/course/view.php', ['id' => $id]), \core\notification::error('Illegal access!'));
}

$PAGE->set_url('/local/superbadges/requirements.php', ['id' => $course->id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_superbadges');

$contentrenderable = new \local_superbadges\output\requirements($course, $context, $superbadge);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
