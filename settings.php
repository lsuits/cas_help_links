<?php

require_once(dirname(__FILE__) . '/../../config.php');
// require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_user::instance($USER->id);
// $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
// $header = fullname($USER);

// Start setting up the page
// $params = array();
// $PAGE->set_context($context);
// $PAGE->set_url('/local/cas_help_links/settings.php', $params);

// $PAGE->set_pagelayout('mydashboard');
// $PAGE->set_pagetype('my-index');
// $PAGE->blocks->add_region('content');
// $PAGE->set_subpage($currentpage->id);

// $PAGE->set_title('Page title here...');
// $PAGE->set_heading($header);

// require_capability('moodle/site:config', context_system::instance());

// admin_externalpage_setup('local_cas_help_links', '', null);

// $PAGE->set_heading($SITE->fullname);
// $PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_cas_help_links'));

// echo $OUTPUT->header();

// echo 'stuff here';

// echo $OUTPUT->footer();
