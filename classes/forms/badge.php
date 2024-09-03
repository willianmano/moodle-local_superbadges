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
class badge extends \moodleform {

    // TODO: Alterar para core_form\dynamic_form
    /**
     * Class constructor.
     *
     * @param array $formdata
     * @param array $customdata
     */
    public function __construct($formdata, $customdata = null) {
        parent::__construct(null, $customdata, 'post',  '', ['class' => 'superbadges-badge-form'], true, $formdata);

        $this->set_display_vertical();
    }

    /**
     * The form definition.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        $mform = $this->_form;

        $id = !(empty($this->_customdata['id'])) ? $this->_customdata['id'] : null;
        $courseid = !(empty($this->_customdata['courseid'])) ? $this->_customdata['courseid'] : null;
        $name = !(empty($this->_customdata['name'])) ? $this->_customdata['name'] : null;

        if (!empty($courseid)) {
            $mform->addElement('hidden', 'courseid', $courseid);
        }

        $mform->addElement('hidden', 'id', $id);

        $mform->addElement('text', 'name', get_string('name', 'local_superbadges'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('name', PARAM_TEXT);
        if ($name) {
            $mform->setDefault('name', $name);
        }

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

    /**
     * A bit of custom validation for this form
     *
     * @param array $data An assoc array of field=>value
     * @param array $files An array of files
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $name = isset($data['name']) ? $data['name'] : null;

        if ($this->is_submitted() && (empty($name) || strlen($name) < 3)) {
            $errors['name'] = get_string('validation:namelen', 'local_superbadges');
        }

        return $errors;
    }
}
