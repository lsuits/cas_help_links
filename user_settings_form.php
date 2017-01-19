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
 * Form for local cas_help_links
 *
 * @package    local_cas_help_links
 * @copyright  2016, William C. Mazilly, Robert Russo
 * @copyright  2016, Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class cas_form extends moodleform {

    function definition() {
        global $CFG, $DB, $OUTPUT;
        $mform = $this->_form;
        $attributes = array(
        'class' => 'cas-display-toggle'
        );

        $courses = $this->_customdata['courseSettingsData'];
        $categories = $this->_customdata['categorySettingsData'];
        $userSettingsData = $this->_customdata['userSettingsData'];
        $mform->addElement('hidden', 'id', $userSettingsData['user_id']);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'personal_preferences', 'Personal Preferences');//get_string('titleforlegened', 'modulename'));
        foreach ($courses as $course) {
            $cfname = $course['course_fullname'] . ' <strong>(' . $course['course_category_name'] . ')</strong>';
            $mform->addElement('advcheckbox', $course['display_input_name'], 'Hide link for this course: ', null, $attributes, array(0, 1));
            $mform->addElement('text', $course['link_input_name'], $cfname, null);
            $mform->setDefault($course['display_input_name'], $course['link_checked']);
            $mform->disabledIf($course['link_input_name'], $course['display_input_name'], 'checked');
            $mform->setDefault($course['link_input_name'], $course['link_url']);
            $mform->setType($course['link_input_name'], PARAM_TEXT);
        }

        $mform->addElement('header', 'category_preferences', 'Category Preferences');//get_string('titleforlegened', 'modulename'));
        foreach ($categories as $category) {
            $cfname = $category['category_name'];
            $mform->addElement('checkbox', $category['display_input_name'], 'Hide all category links: ');
            $mform->addElement('text', $category['link_input_name'], $cfname, null);
            $mform->disabledIf($category['link_input_name'], $category['display_input_name'], 'checked');

            $mform->setDefault($category['display_input_name'],  $category['link_checked']);
            $mform->setDefault($category['link_input_name'], $category['link_url']);
            $mform->setType($category['link_input_name'], PARAM_TEXT);
        }

        $mform->addElement('header', 'user_preferences', 'User Preferences');//get_string('titleforlegened', 'modulename'));
        $mform->addElement('checkbox', $userSettingsData['display_input_name'], 'Hide all my links: ');
        $mform->addElement('text', $userSettingsData['link_input_name'], NULL, null);
        $mform->disabledIf($userSettingsData['link_input_name'], $userSettingsData['display_input_name'], 'checked');

        $mform->setDefault($userSettingsData['display_input_name'],  $userSettingsData['link_checked']);
        $mform->setDefault($userSettingsData['link_input_name'], $userSettingsData['link_url']);
        $mform->setType($userSettingsData['link_input_name'], PARAM_TEXT);

        $this->add_action_buttons();
    }
}
