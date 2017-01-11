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
 * @package   local_cas_help_links
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
// defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
// require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');

$user_id = required_param('id', PARAM_INT);

$context = context_system::instance();

global $PAGE, $USER, $CFG;

$PAGE->set_url($CFG->wwwroot . '/local/cas_help_links/user_settings.php', ['user_id' => $user_id]);
$PAGE->set_context($context);

require_login();
require_capability('local/cas_help_links:editglobalsettings', $context);

// make sure that the user being referenced is the auth user
if ($USER->id != $user_id) {
    echo 'sorry, no';
    // redirect (SOME URL HERE); ??
    die;
}

//////////////////////////////////////////////////////////
/// 
/// (NOTE: it is assumed this is a primary instructor or site admin)
/// 
//////////////////////////////////////////////////////////

// get all data
$courseData = \local_cas_help_links_utility::get_primary_instructor_courses($user_id, true);

// PAGE RENDERING STUFF
$PAGE->set_context($context);
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/style.css"));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/styles/bootstrap-toggle.min.css"));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/module.js"));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/scripts/bootstrap-toggle.min.js"));
$PAGE->requires->js_init_call('M.local_cas_help_links.init_index', [
    'userid' => 45,
]);
// $this->page->requires->string_for_js('noassignmentsselected', 'tool_assignmentupgrade'); <-- example for langs later?

echo $OUTPUT->header();

?>

<div id="component-user-settings">
    
    <h3>Course Links and Settings</h3>

    <div class="course-list-container col-xs-12">
        <table>
            <?php foreach ($courseData as $course) {
                echo '<tr>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input class="display-toggle" ' . $course['link_checked'] . ' type="checkbox" data-toggle="toggle" data-style="ios">&nbsp;&nbsp;&nbsp;&nbsp;' . $course['course_shortname'] . '
                                </label>
                            </div>
                        </td>

                        <td>
                            <p class="btn-edit-user-course">Edit</p>
                        </td>';

                if ($course['link_id']) {
                    echo '<td><p class="current-user-course-url"><span class="url">' . $course['link_url'] . '</span></p></td>';
                } else {
                    echo '<td><p class="current-user-course-url default-url"><span class="url">' . $course['link_url'] . '</span>&nbsp;&nbsp;(Default)</p></td>';
                }
                echo '</tr>';
            } ?>
        </table>
    </div>

    <h3>User Link and Setting</h3>

    <div class="user-container col-xs-12">
        <table>
            <tr>
                <td><p>Help Link</p></td>
                <td><p>http://www.myawesomesauce.com</p></td>
            </tr>

            <tr>
                <td><p>Hide All My Links</p></td>
                <td>
                    <div class="checkbox">
                        <label>
                            <input class="display-toggle" type="checkbox" data-toggle="toggle" data-style="ios">
                        </label>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</div>

<?php

echo $OUTPUT->footer();
