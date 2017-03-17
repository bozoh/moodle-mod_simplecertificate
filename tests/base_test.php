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
 * Base class for unit tests for mod_simplecertificate.
 *
 * @package    mod_simplecertificate
 * @category   phpunit
 * @copyright  2013 onwards Carlos Alexandre S. da Fonseca  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
//require_once($CFG->dirroot . '/mod/simplecertificate/upgradelib.php');

/**
 * Unit tests for (some of) mod/simplecertificate/locallib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplecertificate_base_testcase extends advanced_testcase {

    /** @const Default number of students to create */
    const DEFAULT_STUDENT_COUNT = 3;
    /** @const Default number of teachers to create */
    const DEFAULT_TEACHER_COUNT = 2;
    /** @const Default number of editing teachers to create */
    const DEFAULT_EDITING_TEACHER_COUNT = 2;
    /** @const Optional extra number of students to create */
    const EXTRA_STUDENT_COUNT = 40;
    /** @const Optional number of suspended students */
    const EXTRA_SUSPENDED_COUNT = 10;
    /** @const Optional extra number of teachers to create */
    const EXTRA_TEACHER_COUNT = 5;
    /** @const Optional extra number of editing teachers to create */
    const EXTRA_EDITING_TEACHER_COUNT = 5;
    /** @const Number of groups to create */
    const GROUP_COUNT = 6;

    /** @var stdClass $course New course created to hold the certificates */
    protected $course = null;

    /** @var array $teachers List of DEFAULT_TEACHER_COUNT teachers in the course*/
    protected $teachers = null;

    /** @var array $editingteachers List of DEFAULT_EDITING_TEACHER_COUNT editing teachers in the course */
    protected $editingteachers = null;

    /** @var array $students List of DEFAULT_STUDENT_COUNT students in the course*/
    protected $students = null;

    /** @var array $extrateachers List of EXTRA_TEACHER_COUNT teachers in the course*/
    protected $extrateachers = null;

    /** @var array $extraeditingteachers List of EXTRA_EDITING_TEACHER_COUNT editing teachers in the course*/
    protected $extraeditingteachers = null;

    /** @var array $extrastudents List of EXTRA_STUDENT_COUNT students in the course*/
    protected $extrastudents = null;

    /** @var array $extrasuspendedstudents List of EXTRA_SUSPENDED_COUNT students in the course*/
    protected $extrasuspendedstudents = null;

    /** @var array $groups List of 10 groups in the course */
    protected $groups = null;

    /**
     * Setup function - we will create a course and add an simplecertificate instance to it.
     */
    protected function setUp() {
        global $DB;

        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course();
        $this->teachers = array();
        for ($i = 0; $i < self::DEFAULT_TEACHER_COUNT; $i++) {
            array_push($this->teachers, $this->getDataGenerator()->create_user());
        }

        $this->editingteachers = array();
        for ($i = 0; $i < self::DEFAULT_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->editingteachers, $this->getDataGenerator()->create_user());
        }

        $this->students = array();
        for ($i = 0; $i < self::DEFAULT_STUDENT_COUNT; $i++) {
            array_push($this->students, $this->getDataGenerator()->create_user());
        }

        $this->groups = array();
        for ($i = 0; $i < self::GROUP_COUNT; $i++) {
            array_push($this->groups, $this->getDataGenerator()->create_group(array('courseid'=>$this->course->id)));
        }

        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        foreach ($this->teachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                                                  $this->course->id,
                                                  $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'));
        foreach ($this->editingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                                                  $this->course->id,
                                                  $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        foreach ($this->students as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                                                  $this->course->id,
                                                  $studentrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
        }
    }

    /*
     * For tests that make sense to use alot of data, create extra students/teachers.
     */
    protected function create_extra_users() {
        global $DB;
        $this->extrateachers = array();
        for ($i = 0; $i < self::EXTRA_TEACHER_COUNT; $i++) {
            array_push($this->extrateachers, $this->getDataGenerator()->create_user());
        }

        $this->extraeditingteachers = array();
        for ($i = 0; $i < self::EXTRA_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->extraeditingteachers, $this->getDataGenerator()->create_user());
        }

        $this->extrastudents = array();
        for ($i = 0; $i < self::EXTRA_STUDENT_COUNT; $i++) {
            array_push($this->extrastudents, $this->getDataGenerator()->create_user());
        }

        $this->extrasuspendedstudents = array();
        for ($i = 0; $i < self::EXTRA_SUSPENDED_COUNT; $i++) {
            array_push($this->extrasuspendedstudents, $this->getDataGenerator()->create_user());
        }

        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        foreach ($this->extrateachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                                                  $this->course->id,
                                                  $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'));
        foreach ($this->extraeditingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                                                  $this->course->id,
                                                  $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        foreach ($this->extrastudents as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                                                  $this->course->id,
                                                  $studentrole->id);
            if ($i < (self::EXTRA_STUDENT_COUNT / 2)) {
                groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
            }
        }

        foreach ($this->extrasuspendedstudents as $i => $suspendedstudent) {
            $this->getDataGenerator()->enrol_user($suspendedstudent->id,
                                                  $this->course->id,
                                                  $studentrole->id, 'manual', 0, 0, ENROL_USER_SUSPENDED);
            if ($i < (self::EXTRA_SUSPENDED_COUNT / 2)) {
                groups_add_member($this->groups[$i % self::GROUP_COUNT], $suspendedstudent);
            }
        }
    }

    /**
     * Convenience function to create a testable instance of an simplecertificate.
     *
     * @param array $params Array of parameters to pass to the generator
     * @return testable_simplecertificate Testable wrapper around the simplecertificate class.
     */
    protected function create_instance($params = array(), $options = null) {
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
        $params['course'] = $this->course->id;
       
        $instance = $generator->create_instance($params, $options);
        $cm = get_coursemodule_from_instance('simplecertificate', $instance->id);
        $context = context_module::instance($cm->id);
        return new testable_simplecertificate($context, $cm, $this->course);
    }

    public function test_create_instance() {
        $this->assertNotEmpty($this->create_instance());
    }

}

