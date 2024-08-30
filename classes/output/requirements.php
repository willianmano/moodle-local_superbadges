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
 * Requirements renderer file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Requirements renderer class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirements implements renderable, templatable {
    protected $course;
    protected $context;
    protected $superbadge;

    public function __construct($course, $context, $superbadge) {
        $this->course = $course;
        $this->context = $context;
        $this->superbadge = $superbadge;
    }

    public function export_for_template(renderer_base $output) {
        $badgecriteriautil = new \local_superbadges\util\requirement();

        $installedmethods = \core_plugin_manager::instance()->get_plugins_of_type('superbadgesrequirement');

        $availablemethods = [];
        foreach ($installedmethods as $method) {
            $availablemethods[] = [
                'key' => $method->name,
                'name' => $method->displayname,
            ];
        }

        return [
            'contextid' => $this->context->id,
            'courseid' => $this->course->id,
            'badgeid' => $this->superbadge->id,
            'requirements' => $badgecriteriautil->get_badge_requirements($this->superbadge->id),
            'availablemethods' => $availablemethods
        ];
    }
}
