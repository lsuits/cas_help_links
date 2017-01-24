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

$context = context_system::instance();

global $PAGE, $USER, $CFG;

$PAGE->set_url($CFG->wwwroot . '/local/cas_help_links/user_settings.php');
$PAGE->set_context($context);

require_login();
require_capability('local/cas_help_links:editglobalsettings', $context);

//////////////////////////////////////////////////////////
/// 
/// HANDLE FORM SUBMISSION
/// 
//////////////////////////////////////////////////////////
if ($data = data_submitted() and confirm_sesskey()) {
    
    try {
        
        \local_cas_help_links_input_handler::handle_category_settings_input($data); // @TODO - make this

    } catch (Exception $e) {
        
        var_dump($e);die; // @TODO: make this really do something, validation? errors?

    }

}

//////////////////////////////////////////////////////////
/// 
/// RENDER PAGE
///
/// (NOTE: it is assumed this user is able to edit category links
/// 
//////////////////////////////////////////////////////////

// get all data
$categorySettingsData = \local_cas_help_links_utility::get_all_category_settings();

// PAGE RENDERING STUFF
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/style.css"));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/styles/bootstrap-toggle.min.css"));
// $PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/module.js"));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/scripts/bootstrap-toggle.min.js"));

$output = $PAGE->get_renderer('local_cas_help_links');
echo $output->header();
echo $output->cas_category_links($categorySettingsData);
echo $output->footer();
