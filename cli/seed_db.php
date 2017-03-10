<?php

function dd($thething) { var_dump($thething);die;}

define('CLI_SCRIPT', true);

require_once('../../../config.php');
global $CFG;
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'links'       => false,
        'range'       => '', // ex: 2017-1,2017-12
        'help'        => false
    ),
    array(
        'h' => 'help',
        'r' => 'range',
    )
);

if ($options['help'])
{
    $help = "\nOptions:\n-h, --help            Print out this help\n-r, --range           The month range for activity generation (default: '2017-1,2017-12')\n\nExample:\n\$sudo -u www-data /usr/bin/php local/cas_help_links/seed_db.php --range=2016-1,2017-2\n\n";

    echo $help;
    die;
}

// create a new seeder for this date range
$seeder = new \local_cas_help_links_db_seeder();

// create link records if necessary
if ( ! empty($options['links']))
{
    // clear all link records
    $seeder->clearLinks();

    // first, seed category links
    if ($amountAdded = $seeder->seedCategoryLinks()) {
        echo $amountAdded . " category links added!\n";
    } else {
        cli_error("Could not create category links.\n");
        die;
    }

    // second, seed course links
    if ($amountAdded = $seeder->seedCourseLinks()) {
        echo $amountAdded . " course links added!\n";
    } else {
        cli_error("Could not create course links.\n");
        die;
    }

    // third, seed user (instructor) links
    if ($amountAdded = $seeder->seedUserLinks()) {
        echo $amountAdded . " user links added!\n";
    } else {
        cli_error("Could not create user links.\n");
        die;
    }
}

// if we have valid range input
if ( ! empty($options['range']))
{
    // clear all log records
    $seeder->clearLogs();

    // attempt to generate log activity for the given range
    if ($seeder->seedLog($options['range'])) {
        echo "Click activity added!\n";
    } else {
        cli_error("Could not create log activity.\n");
        die;
    }
}

die;