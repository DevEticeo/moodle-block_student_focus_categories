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
 * Block student_focus_categories
 *
 * @package     block_student_focus_categories
 * @copyright   2022 jan Eticeo <contact@eticeo.fr>
 * @author      2022 jan Guevara Gabrielle <gabrielle.guevara@eticeo.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

// Load libraries.
require_once($CFG->dirroot.'/course/renderer.php');
require_once($CFG->dirroot.'/lib/pagelib.php' );

use core_completion\progress;

define('BLOCK_STUDENT_FOCUS_CATEGORIES_DEFAULT', array('image'            => 1,
                                                        'sequence_name'   => 2,
                                                        'advancement'     => 3,
                                                        'spent_time'      => 4,
                                                        'final_grade'     => 5,
                                                        'obtained_badges' => 7));

class block_student_focus_categories extends block_base
{
    public function init()
    {
        $this->title = get_string('pluginname', 'block_student_focus_categories');
    }

    function has_config()
    {
        return true;
    }

    /**
     * Allow or disable the multiplication of this type of block
     * @return boolean
     */
    public function instance_allow_multiple()
    {
        return true;
    }

    /**
     * The block is usable in all pages
     * @return array
     */
    function applicable_formats()
    {
        return array(
            'all' => true
        );
    }

    /**
     * Hide or display the header
     * @return boolean
     */
    function hide_header()
    {
        return false;
    }

    function html_attributes() {
        $attributes = array(
            'id' => 'inst' . $this->instance->id,
            'class' => 'block_' . $this->name() . ' block ' . $this->bootstrap_size(),
            'role' => $this->get_aria_role()
        );
        if ($this->hide_header()) {
            $attributes['class'] .= ' no-header';
        }
        if ($this->instance_can_be_docked() && get_user_preferences('docked_block_instance_' . $this->instance->id, 0)) {
            $attributes['class'] .= ' dock_on_load';
        }
        return $attributes;
    }

    /**
     * Return the  bootstrap class of the block according to the size entered
     * @return string
     */
    public function bootstrap_size() {
        $space = !empty($this->config->space) ? $this->config->space : 12;

        return "col-sm-12 col-md-".$space." col-".$space;
    }

    /**
     * Return the content of the block customized with params
     * @return stdClass
     */
    public function get_content()
    {
        global $USER;

        $this->title = isset($this->config->title) && $this->config->title ? $this->config->title : get_string('config_title_default', 'block_student_focus_categories');

        if ($this->content !== null) {

            return $this->content;
        }

        $this->content = new stdClass;
        $userId = optional_param('userReplace', $USER->id, PARAM_INT);

        $hasEnableRole = $this->is_user_enabled($userId);
        if ($hasEnableRole) {
            $this->content->text = '';
            // Categories cards
            if (isset($this->config->eticeo_categories)) {
                $categoriesCards = $this->get_categories_cards($this->config->eticeo_categories);
                if (!$categoriesCards) {
                    $categoriesCards = get_string('no_course_at_all', 'block_student_focus_categories');
                }
                $this->content->text .= '<div class="container mt80"><div class="row student-focus-categories-block">'.$categoriesCards.'</div></div>';
                $this->content->footer = '';
            }
        } else {
            $this->title = $this->title.' <i>'.get_string('hidden_for_user', 'block_student_focus_categories').'</i>';
        }

        return $this->content;
    }


    /**************************************
     *        ACCESS FUNCTIONS
     *************************************/