/**
 * Test subclass that makes all the protected methods we want to test public.
 */
class testable_simplecertificate extends simplecertificate {

    const PLUGIN_VERSION = '2.2.2';

    /**
     * Overwrites parents to format $formdata
     * @see simplecertificate::update_instance()
     */
    public function update_instance(stdClass $instance) {
        global $CFG, $USER;
        
        //usercontext
        $user_context = context_user::instance($USER->id);
        
        // Draft fileinfo
        $fileinfo = array(
                'contextid' => $user_context->id,
                'component' => 'user',
                'filearea' => 'draft',
                'filepath' => '/'
        );
               
        $formdata = clone $instance;
        unset($formdata->certificatetext);
        unset($formdata->certificatetextformat);
        
               
        $formdata->certificatetext['text'] = $instance->certificatetext;
        $formdata->certificatetext['format'] = $instance->certificatetextformat;
        
        
        if (!empty($instance->secondpagetext)) {
            unset($formdata->secondpagetext);
            unset($formdata->secondpagetextformat);
            $formdata->secondpagetext['text'] = $instance->secondpagetext;
            $formdata->secondpagetext['format'] = $instance->secondpagetextformat;
        }
        
        $fs = get_file_storage();
        if (!empty($instance->certificateimage)) {
            $imagefileinfo = self::get_certificate_image_fileinfo($this->get_context()->id);
            $imagefile = $fs->get_file($imagefileinfo['contextid'], $imagefileinfo['component'], $imagefileinfo['filearea'], 
                                    $imagefileinfo['itemid'], $imagefileinfo['filepath'], $instance->certificateimage);
            
            $fileinfo['itemid'] = rand(1, 10);
            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
            $fs->create_file_from_storedfile($fileinfo, $imagefile);
            
            $formdata->certificateimage = $fileinfo['itemid'];
            $imagefileinfo = null;
        }
        
        if (!empty($instance->secondimage)) {
            $imagefileinfo = self::get_certificate_secondimage_fileinfo($this->get_context()->id);
            $imagefile = $fs->get_file($imagefileinfo['contextid'], $imagefileinfo['component'], $imagefileinfo['filearea'], 
                                    $imagefileinfo['itemid'], $imagefileinfo['filepath'], $instance->secondimage);
            
            $fileinfo['itemid'] = rand(11, 21);
            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
            $fs->create_file_from_storedfile($fileinfo, $imagefile);
            
            $formdata->secondimage = $fileinfo['itemid'];
            $imagefileinfo = null;
        }

        parent::update_instance($formdata);
    }
    
