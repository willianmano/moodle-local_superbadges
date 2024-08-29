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
 * Badge requirement external api file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\external;

use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;

/**
 * Badge requirement external api class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirement extends external_api {
    /**
     * Create badge parameters
     *
     * @return external_function_parameters
     */
    public static function add_parameters() {
        return new external_function_parameters([
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the badge form, encoded as a json array')
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
    public static function add($jsonformdata) {
        try {
            // We always must pass webservice params through validate_parameters.
            $params = self::validate_parameters(self::add_parameters(), ['jsonformdata' => $jsonformdata]);

            $serialiseddata = json_decode($params['jsonformdata']);

            $context = \core\context\course::instance($serialiseddata->courseid);

            // We always must call validate_context in a webservice.
            self::validate_context($context);

            $requirementclass = "\superbadgesrequirement_{$serialiseddata->method}\badgerequirement";

            $requirementinstance = new $requirementclass;

            $data = $requirementinstance->save($serialiseddata);

            return [
                'message' => get_string('addrequirement_success', 'local_superbadges'),
                'data' => json_encode($data)
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
    public static function add_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_RAW, 'Return message'),
                'data' => new external_value(PARAM_RAW, 'Return data')
            )
        );
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
        global $DB;

            self::validate_parameters(self::delete_parameters(), ['id' => $id]);

            $DB->get_record('local_superbadges_requirements', ['id' => $id], 'id', MUST_EXIST);

            $DB->delete_records('local_superbadges_requirements', ['id' => $id]);

            return [
                'message' => get_string('deleterequirement_success', 'local_superbadges'),
            ];
    }

    /**
     * Delete badge return fields
     *
     * @return external_single_structure
     */
    public static function delete_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, 'Return message')
            )
        );
    }
}
