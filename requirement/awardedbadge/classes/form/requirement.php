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
 * Requirement form definition file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace superbadgesrequirement_awardedbadge\form;

/**
 * Requirement form definition class.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requirement extends \core_form\dynamic_form {
    protected $courseid;
    protected $badgeid;

    protected function get_context_for_dynamic_submission(): \context {
        $this->courseid = $this->_ajaxformdata['courseid'] ?? $this->_ajaxformdata['formdata']['courseid'];
        $this->badgeid = $this->_ajaxformdata['badgeid'] ?? $this->_ajaxformdata['formdata']['badgeid'];

        return \core\context\course::instance($this->courseid);
    }

    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', \core\context\course::instance($this->courseid));
    }

    public function set_data_for_dynamic_submission(): void {
        $this->set_data([
            'method' => $this->optional_param('method', 'awardedbadge', PARAM_TEXT),
            'value' => $this->optional_param('value', 1, PARAM_INT),
        ]);
    }

    public function process_dynamic_submission() {
        if ($this->get_data()->name === 'error') {
            // For testing exceptions.
            throw new \coding_exception('Name is error');
        }
        return $this->get_data();
    }

    public function definition() {
        $mform = $this->_form;

        $badges = $this->get_badges();

        $mform->addElement('select', 'target', get_string('activity', 'superbadgesrequirement_awardedbadge'), $badges);
        $mform->addHelpButton('target', 'activity', 'superbadgesrequirement_awardedbadge');
        $mform->addRule('target', null, 'required', null, 'client');
        $mform->setType('target', PARAM_TEXT);

        // Hidden elements.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $this->courseid);

        $mform->addElement('hidden', 'badgeid');
        $mform->setType('badgeid', PARAM_INT);
        $mform->setDefault('badgeid', $this->badgeid);

        $mform->addElement('hidden', 'method');
        $mform->setType('method', PARAM_TEXT);

        $mform->addElement('hidden', 'value');
        $mform->setType('value', PARAM_INT);
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data['value'])) {
            $errors['value'] = get_string('required');
        }

        return $errors;
    }

    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/local/superbadges/index.php');
    }

    protected function get_badges() {
        global $DB, $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $superbadge = $DB->get_record('local_superbadges_badges', ['id' => $this->badgeid], 'id, badgeid', MUST_EXIST);

        $sql = "SELECT id, name
                FROM {badge}
                WHERE courseid = :courseid
                  AND type = :type
                  AND status != :deleted
                  AND id != :badgeid";

        $params = [
            'courseid' => $this->courseid,
            'type'     => BADGE_TYPE_COURSE,
            'deleted' => BADGE_STATUS_ARCHIVED,
            'badgeid' => $superbadge->badgeid,
        ];

        return $DB->get_records_sql_menu($sql, $params);
    }
}
