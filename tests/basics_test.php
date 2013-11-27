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
 * Genarator tests class.
 *
 * @package    mod_simplecertificate
 * @copyright  2013 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author	   Carlos Alexandre S. da Fonseca
 * @group	   simplecertificate_basics	
 */
class mod_simplecertificate_basic_testcase extends advanced_testcase {
	//http://docs.moodle.org/dev/Writing_PHPUnit_tests
	private $course;
	private $student_account;

	
	public static function setUpBeforeClass() {
	
		
	}
	
	public function setUp() {
		global $DB;
		
		$this->resetDebugging();
		$this->setAdminUser();
		$this->course = $this->getDataGenerator()->create_course();
		$this->student_account = $this->getDataGenerator()->create_user();
		if($student_role=$DB->get_record('role', array('shortname'=>'student'))){
			$this->getDataGenerator()->enrol_user($this->student_account->id, $this->course->id,$student_role->id);
		} else {
			throw new coding_exception("No student role");
		} 
			
	
		
	}
	
    public function test_create_certificate_instance() {
        global $DB;
        $this->resetAfterTest();

        //Basic CRUD test
        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $this->course->id)));
        $cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id));
        $this->assertEquals(1, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertTrue($DB->record_exists('simplecertificate', array('course' => $this->course->id, 'id' => $cert->id)));

        $params = array('course' => $this->course->id, 'name' => 'One more certificate');
        $cert = $this->getDataGenerator()->create_module('simplecertificate', $params);
        $this->assertEquals(2, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertEquals('One more certificate', $DB->get_field_select('simplecertificate', 'name', 'id = :id', array('id' => $cert->id)));
    }
    
    public function test_create_issue_instance() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");
    	
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id));
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	//No certificate is issued
    	$this->assertFalse($DB->record_exists("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	//Has issued
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	
    	//Issuing using $USER
    	$this->setUser($this->student_account);
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert));
    	//Has issued
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->id)));
    }
    
    public function test_create_issue_code() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id));
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	    	
    	//Verify code
    	$this->assertNotEmpty($issuecert->code);
    	$this->assertEquals(36, strlen($issuecert->code));
    	$this->assertEquals($issuecert->id, $DB->get_record('simplecertificate_issues', array('code'=>$issuecert->code),"id")->id);    	
    }
    
    public function  test_pdf_file() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");
    	
    	
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id ));
    	    	
   		 if ($cert->delivery != 3 ) {
    		$this->markTestSkipped("Certificate delivery option must be 3");
    	} 
    	$simplecerticate = new simplecertificate($cert);
    		
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	//Verify if file DON´T EXISTS
    	$fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuecert, null);
    	$fs = get_file_storage();
    	$this->assertFalse($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	
    	//Creating file
    	$this->redirectEmails();
    	$simplecerticate->output_pdf($issuecert);
    	$this->assertTrue($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	
    	//Issue as admin
    	$this->setAdminUser();
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert));
    	//Verify if file DON´T EXISTS
    	$fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuecert, null);
    	$fs = get_file_storage();
    	$this->assertFalse($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	 
    	//Creating file
    	$this->redirectEmails();
    	$simplecerticate->output_pdf($issuecert);
    	//Must not exixst, no file is storage
    	$this->assertFalse($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	
    	
    	
    }
    
    //Delivering tests
    public function test_delivery_email() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");
    	    	 
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$testfrom = 'fromtest@test.com';
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id, 'delivery'=> 2, 'emailfrom' => $testfrom ));

    	if ($cert->delivery != 2 ) {
    		$this->markTestSkipped("Certificate delivery option must be 2");
    	}
    	$simplecerticate = new simplecertificate($cert);
    	
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	
    	//E-mail send to user test
    	unset_config('noemailever');
    	$sink = $this->redirectEmails();
    	$simplecerticate->output_pdf($issuecert);
    	$messages = $sink->get_messages();
    	
    	//Verify email
    	$this->assertEquals(1, count($messages));
    	$this->assertEquals($this->student_account->email, $messages[0]->to);
    	//Verify emailfrom
    	$this->assertEquals($testfrom, $messages[0]->from);
    	
	}
	
	public function test_email_notifications() {
		global $DB, $CFG;
		
		$this->resetAfterTest();
		$this->setAdminUser();
				
		$testemails=array('teachertest1@test.com', 'teachertest2@test.com', 'test1@test.com', 'test2@test.com');
		
		//Setup teacher accounts
		if($editingteacher_role = $DB->get_record('role', array('shortname'=>'editingteacher'))){
			$userteacher1 = $this->getDataGenerator()->create_user(array('email'=>$testemails[0]));
			$userteacher2 = $this->getDataGenerator()->create_user(array('email'=>$testemails[1]));
			$this->getDataGenerator()->enrol_user($userteacher1->id, $this->course->id, $editingteacher_role->id);
			$this->getDataGenerator()->enrol_user($userteacher2->id, $this->course->id, $editingteacher_role->id);
		} else {
			throw new coding_exception("No editing teacher role");
		}
		
		$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id, 'emailteachers'=>1, 'emailothers'=>$testemails[2].','.$testemails[3]));
		$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
		$this->setUser($this->student_account);
		//E-mail send to teachers and others test
		unset_config('noemailever');
		$sink = $this->redirectEmails();
		$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
		$messages = $sink->get_messages();
		
		//Verify e-mails
		$this->assertEquals(4, count($messages));
		foreach ($messages as $msg) {
			$this->assertContains($msg->to, $testemails);
		}
	}
}