    /**
     * Return true if the user has the right to see this block
     * @param $userId     | id of the selected user
     * @return bool
     * @throws dml_exception
     */
    private function is_user_enabled($userId) {
        global $DB, $CFG;

        $hasEnableRole = false;
        $user_roles = isset($this->config->user_role) ? $this->config->user_role : null;
        if (!empty($user_roles)) {
            //enabled for everybody
            if (in_array(0, $user_roles)) {

                return true;
            }
            //manager
            if (in_array(1, $user_roles)) {
                $admins = explode(',', $CFG->siteadmins);
                if (in_array($userId, $admins)) {

                    return true;
                }
            }

            $hasEnableRole = $DB->get_records_sql('SELECT ra.id as raid, u.id FROM {user} u 
                                                   INNER JOIN {role_assignments} ra ON u.id = ra.userid
                                                   WHERE ra.roleid IN (:userroles) 
                                                   AND userid = :userid',
                                                    array('userroles' => implode(',', $user_roles),
                                                         'userid' => $userId));

            $hasEnableRole = !empty($hasEnableRole);
        }

        return $hasEnableRole;
    }

    /**************************************
     *        END ACCESS FUNCTIONS
     *************************************/

    /**
     * Return true if the user is enabled to see this category because is in the enabled cohorts
     * @param $cohorts   | string cohort id separated by commas
     *
     * @return bool
     */
    private function user_in_cohorts($cohorts) {
        global $USER, $DB;

        $userId = optional_param('userReplace', $USER->id, PARAM_INT);
        $cohorts = explode(',', $cohorts);
        foreach ($cohorts as $key => $cohort) {
            if ($cohort == '') {
                unset($cohorts[$key]);
            }
        }
        if (!empty($cohorts)) {
            $inCohort = $DB->get_records_sql('SELECT cohortid FROM {cohort_members} WHERE cohortid in (:cohortlist) AND userid = :userid',
                                                array('cohortlist' => implode(',', $cohorts), 'userid' => $userId));

            return !empty($inCohort);
        }

        return false;
    }

    /**
     * Function which return all the categories card
     * @param $configCategories | array all the categories => $this->config->eticeo_categories
     *
     * @return string
     */
    private function get_categories_cards($categoriesDataList)
    {
        global $USER;

        $userId = optional_param('userReplace', $USER->id, PARAM_INT);

        $categoriesList = array();

        foreach ($categoriesDataList as $idCat => $catData) {
            $catData = json_decode($catData);
            if (isset($catData->order) && $catData->order > 0 && isset($catData->cohorts) && $this->user_in_cohorts($catData->cohorts)) {
                $categoriesList[$catData->order] = $idCat;
            }
        }
        ksort($categoriesList);
        
        if (!empty($categoriesList)) {
            $courseList = array();
            $enrol_get_all_users_courses = enrol_get_all_users_courses($userId, false, array('*'));
            foreach ($enrol_get_all_users_courses as $course) {
                $courseList[$course->id] = $course->id;
            }
        } else {

            return null;
        }
        $text = '';
        foreach ($categoriesList as $idCat) {
            $subcat = core_course_category::get($idCat, IGNORE_MISSING);
            if ($subcat) {
                if (!isset($courseList[$idCat])) {
                    $courseList[$idCat] = array();
                }
                $text .= $this->get_sub_category_card($subcat, $courseList, $userId);
            }
        }

        return $text;
    }


    /**
     * Function which create a card for a sub category
     * @param $subcat        | object core_course_category sub categorie which we want to display on a categorie card
     * @param $courseList    | array list of the courses
     * @param $userId        | int id of the current user
     *
     * @return string the html code
     */
    private function get_sub_category_card($subcat, $courseList, $userId)
    {
        $progressionCompleted = 0;
        if ($this->config->display_progression || $this->config->display_success_change) {
            $progression = $this->get_progression_from_category($subcat, $userId);
            $progressionCompleted = $progression >= 100;
        }

        $sub_category_card = '<div class="category-homepage-list category-'.$subcat->id.'" > 
                                <div class="row">
                                    <div class="col-12">
                                        <h2 class="category-num-category">'.
                                            $subcat->name.
                                        '</h2>
                                    </div>
                                </div>';
        $sub_category_card .= $this->get_category_details($subcat->id, $courseList);
        $sub_category_card .= '</div>';

        return $sub_category_card;
    }


