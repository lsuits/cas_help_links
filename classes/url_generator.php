<?php
 
class local_cas_help_links_url_generator {

    /**
     * Returns an array that includes data about the appropriate CAS Help link to be displayed for this course/user
     * 
     * @param  object $course  moodle course object
     * @param  bool $editLinkForInstructor  if true, will return a link to edit this setting
     * @return array  display|url|label
     */
    public static function getUrlArrayForCourse($course, $editLinkForInstructor = true)
    {
        // if this plugin is disabled, do not display
        if ( ! self::isPluginEnabled())
            return self::getEmptyUrlArray();
        
        $course_id = $course->id;
        $category_id = $course->category;

        // if we can't find a primary instructor for the given course, do not display
        if ( ! $primary_instructor_user_id = self::getPrimaryInstructorId($course->idnumber)) {
            return self::getEmptyUrlArray();
        }

        // if primary instructor is requesting
        if ($primary_instructor_user_id == self::getAuthUserId() && $editLinkForInstructor) {
            // return edit link
            return self::getCourseEditHelpUrlArray($course);
        } else {
            //  otherwise return link pref data
            return self::getDisplayHelpUrlArray($course_id, $category_id, $primary_instructor_user_id);
        }
    }

    /**
     * Returns a appropriate URL for editting CAS help link settings
     * 
     * @param  object $course  moodle course object
     * @return string
     */
    private static function getCourseEditHelpUrlArray($course)
    {
        $urlArray = [
            'display' => true,
            'url' => '/local/cas_help_links/user_settings.php?id=' . self::getAuthUserId(), // @TODO - make this happen
            'label' => get_string('settings_button_label', 'local_cas_help_links'),
        ];

        return $urlArray;
    }

    /**
     * Returns the preferred help link URL array for the given parameters
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return array
     */
    private static function getDisplayHelpUrlArray($course_id, $category_id, $primary_instructor_user_id)
    {
        // get appropriate pref from db
        if ( ! $selectedPref = self::getSelectedPref($course_id, $category_id, $primary_instructor_user_id)) {
            // if no pref can be resolved, return default settings using system config
            $urlArray = self::getDefaultHelpUrlArray();
        } else {
            // otherwise, convert the selected pref result to a single object
            $selectedPref = reset($selectedPref); // @WATCH - should be no multiple results confusion here

            $urlArray = [
                'display' => $selectedPref->display,
                'url' => $selectedPref->link,
                'label' => get_string('help_button_label', 'local_cas_help_links'),
                'link_id' => $selectedPref->id,
            ];
        }

        return $urlArray;
    }

    /**
     * Returns the default help url settings as array
     * 
     * @return array
     */
    private static function getDefaultHelpUrlArray()
    {
        return [
            'display' => self::isPluginEnabled(),
            'url' => get_config('local_cas_help_links', 'default_help_link'),
            'label' => get_string('help_button_label', 'local_cas_help_links'),
            'link_id' => ''
        ];
    }

    /**
     * Retrieves the appropriate pref according to override hierarchy
     * 
     * @param  int  $course_id
     * @param  int  $category_id
     * @param  int  $primary_instructor_user_id
     * @return mixed array|bool
     */
    private static function getSelectedPref($course_id, $category_id, $primary_instructor_user_id)
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
     * Returns the currently authenticated user id
     * 
     * @return int
     */
    private static function getAuthUserId() {
        global $USER;
        
        return $USER->id;
    }

    /**
     * Returns a "primary instructor" user id given a course id number
     * 
     * @param  string $idnumber
     * @return int
     */
    private static function getPrimaryInstructorId($idnumber)
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
     * Returns whether or not this plugin is enabled based off plugin config
     * 
     * @return boolean
     */
    private static function isPluginEnabled()
    {
        return (bool) get_config('local_cas_help_links', 'show_links_global');
    }

    /**
     * Returns a default, "empty" URL array
     * 
     * @return array
     */
    private static function getEmptyUrlArray()
    {
        return [
            'display' => false,
            'url' => '',
            'label' => '',
            'link_id' => 0
        ];
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