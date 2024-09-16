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
 * Badge form file.
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_superbadges\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * The mform class for creating a badge
 *
 * @package    local_superbadges
 * @copyright  2024 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge extends \core_form\dynamic_form {
    protected $courseid;

    protected function get_context_for_dynamic_submission(): \context {
        $this->courseid = $this->_ajaxformdata['courseid'] ?? $this->_ajaxformdata['formdata']['courseid'];

        return \core\context\course::instance($this->courseid);
    }

    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', \core\context\course::instance($this->courseid));
    }

    public function set_data_for_dynamic_submission(): void {
        $this->set_data([]);
    }

    public function process_dynamic_submission() {
        return \local_superbadges\external\badge::create(
            $this->get_data()->courseid,
            $this->get_data()->name,
            $this->get_data()->description,
            $this->save_temp_file('image'),
        );
    }

    /**
     * The form definition.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        $mform = $this->_form;

        $id = !(empty($this->id)) ? $this->id : null;
        $courseid = !(empty($this->courseid)) ? $this->courseid : null;

        if (!empty($courseid)) {
            $mform->addElement('hidden', 'courseid', $courseid);
        }

        $mform->addElement('hidden', 'id', $id);

        $mform->addElement('text', 'name', get_string('name', 'local_superbadges'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('name', PARAM_TEXT);

        if (!$id) {
            $mform->addElement('textarea', 'description', get_string('description', 'badges'), 'wrap="virtual" rows="8" cols="70"');
            $mform->setType('description', PARAM_NOTAGS);
            $mform->addRule('description', null, 'required');

            $imageoptions = array('maxbytes' => 262144, 'accepted_types' => array('optimised_image'));
            $mform->addElement('filepicker', 'image', get_string('newimage', 'badges'), null, $imageoptions);
            $mform->addRule('image', null, 'required');
            $mform->addHelpButton('image', 'badgeimage', 'badges');
        }
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = get_string('required');
        }

        return $errors;
    }

    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/local/superbadges/index.php', ['id' => $this->courseid]);
    }
}
