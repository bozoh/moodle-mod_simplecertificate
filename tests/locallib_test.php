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
 * @group simplecertificate_tests
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

    public function test_create_instance() {
        echo __METHOD__."\n";
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

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
        
    }
    
    public function test_update_instance() {
        echo __METHOD__."\n";
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        
        //Basic CRUD test
        $cert = $this->create_instance();
        $instance=$cert->get_instance();
        $this->assertEquals($this->course->fullname, $instance->coursename);
        $instance->coursename='teste';
        $instanceoldtime=$instance->timemodified;
        $cert->update_instance($instance);
        $instancedb=$DB->get_record('simplecertificate', array('id'=>$cert->get_instance()->id));
        $this->assertEquals('teste', $instancedb->coursename);
        $this->assertTrue($instancedb > $instanceoldtime);
        $this->write_to_report("Can Update a simple certificate ? Ok");
        
    }
    
    public function  test_delete_instance() {
        echo __METHOD__."\n";
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        
        //Basic CRUD test
        $cert = $this->create_instance();
        $this->assertTrue($cert->delete_instance($cert->get_instance()));
        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $this->course->id)));
        $this->write_to_report("Can Delete a simple certificate ? Ok");
    }
    
    public function test_certificate_images() {
        echo __METHOD__."\n";
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        
        //The default data generation puts firstpage and secondpage background images
        $cert = $this->create_instance();
        $fs=get_file_storage();
        
        //Firstpage image
        $fileinfo=$cert::get_certificate_image_fileinfo($cert->get_context());
        $this->assertTrue($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']
                , $fileinfo['filepath'], $cert->get_instance()->certificateimage));
        
        //Second image
        $fileinfo=$cert::get_certificate_secondimage_fileinfo($cert->get_context());
        $this->assertTrue($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']
                , $fileinfo['filepath'], $cert->get_instance()->secondimage));
        $pdf = $cert->testable_create_pdf($cert->get_issue());
        $this->assertNotNull($pdf);
        $filepath = $CFG->dirroot . '/mod/simplecertificate/tests/test_certificate_'.testable_simplecertificate::PLUGIN_VERSION.'.pdf';
        $pdf->Output($filepath, 'F');
        $this->assertTrue(file_exists($filepath));
        $this->write_to_report("Is all images is in certificate: ? Ok");
        
        //Test if can create certificate without any images
        $cert = $this->create_instance(array('certificateimage'=>'', 'secondimage'=>''));
        $this->assertAttributeEmpty('certificateimage', $cert->get_instance());
        $this->assertAttributeEmpty('secondimage', $cert->get_instance());
        $this->write_to_report("Can create certificate without images ? Ok");
        
    }
    
    public function test_certificate_texts() {
        echo __METHOD__."\n";
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        
        //The default data generation has many variable, test if not set variables are printed, like {PROFILE_BIRTHDAY}
        $cert = $this->create_instance();
        $firstpagetext = $cert->testable_get_certificate_text($cert->get_issue());
        $secondpagetext = $cert->testable_get_certificate_text($cert->get_issue(),$cert->get_instance()->secondpagetext);
        //In this test first must be different than second one
        $this->assertNotEquals($firstpagetext, $secondpagetext);
        $this->assertNotContains("{", $firstpagetext);
        $this->write_to_report("Front text is correct: ? Ok");
        $this->assertNotContains("{", $secondpagetext);
        $this->write_to_report("Back text is enable and correct ? Ok");
        
    }
    
    public function test_create_issue_instance() {
        echo __METHOD__."\n";
    	global $DB;

    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$cert = $this->create_instance();
    	//Verify if no certificate is issued
    	//$this->assertFalse($DB->record_exists("simplecertificate_issues", array('certificateid'=>$cert->get_instance()->id)));
    	
    	//Issued a student certificate as manager
    	$issuecert = $cert->get_issue($this->students[0]);
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->assertEquals($this->students[0]->id, $issuecert->userid);
    	$this->write_to_report("Can Retrieve a student simple certificate As manager? Ok");
    	
    	//Issuing a manager certificate as manager (do not save)
    	$issuecert= $cert->get_issue();
    	$this->assertNotEmpty($issuecert);
    	$this->assertFalse($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->write_to_report("Can Retrieve a simple certificate As manager? Ok");
    	 
    	
    	//Issuing as student
    	$this->setUser($this->students[1]);
    	//Verify if no certificate is issued for this studet
    	$this->assertFalse($DB->record_exists("simplecertificate_issues", array('certificateid'=>$cert->get_instance()->id, 'userid'=>$this->students[1]->id)));
    	$issuecert= $cert->get_issue();
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->get_instance()->id, 'userid'=>$this->students[1]->id)));
    	$this->assertTrue(!empty($issuecert->haschange));
    	$this->assertEquals($this->students[1]->id, $issuecert->userid);
    	$this->write_to_report("Can Retrieve a simple certificate As student ? Ok");
    	    	
    	//Must have 2 certificates
    	$this->assertEquals(2, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->get_instance()->id)));
    	$this->write_to_report("Manager issued certificates are not save? Ok");
    	        
    }
    
    public function test_create_issue_code() {
        echo __METHOD__."\n";
        global $DB;
         
        $this->resetAfterTest();
        $this->setAdminUser();
        $cert = $this->create_instance();
        $issuecert = $cert->get_issue($this->students[0]);
         
        //Verify code
        $this->assertNotEmpty($issuecert->code);
        $this->assertEquals(36, strlen($issuecert->code));
        $this->assertEquals($this->students[0]->id, $DB->get_field_select('simplecertificate_issues', 'userid', 'code = :code', array('code' => $issuecert->code)));
         
        $this->write_to_report("Certificate code is correct ? Ok");
    }
    
    public function test_update_instace_update_haschange_issues() {
        echo __METHOD__."\n";
        global $DB;
        
        $this->resetAfterTest();
        $this->setAdminUser();
        $cert = $this->create_instance();
        
        $issuecert1 = $cert->get_issue($this->students[0]);
        $issuecert2 = $cert->get_issue($this->students[1]);
        $issuecert3 = $cert->get_issue($this->students[2]);
        
        //Update haschage status
        $issuecert1->haschange=0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert1));
        $issuecert2->haschange=0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert2));
        $issuecert3->haschange=0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert3));
        //Verify if haschage is really 0
        $this->assertEquals(0, $cert->get_issue($this->students[0])->haschange);
        $this->assertEquals(0, $cert->get_issue($this->students[1])->haschange);
        $this->assertEquals(0, $cert->get_issue($this->students[2])->haschange);
        
        //Update simplecertificate instance
        $cert->update_instance($cert->get_instance());
        
        //Verify if haschage is 1 now
        $this->assertEquals(1, $cert->get_issue($this->students[0])->haschange);
        $this->assertEquals(1, $cert->get_issue($this->students[1])->haschange);
        $this->assertEquals(1, $cert->get_issue($this->students[2])->haschange);
        
        $this->write_to_report("Update certificate updates haschange status in issued certificates? Ok");
        
    }
    
    public function  test_create_pdf_file() {
        echo __METHOD__."\n";
        global $DB, $CFG;
         
        $this->resetAfterTest();
        $this->setAdminUser();
    
        $cert = $this->create_instance();
        $issuecert= $cert->get_issue($this->students[2]);
    
        //Verify if file DON´T EXISTS
        $this->assertTrue(!empty($issuecert->haschange));
        $this->assertFalse($cert->testable_issue_file_exists($issuecert));
        $this->assertDebuggingCalled();
    
        //Creating file
        $file=$cert->testable_get_issue_file($issuecert);
        $this->assertTrue(empty($issuecert->haschange));
        $this->assertTrue($cert->testable_issue_file_exists($issuecert));
        $this->assertEquals($issuecert->pathnamehash, $file->get_pathnamehash());
        $this->write_to_report("create a pdf file ? Ok");
         
        //Verify if only re-create a pdf file if certificate changes
        $issuecert= $cert->get_issue($this->students[2]);
        $this->assertTrue(empty($issuecert->haschange));
        $this->assertFalse($cert->testable_create_pdf($issuecert));
        $this->assertEquals($file, $cert->testable_save_pdf($issuecert));
        $this->assertTrue(empty($issuecert->haschange));
        $this->write_to_report("Only re-create a pdf file if certificate changes? Ok");
         
        //Issue as admin
        $this->setAdminUser();
        $issuecert=$cert->get_issue();
         
        //Verify if file DON´T EXISTS
        $this->assertTrue(!empty($issuecert->haschange));
        $this->assertFalse($cert->testable_issue_file_exists($issuecert));
        $this->assertDebuggingCalled();
         
        //Creating file
        //file created
        $this->assertNotNull($cert->testable_get_issue_file($issuecert));
        $instance=$cert->get_instance();
        //Disabled delivery to do this teste
        $instance->delivery = 3;
        //After delivery action file must be removed
        $cert->output_pdf($issuecert);
         
        //Must not exixst, no file is storage
        $this->assertTrue(empty($issuecert->haschange));
        $this->assertFalse($cert->testable_issue_file_exists($issuecert));
        $this->assertDebuggingCalled();
         
        $this->write_to_report("Managers certificates are not save? Ok");
    
    }
    
    public function test_detete_instace_update_timedelete_issues() {
        echo __METHOD__."\n";
        global $DB;
    
        $this->resetAfterTest();
        $this->setAdminUser();
        $cert = $this->create_instance();
        $issuecert1 = $cert->get_issue($this->students[0]);
        $issuecert2 = $cert->get_issue($this->students[1]);
        $issuecert3 = $cert->get_issue($this->students[2]);
        
        //Verify if timedelete is really null
        $this->assertNull($cert->get_issue($this->students[0])->timedeleted);
        $this->assertNull($cert->get_issue($this->students[1])->timedeleted);
        $this->assertNull($cert->get_issue($this->students[2])->timedeleted);
        
        //Creating issue file, but not issuecert2
        $oldfile1 = $cert->testable_get_issue_file($issuecert1);
        $this->assertFalse(empty($oldfile1));
        $this->assertTrue($cert->testable_issue_file_exists($issuecert1));
        
        $this->assertFalse($cert->testable_issue_file_exists($issuecert3));
        $this->assertDebuggingCalled();
        
        $oldfile3 = $cert->testable_get_issue_file($issuecert3);
        $this->assertFalse(empty($oldfile3));
        $this->assertTrue($cert->testable_issue_file_exists($issuecert3));
          
        //Update simplecertificate instance
        $cert->delete_instance($cert->get_instance());
        //It's expected a debug calling beacause isseucert2 does not create issue file
        $this->assertDebuggingCalled(get_string('filenotfound', 'simplecertificate'). ' (issue id:[' . $issuecert2->id . '])', DEBUG_DEVELOPER);
       
        //Verify if timedelete is not null
        $issuecert1 = $DB->get_record('simplecertificate_issues', array('id'=>$issuecert1->id));
        $issuecert2 = $DB->get_record('simplecertificate_issues', array('id'=>$issuecert2->id));
        $issuecert3 = $DB->get_record('simplecertificate_issues', array('id'=>$issuecert3->id));
        
        $this->assertObjectHasAttribute('timedeleted', $issuecert1);
        $this->assertObjectHasAttribute('timedeleted', $issuecert2);
        $this->assertObjectHasAttribute('timedeleted', $issuecert3);
        
        $this->write_to_report("Delete certificate adds timeend in issued certificates? Ok");
        
        //Verify pathnamehash
        $this->assertNotEquals($oldfile1->get_pathnamehash(), $issuecert1->pathnamehash);
        $this->assertEmpty($DB->get_field('simplecertificate_issues', 'pathnamehash', array('id'=>$issuecert2->id)));
        $this->assertNotEquals($oldfile1->get_pathnamehash(), $issuecert3->pathnamehash);
        
        //Verify if issued certificate is moved to user private file area
        $this->assertTrue($cert->testable_issue_file_exists($issuecert1));
        $this->assertTrue($cert->testable_issue_file_exists($issuecert3));
        $this->assertFalse($cert->testable_issue_file_exists($issuecert2));
        $this->assertDebuggingCalled();        

        $this->write_to_report("Move issues certificate to user private filearea if simplecertificate activity is deleted? Ok");
    }

    //Delivering tests
    public function test_delivery_email() {
        echo __METHOD__."\n";
    	global $DB, $CFG;
    	
    	if (moodle_major_version() < 2.6) {
    		$this->markTestSkipped("Needs moodle 2.6 or grater");
    	}
    	    	 
    	$this->resetAfterTest();
    	$this->setAdminUser();
    	$testfrom = 'fromtest@test.com';
    	//Set some prarmetes
    	$cert = $this->create_instance(array('delivery'=> 2, 'emailfrom' => $testfrom ));
    	$issuecert= $cert->get_issue($this->students[1]);
    	
    	//E-mail send to user test
    	unset_config('noemailever');
    	$sink = $this->redirectEmails();
    	$cert->testable_send_certificade_email($issuecert);
    	$messages = $sink->get_messages();
    	
    	//Verify email
    	$this->assertEquals(1, count($messages));
    	$this->assertEquals($this->students[1]->email, $messages[0]->to);
    	//Verify emailfrom
    	$this->assertEquals($testfrom, $messages[0]->from);
    	
    	$this->write_to_report("Can send certificade to e-mail? Ok");
    	$this->write_to_report("Can change email sender (from email)? Ok");
    	
	}
	
	public function test_email_notifications() {
	    echo __METHOD__."\n";
		global $DB;
		
		if (moodle_major_version() < 2.6) {
			$this->markTestSkipped("Needs moodle 2.6 or grater");
		}
		$this->resetAfterTest();
		$this->setAdminUser();

		//Setup tem certificate instance
		$testemails=array('test1@test.com', 'test2@test.com','test3@test.com');
		$emailothers = implode(',',$testemails);
		$cert = $this->create_instance(array('emailteachers'=>1, 'emailothers'=>$emailothers));
		
		//E-mail send to teachers and others test
		unset_config('noemailever');
		$sink = $this->redirectEmails();
		$issuecert= $cert->get_issue($this->students[0]);
		$messages = $sink->get_messages();
 		
		//Verify e-mails
		$this->assertEquals(count($this->editingteachers)+count($testemails), count($messages));

		foreach ($this->editingteachers as $teacher) {
		    $testemails[] = $teacher->email;
		}
		
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
		parent::setUpBeforeClass();
		
	}
	
	public static function tearDownAfterClass() {
		global $CFG;
		
		fwrite(self::$fhandle, "\nEnd ofPHPUnit tests.\n------\n\n");
		$othertests = file_get_contents ("$CFG->dirroot/mod/simplecertificate/tests/other/TestCaseChkLst.txt");
		fwrite(self::$fhandle, $othertests);
		fclose(self::$fhandle);
		parent::tearDownAfterClass();
	}
	
	private function write_to_report($str) {
		self::$count++;
		fwrite(self::$fhandle, self::$count.'- '.$str."\n");
	}
}

