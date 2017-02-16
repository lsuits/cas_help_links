<?php
 
class local_cas_help_links_logger {

    /**
     * Persists a log record for a "link clicked" activity
     * 
     * @param  int  $user_id  moodle user id
     * @param  int  $course_id  moodle course id
     * @param  int  $link_id  'help links' table record id
     * @return boid
     */
    public static function log_link_click($user_id, $course_id, $link_id = 0)
    {
        // if there is no explicit link id passed, this was a general help click
        if ( ! $link_id) {
            $linkType = 'site';
            $linkUrl = get_config('local_cas_help_links', 'default_help_link');

        // otherwise, attempt to fetch this link record
        } else {
            // if there is a link record, log the appropriate info
            if ($link = \local_cas_help_links_utility::get_link($link_id)) {
                $linkType = $link->type;
                $linkUrl = $link->link;

            // otherwise, there is a problem (link should exist here)
            } else {
                // bail out @TODO - log this error internally?
                return;
            }
        }

        // attempt to fetch this ues course's data
        if ($uesCourseData = \local_cas_help_links_utility::get_ues_course_data($course_id)) {
            // if no result, fallback to empty strings so as not to distrupt the redirect
            if (empty($uesCourseData)) {
                $courseDept = '';
                $courseNumber = '';
            
            // otherwise, log the appropriate info
            } else {
                $courseDept = $uesCourseData->department;
                $courseNumber = $uesCourseData->cou_number;
            }

        // otherwise, something has gone wrong
        } else {
            $courseDept = '';
            $courseNumber = '';
        }

        global $DB;

        $log_record = new stdClass;
        $log_record->user_id = $user_id;
        $log_record->time_clicked = time();
        $log_record->link_type = $linkType;
        $log_record->link_url = $linkUrl;
        $log_record->course_dept = $courseDept;
        $log_record->course_number = $courseNumber;

        $DB->insert_record(self::get_log_table_name(), $log_record);
    }

    /**
     * Returns the name of the 'help links log' table
     * 
     * @return string
     */
    private static function get_log_table_name()
    {
        return 'local_cas_help_links_log';
    }
    
}
