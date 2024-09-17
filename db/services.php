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
 * Pluguin services file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_superbadges_editbadge' => [
        'classname' => 'local_superbadges\external\badge',
        'classpath' => 'local/superbadges/classes/external/badge.php',
        'methodname' => 'edit',
        'description' => 'Creates a new badge',
        'type' => 'write',
        'ajax' => true
    ],
    'local_superbadges_deletebadge' => [
        'classname' => 'local_superbadges\external\badge',
        'classpath' => 'local/superbadges/classes/external/badge.php',
        'methodname' => 'delete',
        'description' => 'Deletes a badge',
        'type' => 'write',
        'ajax' => true
    ],
    'local_superbadges_addrequirement' => [
        'classname' => 'local_superbadges\external\requirement',
        'classpath' => 'local/superbadges/classes/external/requirement.php',
        'methodname' => 'add',
        'description' => 'Adds a new requirement to badge',
        'type' => 'write',
        'ajax' => true
    ],
    'local_superbadges_deleterequirement' => [
        'classname' => 'local_superbadges\external\requirement',
        'classpath' => 'local/superbadges/classes/external/requirement.php',
        'methodname' => 'delete',
        'description' => 'Deletes a badge',
        'type' => 'write',
        'ajax' => true
    ],
    'local_superbadges_checknotificationbadge' => [
        'classname' => 'local_superbadges\external\notification',
        'classpath' => 'local/superbadges/classes/external/notification.php',
        'methodname' => 'checknotificationbadge',
        'description' => 'Check if a user has a badge notification pending',
        'type' => 'write',
        'ajax' => true
    ],
    'local_superbadges_deliverbadge' => [
        'classname' => 'local_superbadges\external\badge',
        'classpath' => 'local/superbadges/classes/external/badge.php',
        'methodname' => 'deliver',
        'description' => 'Deliver a badge for users',
        'type' => 'write',
        'ajax' => true
    ],
];
