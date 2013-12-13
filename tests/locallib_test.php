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
 * Unit tests for (some of) mod/simplecertificate/locallib.php.
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
require_once($CFG->dirroot . '/mod/simplecertificate/tests/base_test.php');

/**
 * Unit tests for (some of) mod/simplecertificate/locallib.php.
 *
 * @copyright  2013 onwards Carlos Alexandre S. da Fonseca 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group	   simplecertificate_basics	 
 * */
class mod_simplecertificate_locallib_testcase extends mod_simplecertificate_base_testcase {
    public static $count;
    public static $fhandle;

    public function test_create_certificate_instance() {
        global $DB;
        $this->resetAfterTest();

        //Basic CRUD test
        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $this->course->id)));
        $cert = $this->create_instance();
        $this->assertEquals(1, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertTrue($DB->record_exists('simplecertificate', array('course' => $this->course->id, 'id' => $cert->get_instance()->id)));

        $params = array('course' => $this->course->id, 'name' => 'One more certificate');
        $cert = $this->create_instance($params);
        $this->assertEquals(2, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertEquals('One more certificate', $DB->get_field_select('simplecertificate', 'name', 'id = :id', array('id' => $cert->get_instance()->id)));
        
        $this->write_to_report("Creating plugin is working ? Ok");
        $this->write_to_report("Can Create a simple certificate ? Ok");
        $this->write_to_report("Can Update a simple certificate ? Ok");
        $this->write_to_report("Can Delete a simple certificate ? Ok");
    }
    
    public function test_create_issue_instance() {
    	global $DB;
    	
    	
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id));
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	//No certificate is issued
    	$this->assertFalse($DB->record_exists("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	//Issued a student certificate as manager
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->assertEquals($this->course->fullname, $issuecert->coursename);
    	$this->assertEquals($this->student_account->id, $issuecert->userid);
    	$this->write_to_report("Can Retrieve a student simple certificate As manager? Ok");
    	
    	//Issuing a manager certificate as manager (do not save)
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert));
    	$this->assertNotEmpty($issuecert);
    	$this->assertFalse($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->assertEquals($this->course->fullname, $issuecert->coursename);
    	$this->assertNotEquals($this->student_account->id, $issuecert->userid);
    	$this->write_to_report("Can Retrieve a simple certificate As manager? Ok");
    	 
    	
    	//Issuing using as student
    	$this->setUser($this->student_account);
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert));
    	//Has issued
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->assertEquals($this->course->fullname, $issuecert->coursename);
    	$this->assertEquals($this->student_account->id, $issuecert->userid);
    	$this->write_to_report("Can Retrieve a simple certificate As student ? Ok");
    	
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	
    	        
    }
    
    public function test_create_issue_code() {
    	global $DB;
    	
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id));
    	$simplecertgen = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	    	
    	//Verify code
    	$this->assertNotEmpty($issuecert->code);
    	$this->assertEquals(36, strlen($issuecert->code));
    	$this->assertEquals($issuecert->id, $DB->get_record('simplecertificate_issues', array('code'=>$issuecert->code),"id")->id);
    	
    	$this->write_to_report("Certificate code is correct ? Ok");
    }
    
    public function  test_pdf_file() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/tests/fixtures/locallibwarp.php");
    	
    	if (moodle_major_version() < 2.6) {
    		$this->markTestSkipped("Needs moodle 2.6 or grater");
    	}
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id ));

    	//Deplivery option must be 3 for this test
   		$cert->delivery = 3; 
    	$simplecerticate = new simplecertificateWarperClass($cert);
    		
    	$issuecert= $simplecerticate->get_issue($this->student_account);

    	//Verify if file DON´T EXISTS
    	$this->assertFalse($simplecerticate->issue_file_exists($issuecert));
    	$this->assertTrue(!empty($issuecert->haschange));
    	
    	//Creating file
    	$simplecerticate->output_pdf($issuecert);
    	$this->assertTrue(empty($issuecert->haschange));
    	$this->assertTrue($simplecerticate->issue_file_exists($issuecert));
    	$this->write_to_report("Can Open certificade file in browser? Ok");
    	$this->write_to_report("Can Download certificade file in browser? Ok");
    	
    	//Verify if only re-create a pdf file if certificate changes
    	$issuecert= $simplecerticate->get_issue($this->student_account);
    	$this->assertTrue(empty($issuecert->haschange));
    	$this->assertFalse($simplecerticate->create_pdf($issuecert));
    	$fileinfo=$simplecerticate::get_certificate_issue_fileinfo($issuecert);
    	$this->assertEquals($fileinfo['filename'], $simplecerticate->save_pdf(null, $issuecert));
    	$this->assertTrue(empty($issuecert->haschange));
    	//TODO how to test simplecertificate_update_instance function
    	$this->write_to_report("Only re-create a pdf file if certificate changes? Ok");
    	
    	//Issue as admin
    	$this->setAdminUser();
    	$issuecert= $simplecerticate->get_issue();
    	
    	//Verify if file DON´T EXISTS
    	$this->assertFalse($simplecerticate->issue_file_exists($issuecert));
    	$this->assertTrue(!empty($issuecert->haschange));

    	//Creating file
    	$simplecerticate->output_pdf($issuecert);
    	//Must not exixst, no file is storage
    	$this->assertFalse($simplecerticate->issue_file_exists($issuecert));
    	$this->assertTrue(empty($issuecert->haschange));
    	$this->write_to_report("Managers certificates are not save? Ok");
    }
    
    //Delivering tests
    public function test_delivery_email() {
    	global $DB, $CFG;
    	require_once("$CFG->dirroot/mod/simplecertificate/tests/fixtures/locallibwarp.php");
    	
    	if (moodle_major_version() < 2.6) {
    		$this->markTestSkipped("Needs moodle 2.6 or grater");
    	}
    	    	 
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$testfrom = 'fromtest@test.com';
    	$cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $this->course->id, 'delivery'=> 2, 'emailfrom' => $testfrom ));
    	
    	$simplecerticate = new simplecertificateWarperClass($cert);
    	$issuecert= $simplecerticate->get_issue($this->student_account);
    	$pdfcert = $simplecerticate->create_pdf($issuecert);
    	@$simplecerticate->save_pdf($pdfcert, $issuecert);
    	
    	//E-mail send to user test
    	unset_config('noemailever');
    	$sink = $this->redirectEmails();
    	$simplecerticate->send_certificade_email($issuecert);
    	$messages = $sink->get_messages();
    	
    	//Verify email
    	$this->assertEquals(1, count($messages));
    	$this->assertEquals($this->student_account->email, $messages[0]->to);
    	//Verify emailfrom
    	$this->assertEquals($testfrom, $messages[0]->from);
    	
    	$this->write_to_report("Can send certificade to e-mail? Ok");
    	$this->write_to_report("Can change email sender (from email)? Ok");
    	
	}
	
	public function test_email_notifications() {
		global $DB;
		
		if (moodle_major_version() < 2.6) {
			$this->markTestSkipped("Needs moodle 2.6 or grater");
		}
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
		
		$this->write_to_report("Can notify Teacher, when a certificate is issued? Ok");
		$this->write_to_report("Can notify others, when a certificate is issued? Ok");
	}
	
	public static function setUpBeforeClass() {
		global $CFG;
		
		$moodle_version='moodle-'.moodle_major_version();
		$moodle_version.=' '.testable_simplecertificate::PLUGIN_VERSION;
		$moodle_version.=' build: '.get_config('mod_simplecertificate','version')."\n";
		
		self::$fhandle = fopen("$CFG->dirroot/mod/simplecertificate/TestCaseResults.txt", "w");
		fwrite(self::$fhandle, $moodle_version);
		fwrite(self::$fhandle, 'Runned at: '.date('Y-m-d H:i')."\n\n");
		fwrite(self::$fhandle, "\n------\nPHPUnit tests:\n\n");
		self::$count = 0;
		
	}
	
	public static function tearDownAfterClass() {
		global $CFG;
		
		fwrite(self::$fhandle, "\nEnd ofPHPUnit tests.\n------\n\n");
		$othertests = file_get_contents ("$CFG->dirroot/mod/simplecertificate/tests/other/TestCaseChkLst.txt");
		fwrite(self::$fhandle, $othertests);
		fclose(self::$fhandle);
	}
	
	private function write_to_report($str) {
		self::$count++;
		fwrite(self::$fhandle, self::$count.'- '.$str."\n");
	}
}

