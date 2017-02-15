<?php
 
class local_cas_help_links_db_seeder {

    public $startDate;
    
    public $endDate;

    public $hoursLeft;

    public $db;
    
    public $urls;

    public $courses;

    public $courseCount;

    public $studentUsers;

    public $studentUserCount;

    public $links;

    public $linkCount;

    public function __construct() {
        global $DB;

        $this->db = $DB;
        $this->urls = include('../resources/sample_urls.php');
        $this->studentUsers = null;
        $this->studentUserCount = null;
        $this->links = null;
        $this->linkCount = null;
    }

    /**
     * Deletes all cas help link records
     * 
     * @return void
     */
    public function clearLinks()
    {
        $this->db->delete_records('local_cas_help_links');
    }

    /**
     * Deletes all cas help link log records
     * 
     * @return void
     */
    public function clearLogs()
    {
        $this->db->delete_records('local_cas_help_links_log');
    }

    /**
     * Inserts sample "category" cas help links into the DB
     * 
     * @return bool
     */
    public function seedCategoryLinks()
    {
        $amountAdded = 0;

        // get all course category ids
        foreach ($this->getCategories() as $category)
        {
            $categoryId = (int) $category->id;

            // 80% chance a category will have a link
            if (byChance(80)) {
                $this->insertLink('category', $categoryId);

                $amountAdded++;
            }
        }

        return $amountAdded;
    }

    /**
     * Inserts sample "course" cas help links into the DB
     * 
     * @return bool
     */
    public function seedCourseLinks()
    {
        $amountAdded = 0;

        // get all course ids
        foreach (get_courses() as $course)
        {
            $courseId = (int) $course->id;

            // 40% chance a course will have a link
            if (byChance(40)) {
                $this->insertLink('course', $courseId);

                $amountAdded++;
            }
        }

        return $amountAdded;
    }

    /**
     * Inserts sample "user" cas help links into the DB
     * 
     * @return bool
     */
    public function seedUserLinks()
    {
        $amountAdded = 0;

        // get all course ids
        foreach ($this->getInstructorUsers() as $user)
        {
            $userId = (int) $user->id;

            // 20% chance a course will have a link
            if (byChance(20)) {
                $this->insertLink('user', $userId);

                $amountAdded++;
            }
        }

        return $amountAdded;
    }

    /**
     * Inserts logging activity given a range of months into the DB
     * 
     * @param string $monthRangeString  ex: 2016-4,2017-2
     * @return bool
     */
    public function seedLog($monthRangeString)
    {
        $success = false;
        $tickDate = $this->startDate = $this->getDateFromString('start', $monthRangeString);
        $this->endDate = $this->getDateFromString('end', $monthRangeString);
        $this->hoursLeft = $this->getHoursLeft();

        // iterate through each hour in the range
        while ($this->hoursLeft > 0) {
            // calculate clicks this hour (between 0 - 100)
            $clicksThisHour = mt_rand(0, 100);

            foreach (range(1, $clicksThisHour) as $click) {
                // get a random user id
                $userId = $this->getRandomUserId();

                // get a random course id
                $courseId = $this->getRandomCourseId();

                // get a random link id
                $linkId = $this->getRandomLinkId();
                
                // @TODO - randomize the specific time portion of timestamp
                $this->insertLogRecord($userId, $linkId, $courseId, $tickDate->getTimestamp());
            }

            // add an hour to the current timestamp
            $tickDate->add(new DateInterval('PT1H'));
            
            $this->hoursLeft--;
            $success = true;
        }
        
        return $success;
    }

    /**
     * Returns a random course id,
     * also sets available course list and count if not already set
     * 
     * @return int
     */
    private function getRandomCourseId()
    {
        if (is_null($this->courses)) {
            // @TODO - make sure we're getting a real, active course
            $this->courses = array_values(get_courses());
            $this->courseCount = count($this->courses);
        }

        $course = $this->courses[mt_rand(0, $this->courseCount - 1)];

        return (int) $course->id;
    }

    /**
     * Returns a random student user id,
     * also sets available student user list and count if not already set
     * 
     * @return int
     */
    private function getRandomUserId()
    {
        if (is_null($this->studentUsers)) {
            $this->studentUsers = array_values($this->getStudentUsers());
            $this->studentUserCount = count($this->studentUsers);
        }

        $user = $this->studentUsers[mt_rand(0, $this->studentUserCount - 1)];

        return (int) $user->id;
    }

