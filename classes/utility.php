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

    /**
     * Returns whether or not this plugin is enabled based off plugin config
     * 
     * @return boolean
     */
    public static function isPluginEnabled()
    {
        return (bool) get_config('local_cas_help_links', 'show_links_global');
    }

    /**
     * Returns a "primary instructor" user id given a course id number
     * 
     * @param  string $idnumber
     * @return int
     */
    public static function getPrimaryInstructorId($idnumber)
    {
        global $DB;

        $result = $DB->get_records_sql('SELECT DISTINCT(t.userid), cts.requesterid FROM mdl_enrol_ues_sections sec
            INNER JOIN mdl_enrol_ues_teachers t ON t.sectionid = sec.id
            LEFT JOIN mdl_enrol_cps_team_sections cts ON cts.sectionid = sec.id
            WHERE t.primary_flag = 1
            AND sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND sec.idnumber = ?', array($idnumber));

        // if no query results, assume there is no primary user id
        if ( ! count($result))
            return 0;

        // get the key (column name) in which we'll look up the primary from the query results
        $key = count($result) > 1 ? 'requesterid' : 'userid';

        // get the first record from the results
        $first = array_values($result)[0];

        // get the user id from the results
        $userId = property_exists($first, $key) ? $first->$key : 0;

        // be sure to return 0 if still no user id can be determined
        return ! is_null($userId) ? (int) $userId : 0;
    }

    /**
     * Returns the currently authenticated user id
     * 
     * @return int
     */
    public static function getAuthUserId() {
        global $USER;
        
        return $USER->id;
    }

    /**
     * Retrieves the appropriate pref according to override hierarchy
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return mixed array|bool
     */
    public static function getSelectedPref($course_id, $category_id, $primary_instructor_user_id)
    {
        // pull all of the preference data relative to the course, category, user
        $prefs = self::getRelatedPrefData($course_id, $category_id, $primary_instructor_user_id);

        $selectedPref = false;

        // first, keep only prefs with this primary associated
        if ($primaryUserPrefs = array_where($prefs, function ($key, $pref) use ($primary_instructor_user_id) {
            return $pref->user_id == $primary_instructor_user_id;
        }))
        {
            // if so, keep only primary "hide" prefs, if any
            if ($primaryUserHidePrefs = array_where($primaryUserPrefs, function ($key, $pref) {
                return ! $pref->display;
            }))
            {
                // get any "hide" pref for this primary user
                $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) {
                    return $pref->type == 'user';
                });

                if ( ! $selectedPref) {
                    // get any "hide" pref for this primary user & category
                    $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) use ($category_id) {
                        return $pref->type == 'category' && $pref->category_id == $category_id;
                    });
                }

                if ( ! $selectedPref) {
                    // get any "hide" pref for this primary user & course
                    $selectedPref = array_where($primaryUserHidePrefs, function ($key, $pref) use ($course_id) {
                        return $pref->type == 'course' && $pref->course_id == $course_id;
                    });
                }
            // otherwise, keep only "show" prefs, if any
            } else if ($primaryUserShowPrefs = array_where($primaryUserPrefs, function ($key, $pref) {
                return $pref->display;
            }))
            {
                // get any "show" pref for this primary user & course
                $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) use ($course_id) {
                    return $pref->type == 'course' && $pref->course_id == $course_id;
                });

                // get any "show" pref for this primary user & category
                if ( ! $selectedPref) {
                    $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) use ($category_id) {
                        return $pref->type == 'category' && $pref->category_id == $category_id;
                    });
                }

                // get any "show" pref for this primary user
                if ( ! $selectedPref) {
                    $selectedPref = array_where($primaryUserShowPrefs, function ($key, $pref) {
                        return $pref->type == 'user';
                    });
                }
            }
        // otherwise, keep only this category's prefs
        } else if ($categoryPrefs = array_where($prefs, function ($key, $pref) use ($category_id) {
                return $pref->type == 'category' && $pref->category_id == $category_id;
            })) {

            // get any "hide" pref for this category
            $selectedPref = array_where($categoryPrefs, function ($key, $pref) {
                return ! $pref->display;
            });

            if ( ! $selectedPref) {
                // get any "show" pref for this category
                $selectedPref = array_where($categoryPrefs, function ($key, $pref) {
                    return $pref->display;
                });
            }
        }
        
        return $selectedPref;
    }

    /**
     * Retrieves all pref data related to the given parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return array
     */
    private static function getRelatedPrefData($course_id, $category_id, $primary_instructor_user_id = 0)
    {
        global $DB;
        
        $whereClause = self::buildPrefsWhereClause($course_id, $category_id, $primary_instructor_user_id);

        $result = $DB->get_records_sql("SELECT * FROM mdl_local_cas_help_links links WHERE " . $whereClause);

        return $result;
    }

    /**
     * Returns an appropriate sql where clause string given specific parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return string
     */
    private static function buildPrefsWhereClause($course_id, $category_id, $primary_instructor_user_id = 0)
    {
        $wheres = [];
        
        // include this category in the results
        $wheres[] = "links.type = 'category' AND links.category_id = " . $category_id;

        // if a primary user was specified, include their link prefs
        if ($primary_instructor_user_id) {
            // include this user's personal settings
            $wheres[] = "links.type = 'user' AND links.user_id = " . $primary_instructor_user_id;
            
            // include this user's specific course settings
            $wheres[] = "links.type = 'course' AND links.user_id = " . $primary_instructor_user_id . " AND links.course_id = " . $course_id;
            
            // include this uer's specific category settings
            $wheres[] = "links.type = 'category' AND links.user_id = " . $primary_instructor_user_id . " AND links.category_id = " . $category_id;
        }

        // flatten the where clause array
        $whereClause = array_reduce($wheres, function ($carry, $item) {
            $carry .= '(' . $item . ') OR ';
            return $carry;
        });
        
        // remove the final "or" from the where clause
        $whereClause = substr($whereClause, 0, -4);

        return $whereClause;
    }

}

/**
 * Helper function: Filter the array using the given Closure.
 *
 * @param  array     $array
 * @param  \Closure  $callback
 * @return array
 */
function array_where($array, Closure $callback)
{
    $filtered = [];
    
    foreach ($array as $key => $value) {
        if (call_user_func($callback, $key, $value)) $filtered[$key] = $value;
    }
    
    return $filtered;
}