    //For mocking
    function check_user_can_access_certificate_instance($userid) {
         return $this->testable_check_user_can_access_certificate_instance($userid);
    }
    
    public function testable_check_user_can_access_certificate_instance($userid) {
        return  parent::check_user_can_access_certificate_instance($userid);
    }
   
    
    /**
	 * Prepare to print an activity grade.
	 *
	 * @param int $moduleid        	
	 * @param int $userid        	
	 * @return stdClass bool the mod object if it exists, false otherwise
	 */
	public function testable_get_mod_grade($moduleid, $userid) {
		return parent::get_mod_grade($moduleid, $userid);
	}
	
	
	
	 /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    public function testable_get_teachers() {
        return parent::get_teachers();
    }
    
    public function testable_create_pdf($issuecert, $pdf = null, $isbulk = false) {
    	return parent::create_pdf($issuecert, $pdf, $isbulk);
    }

    /**
     * This function returns success or failure of file save
     *
     * @param string $pdf is the string contents of the pdf
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return string with filename if successful, null otherwise
     */
    public function testable_save_pdf($issuecert) {
		return parent::save_pdf($issuecert);
	}

    /**
     * Sends the student their issued certificate from moddata as an email
     * attachment.
     *
     * @param stdClass $course
     * @param stdClass $certificate
     * @param stdClass $certrecord
     * @param stdClass $context
     */
    public function testable_send_certificade_email($issuecert) {
    	return parent::send_certificade_email($issuecert);
    }

    public function testable_get_issue_file ($issuecert) {
    	return parent::get_issue_file($issuecert);
    }
    
    public function testable_get_certificate_text($issuecert, $certtext = null) {
        return parent::get_certificate_text($issuecert, $certtext);
    }
    
    /**
     * Returns the date to display for the certificate.
     *
     * @param stdClass $certificate
     * @param stdClass $certrecord
     * @param stdClass $course
     * @param int $userid
     * @return string the date
     */
    public function testable_get_date($certissue, $userid = null) {
        return parent::get_date($certissue, $userid);
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     *
     * @return array
     */
    public function testable_get_outcomes() {
    	return parent::get_outcomes();
    }

    /**
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    public function testable_get_outcome($userid) {
    	return parent::get_outcome($userid);
    }

        
    /**
     * Verify if user meet issue conditions
     * 
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    public function testable_can_issue($user = null, $chkcompletation = true) {
    	return parent::can_issue($user, $chkcompletation);
    }
    
    /**
     * 
     * @param unknown $issuecertid
     * @param string $user
     * @return true if exist 
     * 
     */
    public function testable_issue_file_exists($issuecert) {
        if (!parent::issue_file_exists($issuecert)) {
            debugging('Issued certificate pathnamehash='.$issuecert->pathnamehash);
            return false;
        }
        return true;
    }

   	public function testable_get_issued_certificate_users ($sort="ci.timecreated ASC", $groupmode=0, $page = 0, $perpage = self::SIMPLECERT_MAX_PER_PAGE) {
   		return parent::get_issued_certificate_users($sort, $groupmode, $page, $perpage);
	}
    
}
