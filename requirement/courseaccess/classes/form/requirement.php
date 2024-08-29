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

namespace superbadgesrequirement_courseaccess\form;

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
            'method' => $this->optional_param('method', 'courseaccess', PARAM_TEXT),
        ]);
    }

    public function process_dynamic_submission() {
        if ($this->get_data()->name === 'error') {
            // For testing exceptions.
            throw new \coding_exception('Value is error');
        }
        return $this->get_data();
    }

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'value', get_string('daystoaccess', 'superbadgesrequirement_courseaccess'), 'size="50"');
        $mform->addHelpButton('value', 'daystoaccess', 'superbadgesrequirement_courseaccess');
        $mform->addRule('value', null, 'required', null, 'client');
        $mform->setType('value', PARAM_TEXT);

        // Hidden elements.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $this->courseid);

        $mform->addElement('hidden', 'badgeid');
        $mform->setType('badgeid', PARAM_INT);
        $mform->setDefault('badgeid', $this->badgeid);

        $mform->addElement('hidden', 'method');
        $mform->setType('method', PARAM_TEXT);
    }

    public function validation($data, $files) {
        $errors = [];
        if (empty($data['value'])) {
            $errors['value'] = get_string('required');
        }

        if (((int)($data['value'])) < 1) {
            $errors['value'] = 'Valor deve ser maior do que 1';
        }

        return $errors;
    }

    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/local/superbadges/index.php');
    }
}