    /**
     * Returns a random cas help link id,
     * also sets available link list and count if not already set
     * 
     * @return int
     */
    private function getRandomLinkId()
    {
        if (is_null($this->links)) {
            $this->links = array_values($this->getLinks());
            $this->linkCount = count($this->links);
        }

        $link = $this->links[mt_rand(0, $this->linkCount - 1)];

        return (int) $link->id;
    }

    /**
     * Inserts a generated link record of the given type and id
     * 
     * @param  string $type  category|course|user
     * @param  int $id
     * @return int
     */
    private function insertLink($type, $id)
    {
        $identifier = $type . '_id';

        $link = new stdClass();
        $link->type = $type;
        $link->$identifier = $id;
        $link->display = (int) byChance(85);
        $link->link = $this->getRandomUrl();

        $id = $this->db->insert_record('local_cas_help_links', $link);

        return $id;
    }

    /**
     * Inserts a log record for the given parameters
     * 
     * @param  int $userId
     * @param  int $linkId
     * @param  int $courseId
     * @param  int $timestamp
     * @return int
     */
    private function insertLogRecord($userId, $linkId, $courseId, $timestamp)
    {
        $logRecord = new stdClass();
        $logRecord->user_id = $userId;
        $logRecord->link_id = $linkId;
        $logRecord->course_id = $courseId;
        $logRecord->time_clicked = $timestamp;

        $id = $this->db->insert_record('local_cas_help_links_log', $logRecord);

        return $id;
    }

    /**
     * Returns an array of objects containing category ids
     * 
     * @return array
     */
    private function getCategories()
    {
        $catIds = $this->db->get_records_sql('SELECT id FROM {course_categories} WHERE id != 1');
        
        return $catIds;
    }

    /**
     * Returns an array of objects containing primary instructor user ids
     * 
     * @return array
     */
    private function getInstructorUsers()
    {
        $result = $this->db->get_records_sql('SELECT DISTINCT u.id FROM {enrol_ues_teachers} t
            INNER JOIN {user} u ON u.id = t.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = t.sectionid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND cou.cou_number < "5000"
            AND t.primary_flag = "1"
            AND t.status = "enrolled"');

        return $result;
    }

    /**
     * Returns an array of objects containing student user ids
     * 
     * @return array
     */
    private function getStudentUsers()
    {
        $result = $this->db->get_records_sql('SELECT DISTINCT u.id FROM {enrol_ues_students} s
            INNER JOIN {user} u ON u.id = s.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = s.sectionid
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND s.status = "enrolled"');

        return $result;
    }

    /**
     * Returns all cas help link records
     * 
     * @return array
     */
    private function getLinks()
    {
        $result = $this->db->get_records('local_cas_help_links');

        return $result;
    }

    /**
     * Returns the difference in hours between the start and end date
     * 
     * @return int
     */
    private function getHoursLeft()
    {
        $interval = $this->startDate->diff($this->endDate);
        
        return (int) $interval->format('%a') * 24;
    }

    /**
     * Returns a specific datetime for the start or end of a given "month range string"
     * 
     * @param  string $date  start(default)|end
     * @param  string $rangeString  ex: 2016-4,2017-2
     * @return DateTime
     */
    private function getDateFromString($date = 'start', $rangeString)
    {
        list($start, $end) = explode(',', $rangeString);

        $day = $date == 'end' ? '28' : '1'; // @TODO - calculate real last day of month

        $time = $date == 'end' ? '11:59:59' : '00:00:00';

        $datetime = DateTime::createFromFormat('Y-n-j G:i:s', $$date . '-' . $day . ' ' . $time);

        return $datetime;
    }

    /**
     * Returns a random URL
     * 
     * @return string
     */
    private function getRandomUrl()
    {
        $key = mt_rand(0, 9999);

        return $this->urls[$key];
    }

}

/**
 * Helper function for determining true/false based on a given chance of being true
 * 
 * @param  int $pct  (ex: 40 = 40%)
 * @return bool
 */
function byChance($pct)
{
    return mt_rand(1, 100) <= $pct;
}