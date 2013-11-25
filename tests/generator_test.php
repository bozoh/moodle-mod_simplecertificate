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
class mod_simplecertificate_generator_testcase extends advanced_testcase {
	//http://docs.moodle.org/dev/Writing_PHPUnit_tests
	private $course;
	private $student_account;

	
	public static function setUpBeforeClass() {
		//For e-mail send test
		unset_config('noemailever');
		
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
    	$issuecert= $simplecertgen->create_issue(array('certificate'=>$cert, 'user'=>$this->student_account));
    	//Issue certificate is create ?
    	$this->assertNotEmpty($issuecert);
    	$this->assertTrue($DB->record_exists("simplecertificate_issues", array('id'=>$issuecert->id)));
    	$this->assertEquals(1, $DB->count_records("simplecertificate_issues", array('certificateid'=>$cert->id)));
    	
    	//Verify code
    	$this->assertNotEmpty($issuecert->code);
    	$this->assertEquals(36, strlen($issuecert->code));
    	$this->assertEquals($issuecert->id, $DB->get_record('simplecertificate_issues', array('code'=>$issuecert->code),"id")->id);    	
    	

    	//TODO Verificar se um pdf foi gerado
    	
    	$fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuecert, null);
    	$fs = get_file_storage();
    	$this->assertFalse($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	if ($cert->delivery != 3 ){
    		$simplecert=new simplecertificate($cert);
    		$certpdffile = $simplecert->output_pdf($issuecert);
    		$this->assertNotEmpty($certpdffile);
    		$this->assertTrue($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']));
    	}
    	


    	//TODO verificar se consegue emitir para o e-mail
    	//TODO verificar se faz a notificação para o e-mail dos professores, e outros
    	
    }
}
