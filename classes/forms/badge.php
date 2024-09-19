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
    protected $id;
    protected $courseid;
    protected $badgeid;
    protected $name;
    protected $description;

    protected function get_context_for_dynamic_submission(): \context {
        $this->id = $this->_ajaxformdata['id'] ?? $this->_ajaxformdata['formdata']['id'];
        $this->courseid = $this->_ajaxformdata['courseid'] ?? $this->_ajaxformdata['formdata']['courseid'];
        $this->badgeid = $this->_ajaxformdata['badgeid'] ?? $this->_ajaxformdata['formdata']['badgeid'];
        $this->name = $this->_ajaxformdata['name'] ?? $this->_ajaxformdata['formdata']['name'];
        $this->description = $this->_ajaxformdata['description'] ?? $this->_ajaxformdata['formdata']['description'];

        return \core\context\course::instance($this->courseid);
    }

    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', \core\context\course::instance($this->courseid));
    }

    public function set_data_for_dynamic_submission(): void {
        $this->set_data([
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }

    public function process_dynamic_submission() {
        $badgeutil = new \local_superbadges\util\badge();

        if ($this->id) {
            $image = null;
            if ($this->get_data()->image) {
                $image = $this->save_temp_file('image');
            }

            return $badgeutil->edit(
                $this->get_data()->id,
                $this->get_data()->badgeid,
                $this->get_data()->name,
                $this->get_data()->description,
                $image,
            );
        }

        return $badgeutil->create(
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

        $mform->addElement('hidden', 'id', $this->id);
        $mform->addElement('hidden', 'badgeid', $this->badgeid);
        $mform->addElement('hidden', 'courseid', $this->courseid);

        $mform->addElement('text', 'name', get_string('name', 'local_superbadges'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description', 'badges'), 'wrap="virtual" rows="8" cols="70"');
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addRule('description', null, 'required');

        $imageoptions = ['maxbytes' => 262144, 'accepted_types' => ['optimised_image']];
        $mform->addElement('filepicker', 'image', get_string('newimage', 'badges'), null, $imageoptions);
        $mform->addHelpButton('image', 'badgeimage', 'badges');

        if (!$this->id) {
            $mform->addRule('image', null, 'required');
        } else {
            $currentimage = $mform->createElement('static', 'currentimage', get_string('currentimage', 'badges'));
            $mform->insertElementBefore($currentimage, 'image');
        }
    }

    public function definition_after_data() {
        $mform = $this->_form;

        if (!empty($this->badgeid)) {
            $badge = new \core_badges\badge($this->badgeid);

            $mform->getElement('currentimage')->setValue($this->print_badge_image($badge, $badge->get_context(), 'large'));
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

    /**
     * Print badge image.
     *
     * @param badge $badge Badge object
     * @param stdClass $context
     * @param string $size
     */
    private function print_badge_image(\core_badges\badge $badge, \stdClass $context, $size = 'small') {
        $fsize = ($size == 'small') ? 'f2' : 'f1';

        $imageurl = \moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', $fsize, false);
        // Appending a random parameter to image link to forse browser reload the image.
        $imageurl->param('refresh', rand(1, 10000));
        $attributes = ['src' => $imageurl, 'alt' => s($badge->name), 'class' => 'activatebadge'];

        return \html_writer::empty_tag('img', $attributes);
    }
}
