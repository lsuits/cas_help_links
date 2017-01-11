<?php
 
class local_cas_help_links_utility {

    /**
     * Returns an array of this primary instructor user's course settings data
     * 
     * @param  int  $user_id
     * @return array|string
     */
    public static function get_primary_instructor_course_settings($user_id)
    {
        $courseData = self::get_primary_instructor_course_data($user_id);

        $transformedCourseData = self::transform_course_data($courseData, $user_id);

        return $transformedCourseData;
    }
    
    /**
     * Returns an array of this primary instructor user's category settings data
     * 
     * @param  int  $user_id
     * @return array|string
     */
    public static function get_primary_instructor_category_settings($user_id)
    {
        $categoryData = self::get_primary_instructor_category_data($user_id);

        $transformedCategoryData = self::transform_category_data($categoryData, $user_id);

        return $transformedCategoryData;
    }

   /**
     * Returns an array of this primary instructor user's personal settings data
     * 
     * @param  int  $user_id
     * @return array|string
     */
    public static function get_primary_instructor_user_settings($user_id)
    {
        $userLink = self::get_user_link_data($user_id);

        $transformedUserData = self::transform_user_data($userLink, $user_id);

        return $transformedUserData;
    }

    /**
     * Fetches the given primary's current course data
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_primary_instructor_course_data($user_id)
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

        return $result;
    }

    /**
     * Fetches the given primary's current category data
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_primary_instructor_category_data($user_id)
    {
        global $DB;

        $result = $DB->get_records_sql('SELECT DISTINCT u.id, cc.id, cc.name FROM mdl_enrol_ues_teachers t
            INNER JOIN mdl_user u ON u.id = t.userid
            INNER JOIN mdl_enrol_ues_sections sec ON sec.id = t.sectionid
            INNER JOIN mdl_course c ON c.idnumber = sec.idnumber
            INNER JOIN mdl_course_categories cc ON cc.id = c.category
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND t.primary_flag = "1"
            AND t.status = "enrolled"
            AND u.id = ?', array($user_id));

        return $result;
    }

    /**
     * Fetches a cas_help_link object as an array for the given user
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_link_data($user_id)
    {
        $result = self::get_links('user', $user_id);

        $result = count($result) ? current($result) : [];

        return $result;
    }

    /**
     * Returns an array of the given course data array but including 'cas_help_link' information
     * 
     * @param  array $courseData
     * @return array
     */
    private static function transform_course_data($courseData, $user_id)
    {
        $output = [];

        $userCourseLinks = self::get_user_course_link_data($user_id);

        foreach ($courseData as $courseArray) {
            $course = get_course($courseArray->id);

            $linkExistsForCourse = array_key_exists($courseArray->id, $userCourseLinks);

            $isChecked = $linkExistsForCourse ? $userCourseLinks[$course->id]->display : true;

            $output[$course->id] = [
                'user_id' => $user_id,
                'course_id' => $course->id,
                'course_fullname' => $course->fullname,
                'course_shortname' => $course->shortname,
                'course_idnumber' => $course->idnumber,
                'link_id' => $linkExistsForCourse ? $userCourseLinks[$course->id]->id : '0',
                'link_display' => $linkExistsForCourse ? $userCourseLinks[$course->id]->display : '0',
                'link_checked' => $isChecked ? 'checked' : '',
                'link_url' => $linkExistsForCourse ? $userCourseLinks[$course->id]->link : '',
            ];
        }

        return $output;
    }

    /**
     * Returns an array of the given category data array but including 'cas_help_link' information
     * 
     * @param  array $categoryData
     * @return array
     */
    private static function transform_category_data($categoryData, $user_id)
    {
        $output = [];

        $userCategoryLinks = self::get_user_category_link_data($user_id);

        foreach ($categoryData as $categoryArray) {
            $category = self::get_category($categoryArray->id);

            $linkExistsForCategory = array_key_exists($categoryArray->id, $userCategoryLinks);

            $isChecked = $linkExistsForCategory ? $userCategoryLinks[$category->id]->display : true;

            $output[$category->id] = [
                'user_id' => $user_id,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'link_id' => $linkExistsForCategory ? $userCategoryLinks[$category->id]->id : '',
                'link_display' => $linkExistsForCategory ? $userCategoryLinks[$category->id]->display : '',
                'link_checked' => $isChecked ? 'checked' : '',
                'link_url' => $linkExistsForCategory ? $userCategoryLinks[$category->id]->link : '',
            ];
        }

        return $output;
    }

    /**
     * Returns an array of the given user data but including 'cas_help_link' information
     * 
     * @param  mixed $link
     * @return array
     */
    private static function transform_user_data($link, $user_id)
    {
        $isChecked = is_object($link) ? $link->display : true;

        return [
            'user_id' => $user_id,
            'link_id' => is_object($link) ? $link->id : '',
            'link_display' => is_object($link) ? $link->display : '',
            'link_checked' => $isChecked ? 'checked' : '',
            'link_url' => is_object($link) ? $link->link : '',
        ];
    }

    /**
     * Returns an array of this user's course link preferences, if any, keyed by the course_id
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_course_link_data($user_id)
    {
        // pull raw cas_help_links records
        $userCourseLinks = self::get_user_course_links($user_id);

        $output = [];

        // re-key array with course_id instead of link record id
        foreach ($userCourseLinks as $linkId => $linkData) {
            $output[$linkData->course_id] = $linkData;
        }

        return $output;
    }

    /**
     * Returns an array of this user's category link preferences, if any, keyed by the category_id
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_category_link_data($user_id)
    {
        // pull raw cas_help_links records
        $userCategoryLinks = self::get_user_category_links($user_id);

        $output = [];

        // re-key array with category_id instead of link record id
        foreach ($userCategoryLinks as $linkId => $linkData) {
            $output[$linkData->category_id] = $linkData;
        }

        return $output;
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's courses
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_course_links($user_id)
    {
        return self::get_links('course', $user_id);
    }

    /**
     * Fetches an array of cas_help_link objects for the given user's categories
     * 
     * @param  int $user_id
     * @return array
     */
    private static function get_user_category_links($user_id)
    {
        return self::get_links('category', $user_id);
    }

    /**
     * Fetches an array of cas_help_link objects of a given type for the given user
     * 
     * @param  string $type
     * @param  int $user_id
     * @return object
     */
    private static function get_links($type, $user_id)
    {
        global $DB;

        $result = $DB->get_records('local_cas_help_links', [
            'type' => $type,
            'user_id' => $user_id,
        ]);

        return $result;
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

    /**
     * Returns a moodle course_category object for the given id
     * 
     * @param  int $category_id
     * @return object
     */
    private static function get_category($category_id)
    {
        global $DB;

        $result = $DB->get_record('course_categories', ['id' => $category_id]);

        return $result;
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