    /**
     * Build a table with the information of each category's courses
     * @param $idCategory      | int id of the category
     * @param $userCourses     | array list of the user's courses
     *
     * @return string the category
     */
    private function get_category_details($idCategory, $userCourses) {
        global $DB, $USER, $PAGE, $CFG;

        require_once ($CFG->dirroot .'/config.php');

        $userId = optional_param('userReplace', $USER->id, PARAM_INT);

        require_once ($CFG->libdir . "/badgeslib.php");
        require_once ($CFG->dirroot . '/mod/quiz/locallib.php');

        //Array for sorting by column
        $sortArray = array();
        //Array of table titles
        $coursesTitles = array();
        //Array of rows in the table
        $coursesList = array();
        $student_focus_categories_params = $this->config->student_focus_categories_params;
        natsort($student_focus_categories_params);
        /* TABLE TITLES */
        foreach ($student_focus_categories_params as $student_focus_categories_param => $num)
        {
            if ($num > 0) {
                $isPrimary = $student_focus_categories_param == "sequence_name" || $student_focus_categories_param == "image" ? 'true' : 'false';
                $coursesTitles[$student_focus_categories_param] = array('name' => get_string($student_focus_categories_param, 'block_student_focus_categories'),
                                                                        'isPrimary' => $isPrimary);
                $sortArray[$student_focus_categories_param] = array();
            }
        }

        /* SPENT TIME TABLE EXIST ? */
        if (isset($student_focus_categories_params['spent_time']) && $student_focus_categories_params['spent_time']) {
            $spentTimeExist = $this->check_if_table_exists('user_stats_time');
            //if the table doesn't exist, we don't display the column spent time
            if (!$spentTimeExist) {
                unset($coursesTitles['spent_time']);
            }
        }

        // We get the courses of the category in parameter
        $subcat = core_course_category::get($idCategory, IGNORE_MISSING);
        $courses = $subcat->get_courses();

        /* BADGES */
        if (isset($student_focus_categories_params['obtained_badges']) && $student_focus_categories_params['obtained_badges']) {
            $totalBadges = 0;
            $badgesArray = array();
            foreach ($courses as $course) {
                if (in_array($course->id, $userCourses)) {
                    $courseBadges = badges_get_badges(BADGE_TYPE_COURSE, $course->id, '', '', 0, BADGE_PERPAGE, $userId);
                    $totalBadges = max($totalBadges, count($courseBadges));
                    $badgesArray[$course->id] = $courseBadges;
                }
            }
            //if we doesn't have any available badges, we doesn't show the column
            if (!$totalBadges) {
                unset($coursesTitles['obtained_badges']);
            } else {
                $maxBadges = isset($student_focus_categories_params['maxbadges']) ? $student_focus_categories_params['maxbadges'] : null;
                if ($maxBadges === null) {
                    $maxBadges = $totalBadges;
                }
                $maxBadges = (int)$maxBadges;
            }
        }
        $defaultPreview = $this->get_default_preview();
        // we search the data of each course
        foreach ($courses as $course) {
            if (in_array($course->id, $userCourses)) {
                //we reset the variables
                $contentimages = $progressDonut = $finalGrade = $obtainedBadgesHtml = '';
                $spentTime = 0;
                $coursesList[$course->id] = array();
                foreach ($student_focus_categories_params as $student_focus_categories_param => $num) {
                    if ($num > 0) {
                        switch ($student_focus_categories_param) {
                            case 'sequence_name':
                                /****************************
                                 *       SEQUENCE NAME      *
                                 ****************************/
                                $sortArray['sequence_name'][$course->fullname . '-' . $course->id] = $course->id;

                                // SEQUENCE NAME COLUMN
                                $coursesList[$course->id]['sequence_name'] = $course->fullname;
                                break;
                            case 'image':
                                /****************************
                                 *           IMAGE          *
                                 ****************************/
                                $contentimages = $defaultPreview;
                                foreach ($course->get_course_overviewfiles() as $file) {
                                    $courseTitle = '';
                                    //Image du cours
                                    $isimage = $file->is_valid_image();
                                    $url = file_encode_url("{$CFG->wwwroot}/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
                                    if ($isimage) {
                                        $contentimages = '<img class="img-course" alt="' . $courseTitle . '" src="' . $url . '"/>';
                                    }
                                }
                                // IMAGE COLUMN
                                $coursesList[$course->id]['image'] = $contentimages;
                                break;
                            case 'advancement':
                                /****************************
                                 *     ADVANCEMENT (donut)  *
                                 ****************************/
                                //if the plugin local_progression_save exists, we will take the data in the table progression_save
                                if ($this->check_if_table_exists('progression_save')) {
                                    $sql = "SELECT id, progression FROM {progression_save} WHERE userid = :userid AND courseid = :courseid";
                                    $res = $DB->get_records_sql($sql, array('userid' => $userId, 'courseid' => $course->id));

                                    if ($res) {
                                        $progressFloored = current($res)->progression;
                                    } else {
                                        $progressFloored = null;
                                    }
                                } else {
                                    //if the plugin local_progression_save isn't install, we call the moodle function
                                    $progressFloored = progress::get_course_progress_percentage($course, $userId);
                                }
                                $progress = floor($progressFloored);
                                $progressDonut = $this->get_progression_donut($progress, $this->config->eticeo_style['progression_color']);

                                $sortArray['advancement'][$progress . '-' . $course->id] = $course->id;

                                // ADVANCEMENT COLUMN
                                $coursesList[$course->id]['advancement'] = $progressDonut;
                                break;
                            case 'spent_time':
                                /*************************
                                 *       SPENT TIME      *
                                 *************************/
                                if ($spentTimeExist) {
                                    $hour = $minutes = 0;
                                    $sql = "select SUM(timetot) as timetotcourse FROM {user_stats_time} WHERE userid = :userid AND courseid = :courseid";
                                    $course_estimate_column_data = $DB->get_record_sql($sql, array('userid' => $userId, 'courseid' => $course->id));
                                    $spentTimeSecondes = $course_estimate_column_data->timetotcourse;
                                    if ($spentTimeSecondes > 0) {
                                        $minutes = (int)($spentTimeSecondes / 60);
                                        $hour = (int)($minutes / 60);
                                        $minutes -= $hour * 60;
                                    }
                                    $spentTime = $hour . ' h ' . sprintf('%02d', $minutes) . ' min';
                                    $sortArray['spent_time'][$spentTimeSecondes . '-' . $course->id] = $course->id;

                                    // SPENT TIME COLUMN
                                    $coursesList[$course->id]['spent_time'] = $spentTime;
                                }
                                break;
                            case 'final_grade':
                                /*************************
                                 *       FINAL NOTE      *
                                 *************************/
                                $finalGrade = '<span class="grade-unavailable"> - </span>';
                                $quizzesCourse = get_course($course->id);
                                $quizzes = get_all_instances_in_courses("quiz", array($course->id => $quizzesCourse), $userId);
                                if (!empty($quizzes)) {
                                    $quiz = array_pop($quizzes);
                                    $quizobj = \quiz::create($quiz->id, $userId);
                                    $quizz = $quizobj->get_quiz();
                                    //we take all the grade the user had
                                    $studentAttempts = quiz_get_user_attempts($quizz->id, $userId, 'finished', true);
                                    if (!empty($studentAttempts)) {
                                        $maxGrade = 0;
                                        foreach ($studentAttempts as $attempt) {
                                            $grade = quiz_rescale_grade($attempt->sumgrades, $quizz, false);
                                            //we take the best grade the user has for this quizz
                                            $maxGrade = $maxGrade > $grade ? $maxGrade : $grade;
                                        }
                                        //we put it into percentage
                                        if ($quizz->grade > 0) {
                                            $finalGrade = ceil(($maxGrade / $quizz->grade) * 100) . '%';
                                        }
                                        $sortArray['final_grade'][$maxGrade . '-' . $course->id] = $course->id;
                                    }
                                }
                                // FINAL NOTE COLUMN
                                $coursesList[$course->id]['final_grade'] = $finalGrade;
                                break;
                            case 'obtained_badges':
                                /****************************
                                 *          BADGES          *
                                 ****************************/
                                if ($totalBadges) {
                                    $courseBadges = $badgesArray[$course->id];

                                    $obtainedBadgesHtml = '';
                                    $numBadges = $numBadgesObtained = 0;
                                    foreach ($courseBadges as $badge) {
                                        if ($numBadges < $maxBadges) {
                                            if ($badge->dateissued) {
                                                $class = 'badge-obtained';
                                            } else {
                                                $class = 'badge-not-obtained';
                                                $numBadgesObtained++;
                                            }
                                            $obtainedBadgesHtml .= '<span class="' . $class . '">' . print_badge_image($badge, $badge->get_context()) . '</span>';
                                            $numBadges++;
                                        }
                                    }
                                    $unavailablesBadges = min($maxBadges, $totalBadges) - $numBadges;
                                    if ($unavailablesBadges > 0) {
                                        $obtainedBadgesHtml .= str_repeat('<span class="badge-unavailable"> - </span>', $unavailablesBadges);
                                    }
                                    $sortArray['obtained_badges'][$numBadgesObtained . '-' . $course->id] = $course->id;

                                    // OBTAINED BADGES COLUMN
                                    $coursesList[$course->id]['obtained_badges'] = $obtainedBadgesHtml;
                                }
                                break;
                        }
                    }
                }
            }
        }
        if (empty($coursesList)) {
            return '<i>'.get_string('no_course', 'block_student_focus_categories').'</i>';
        }

        // Table for sorting columns
        foreach ($sortArray as $key => $array) {
            $sortFlag = SORT_NUMERIC;
            if ($key == 'sequence_name') {
                $sortFlag = SORT_STRING;
            }
            ksort($sortArray[$key], $sortFlag);
            $array = array_flip($sortArray[$key]);
            $array = array_keys($array);
            $sortArray[$key] = array_flip($array);
        }

        $tableHeader = '';
        foreach ($coursesTitles as $id => $title) {
            $tableHeader .= '<th class="col_'.$id.'" data-primary="'.$title['isPrimary'].'">';
            if ($id != 'image') {
                $tableHeader .= '<i class="fa fa-sort" onclick="studentFocusCategories_sortTable(\''.$id.'\', true, '.$idCategory.')"></i><span>'.$title['name'].'</span>';
            }
            $tableHeader .= '</th>';
        }
        $table = '';

        foreach ($coursesList as $courseid => $course) {
            $row = '';
            foreach ($coursesTitles as $param => $title) {
                $row .='<td class="col_'.$param.'" data-primary="'.$title['isPrimary'].'"';
                if ($param != 'image' && isset($sortArray[$param][$courseid])) {
                    $row .=' data-sortvalue="'.$sortArray[$param][$courseid].'"';
                }
                $row .='><span onclick="studentFocusCategories_openCourse('.$courseid .')" class="'.$param.'_data">'.(isset($course[$param]) ? $course[$param] : '').'</span>';
                if ($param == 'sequence_name') {
                    $row .='<br/><button onclick="studentFocusCategories_deployCourse('.$courseid .')" class="deployCourse">'.
                                        get_string('button_deploy_course_data', 'block_student_focus_categories').'
                                 </button>
                                 <button onclick="studentFocusCategories_foldCourse('.$courseid .')" class="foldCourse">'.
                                        get_string('button_fold_course_data', 'block_student_focus_categories').'
                                 </button>';
                }
                $row .='</td>';
            }
            $table .= '<tr class="course-line-'.$courseid.'">'.$row.'</tr>';
        }
        $table = '<table class="courses-table">
                          <thead>
                              <tr>'.$tableHeader.'</tr>
                          </thead>'.$table.'
                    </table>';
        $PAGE->requires->js('/blocks/student_focus_categories/js/student-focus-categories.js');

        return $table;
    }


   /**
    * search if an image or a video is recorded, put it in the good format and return it
    * @return string html of the image or the video by default
    */
    private function get_default_preview()
    {
        $syscontext = context_system::instance();
        $fs = get_file_storage();

        $filesArray =  $fs->get_area_files($syscontext->id, 'block_student_focus_categories', 'content');
        if (count($filesArray) > 0) {
            $file = array_pop($filesArray);
            $pathname = $file && isset($file->pathname) ? $file->pathname : '';
            $filename = $file && isset($file->filename) ? $file->filename : '';
	        $imageUrl = moodle_url::make_pluginfile_url($syscontext->id, 'block_student_focus_categories', 'content', $file->get_id(), $pathname, $filename);

            return '<img src="'.$imageUrl.'">';
        }

        return '';
    }

   /**
    * Calculate the progression rate of the user in the category's courses
    * @param $subcat  | object core_course_category (sub category)
    * @param $userId  | int id of the user
    *
    * @return int progression rate
    */
    private function get_progression_from_category($subcat, $userId)
    {
        global $DB;

        $courses = $subcat->get_courses();

        $progressTotal = 0;
        $countCourses = count($courses);
        if ($countCourses > 0) {
            //if the plugin local_progression_save exists, we will take the data in the table progression_save
            if ($this->check_if_table_exists('progression_save')) {
                //Here we get a table with all the information about the progressions
                $courseIdList = implode(', ', array_map(function ($c) {
                    return $c->id;
                }, $courses));
                $studentIdList = $userId;

                $sql = "SELECT courseid,AVG(progression) as moyprogress
                    FROM {progression_save} 
                    WHERE userid in (:liststudents) AND courseid in (:listcourses) 
                    GROUP BY courseid";
                $res = $DB->get_records_sql($sql, array('liststudents' => $studentIdList, 'listcourses' => $courseIdList));

                if ($res) {
                    $progressTotal = current($res)->moyprogress;
                } else {
                    $progressTotal = 0;
                }
            } else {
                //if the plugin local_progression_save isn't install, we call the moodle function
                $progressTotal = 0;
                foreach ($courses as $course) {
                    $progressTotal += \core_completion\progress::get_course_progress_percentage($course);
                }
                $progressTotal = ceil($progressTotal / $countCourses);
            }
        }

        return $progressTotal;
    }


    /**************************************
     *        UTILS FUNCTIONS
     *************************************/

    /**
     * search if an image or a video is recorded, put it in the good format and return it
     * @param $progressTotal  | int user progression in the category
     *
     * @return string html of the image or the video by default
     */
    function get_progression_donut($progressTotal, $donutColor)
    {
        $progression_donut = '<svg viewbox="0 0 120 120" class="category_progression">
                                 <circle cx="60" cy="60" r="50" style="fill: white;"/>
                                      <path fill="none" stroke-linecap="butt" stroke-width="20" stroke="'.$donutColor.'54"
                                            stroke-dasharray="250, 0"
                                            d="M60 20 a 40 40 0 0 1 0 80 a 40 40 0 0 1 0 -80"/>
    
                                      <path fill="none" stroke-linecap="'.($progressTotal>= 100 ? 'square' : 'butt').'" stroke-width="20" stroke="'.$donutColor.'"
                                            stroke-dasharray="'.(250 * $progressTotal / 100).', 250"
                                            d="M60 20 a 40 40 0 0 1 0 80 a 40 40 0 0 1 0 -80"/>
                                      <text x="60" y="60" text-anchor="middle" dy="7" font-size="20" stroke="#000">'.$progressTotal.'%</text>
                                  </svg>';

        return $progression_donut;
    }

    /**
     * Return true if the table exist
     * @param $tableName     | string name of the table
     * @return bool
     */
    private function check_if_table_exists($tableName)
    {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table($tableName);

        return $dbman->table_exists($table);
    }

    /**************************************
     *        END UTILS FUNCTIONS
     *************************************/
}