<?php
 
class local_cas_help_links_utility {

    /**
     * Returns an array of this primary instructor user's course data
     * 
     * @param  int  $user_id
     * @param  boolean $includeLinkData  whether or not to include 'cas_help_link' data in the results
     * @param  boolean $asJson  whether or not to return the results as JSON
     * @return array|string
     */
    public static function get_primary_instructor_courses($user_id, $includeLinkData = false, $asJson = false)
    {
        global $DB;

        $result = $DB->get_records_sql('SELECT DISTINCT u.id, c.id, c.fullname, c.shortname, c.idnumber FROM mdl_enrol_ues_teachers t
            INNER JOIN mdl_user u ON u.id = t.userid
            INNER JOIN mdl_enrol_ues_sections sec ON sec.id = t.sectionid
            INNER JOIN mdl_course c ON c.idnumber = sec.idnumber
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND t.primary_flag = "1"
            AND t.status = "enrolled"
            AND u.id = ?', array($user_id));

        if ( ! $includeLinkData)
            return $asJson ? json_encode($result) : $result;

        $transformedResult = self::transform_course_data($result);

        return $asJson ? json_encode($transformedResult) : $transformedResult;
    }

    /**
     * Returns an array of the given course data array but including 'cas_help_link' information
     * 
     * @param  array $courses
     * @return array
     */
    private static function transform_course_data($courses)
    {
        global $USER;

        $output = [];

        foreach ($courses as $courseArray) {
            $course = get_course($courseArray->id);

            $helpUrlArray = \local_cas_help_links_url_generator::getUrlArrayForCourse($course, false);    

            $output[$courseArray->id] = [
                'user_id' => $USER->id,
                'course_id' => $courseArray->id,
                'course_fullname' => $courseArray->fullname,
                'course_shortname' => $courseArray->shortname,
                'course_idnumber' => $courseArray->idnumber,
                'link_id' => $helpUrlArray['link_id'],
                'link_display' => $helpUrlArray['display'],
                'link_checked' => $helpUrlArray['display'] ? 'checked' : '',
                'link_url' => $helpUrlArray['url'],
                'link_edit_url' => 'http://www.google.com',
            ];
        }

        return $output;
    }

}