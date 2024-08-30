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
 * Index renderer file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\output;

use local_superbadges\util\badge;
use renderable;
use templatable;
use renderer_base;

/**
 * Index renderer class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mybadges implements renderable, templatable {
    protected $user;
    protected $course;
    protected $context;

    public function __construct($course, $context) {
        global $USER;

        $this->user = $USER;
        $this->course = $course;
        $this->context = $context;
    }

    public function export_for_template(renderer_base $output) {
        $badgeutil = new badge();

        $badges = $badgeutil->get_user_course_badges_with_requirements($this->user->id, $this->course->id, $this->context->id);

        foreach ($badges as $key => $badge) {
            foreach ($badge['requirements'] as $requirement) {
                $html = $output->render_from_template($this->get_mustache_file($requirement->method), $requirement->progressdata);

                $badges[$key]['requirementshtml'] = !isset($badges[$key]['requirementshtml']) ? $html : $badges[$key]['requirementshtml'] . $html;
            }
        }

        return [
            'contextid' => $this->context->id,
            'courseid' => $this->course->id,
            'badges' => $badges
        ];
    }

    private function get_mustache_file($method) {
        global $CFG;

        $file = "{$CFG->dirroot}/local/superbadges/requirement/{$method}/templates/progress.mustache";
        if (file_exists($file)) {
            return "superbadgesrequirement_{$method}/progress";
        }

        return "local_superbadges/progress";
    }
}
