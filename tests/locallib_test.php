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
 * @package mod_simplecertificate
 * @category phpunit
 * @copyright 2013 onwards Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group simplecertificate_tests
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
// ...require_once($CFG->dirroot . '/mod/simplecertificate/upgradelib.php');.
require_once($CFG->dirroot . '/mod/simplecertificate/tests/base_test.php');

/**
 * Unit tests for (some of) mod/simplecertificate/locallib.php.
 *
 * @copyright 2013 onwards Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group simplecertificate_basics
 */

// ...vendor/bin/phpunit mod_simplecertificate_locallib_testcase mod/simplecertificate/tests/locallib_test.php .
class mod_simplecertificate_locallib_testcase extends mod_simplecertificate_base_testcase {

    public function test_create_instance() {
        global $DB;

        // Basic CRUD test.
        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $this->course->id)));
        $cert = $this->create_instance();
        $this->assertEquals(1, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertTrue($DB->record_exists('simplecertificate',
                          array('course' => $this->course->id, 'id' => $cert->get_instance()->id))
        );

        $params = array('course' => $this->course->id, 'name' => 'One more certificate');
        $cert = $this->create_instance($params);
        $this->assertEquals(2, $DB->count_records('simplecertificate', array('course' => $this->course->id)));
        $this->assertEquals('One more certificate',
                            $DB->get_field_select('simplecertificate', 'name', 'id = :id',
                            array('id' => $cert->get_instance()->id))
        );
    }

    public function test_update_instance() {
        global $DB;

        // Basic CRUD test.
        $cert = $this->create_instance();
        $instance = $cert->get_instance();
        $this->assertEquals($this->course->fullname, $instance->coursename);
        $instance->coursename = 'teste';
        $instanceoldtime = $instance->timemodified;
        $cert->update_instance($instance);
        $instancedb = $DB->get_record('simplecertificate', array('id' => $cert->get_instance()->id));
        $this->assertEquals('teste', $instancedb->coursename);
        $this->assertTrue($instancedb > $instanceoldtime);
    }

    public function test_delete_instance() {
        global $DB;

        // Basic CRUD test.
        $cert = $this->create_instance();
        $this->assertTrue($cert->delete_instance($cert->get_instance()));
        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $this->course->id)));
    }

    public function test_certificate_images() {
        global $DB, $CFG;

        // The default data generation puts firstpage and secondpage background images.
        $cert = $this->create_instance();
        $fs = get_file_storage();

        // Firstpage image.
        $fileinfo = $cert::get_certificate_image_fileinfo($cert->get_context());
        $this->assertTrue($fs->file_exists($fileinfo['contextid'],
                                           $fileinfo['component'],
                                           $fileinfo['filearea'],
                                           $fileinfo['itemid'],
                                           $fileinfo['filepath'],
                                           $cert->get_instance()->certificateimage)
        );

        // Second image.
        $fileinfo = $cert::get_certificate_secondimage_fileinfo($cert->get_context());
        $this->assertTrue($fs->file_exists($fileinfo['contextid'],
                        $fileinfo['component'],
                        $fileinfo['filearea'],
                        $fileinfo['itemid'],
                        $fileinfo['filepath'],
                        $cert->get_instance()->secondimage)
        );

        // PDF creation don't work in moodle 3.0.
        $pdf = $cert->testable_create_pdf($cert->get_issue());
        $this->assertNotNull($pdf);
        $filepath = $CFG->dirroot .
        '/mod/simplecertificate/tests/test_certificate_'.testable_simplecertificate::PLUGIN_VERSION.'.pdf';
        $pdf->Output($filepath, 'F');
        $this->assertTrue(file_exists($filepath));

        // Test if can create certificate without any images.
        $cert = $this->create_instance(array('certificateimage' => '', 'secondimage' => ''));
        $this->assertAttributeEmpty('certificateimage', $cert->get_instance());
        $this->assertAttributeEmpty('secondimage', $cert->get_instance());
    }

    public function test_certificate_texts() {
        global $DB, $CFG;

        // The default data generation has many variable, test if not set variables are printed, like {PROFILE_BIRTHDAY}.
        $cert = $this->create_instance();
        $firstpagetext = $cert->testable_get_certificate_text($cert->get_issue());
        $secondpagetext = $cert->testable_get_certificate_text($cert->get_issue(), $cert->get_instance()->secondpagetext);
        // In this test first must be different than second one.
        $this->assertNotEquals($firstpagetext, $secondpagetext);
        $this->assertNotContains("{", $firstpagetext);
        $this->assertNotContains("{", $secondpagetext);
    }

    public function test_create_issue_instance() {
        global $DB;

        $cert = $this->create_instance();
        // Verify if no certificate is issued.
        $this->assertFalse($DB->record_exists("simplecertificate_issues",
                        array('certificateid' => $cert->get_instance()->id))
        );

        // Issued a student certificate as manager.
        $issuecert = $cert->get_issue($this->students[0]);
        $this->assertNotEmpty($issuecert);
        $this->assertTrue($DB->record_exists("simplecertificate_issues", array('id' => $issuecert->id)));
        $this->assertTrue(!empty($issuecert->haschange));
        $this->assertEquals($this->students[0]->id, $issuecert->userid);

        // Issuing a manager certificate as manager (do not save).
        $issuecert = $cert->get_issue();
        $this->assertNotEmpty($issuecert);
        $this->assertFalse($DB->record_exists("simplecertificate_issues", array('id' => $issuecert->id)));
        $this->assertTrue(!empty($issuecert->haschange));

        // Issuing as student.
        $this->setUser($this->students[1]);
        // Verify if no certificate is issued for this student.
        $this->assertFalse($DB->record_exists("simplecertificate_issues",
                                        array('certificateid' => $cert->get_instance()->id,
                                              'userid' => $this->students[1]->id))
        );
        $issuecert = $cert->get_issue();
        $this->assertNotEmpty($issuecert);
        $this->assertTrue($DB->record_exists("simplecertificate_issues", array('id' => $issuecert->id)));
        $this->assertEquals(1,
                            $DB->count_records("simplecertificate_issues",
                                            array('certificateid' => $cert->get_instance()->id,
                                                  'userid' => $this->students[1]->id))
        );
        $this->assertTrue(!empty($issuecert->haschange));
        $this->assertEquals($this->students[1]->id, $issuecert->userid);

        // Must have 2 certificates.
        $this->assertEquals(2, $DB->count_records("simplecertificate_issues",
                        array('certificateid' => $cert->get_instance()->id)));
    }

    public function test_create_issue_code() {
        global $DB;

        $cert = $this->create_instance();
        $issuecert = $cert->get_issue($this->students[0]);

        // Verify code.
        $this->assertNotEmpty($issuecert->code);
        $this->assertEquals(36, strlen($issuecert->code));
        $this->assertEquals($this->students[0]->id,
                            $DB->get_field_select('simplecertificate_issues', 'userid', 'code = :code',
                                                array('code' => $issuecert->code)));
    }

    public function test_update_instace_update_haschange_issues() {
        global $DB;

        $cert = $this->create_instance();

        $issuecert1 = $cert->get_issue($this->students[0]);
        $issuecert2 = $cert->get_issue($this->students[1]);
        $issuecert3 = $cert->get_issue($this->students[2]);

        // Update haschage status.
        $issuecert1->haschange = 0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert1));
        $issuecert2->haschange = 0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert2));
        $issuecert3->haschange = 0;
        $this->assertTrue($DB->update_record('simplecertificate_issues', $issuecert3));
        // Verify if haschage is really 0.
        $this->assertEquals(0, $cert->get_issue($this->students[0])->haschange);
        $this->assertEquals(0, $cert->get_issue($this->students[1])->haschange);
        $this->assertEquals(0, $cert->get_issue($this->students[2])->haschange);

        // Update simplecertificate instance.
        $cert->update_instance($cert->get_instance());

        // Verify if haschage is 1 now.
        $this->assertEquals(1, $cert->get_issue($this->students[0])->haschange);
        $this->assertEquals(1, $cert->get_issue($this->students[1])->haschange);
        $this->assertEquals(1, $cert->get_issue($this->students[2])->haschange);
    }
    // PDF creation don't work in moodle 3.0.
    public function test_create_pdf_file() {
    }

    public function test_detete_instace_update_timedelete_issues() {
        global $DB;

        // PDF creation don't work in moodle 3.0.
        $cert = $this->create_instance();
        $issuecert1 = $cert->get_issue($this->students[0]);

        // Verify if timedelete is really null.
        $this->assertObjectNotHasAttribute('timedeleted', $issuecert1);

        // Update simplecertificate instance.
        $cert->delete_instance($cert->get_instance());
        $this->assertDebuggingCalled(null, DEBUG_DEVELOPER);

        // Verify if timedelete is not null.
        $issuecert1 = $DB->get_record('simplecertificate_issues', array('id' => $issuecert1->id));

        $this->assertObjectHasAttribute('timedeleted', $issuecert1);
    }

    // DOn't work with moodle 3.0.
    // Delivering tests.
    public function test_delivery_email() {
    }

    public function test_email_notifications() {
        global $DB;

        if (moodle_major_version() < 2.6) {
            $this->markTestSkipped("Needs moodle 2.6 or grater");
        }

        // Setup tem certificate instance.
        $testemails = array('test1@test.com', 'test2@test.com', 'test3@test.com');
        $emailothers = implode(',', $testemails);
        $cert = $this->create_instance(array('emailteachers' => 1, 'emailothers' => $emailothers));

        // E-mail send to teachers and others test.
        unset_config('noemailever');
        $sink = $this->redirectEmails();
        $issuecert = $cert->get_issue($this->students[0]);
        $messages = $sink->get_messages();

        // Verify e-mails.
        $this->assertEquals(count($this->editingteachers) + count($testemails), count($messages));

        foreach ($this->editingteachers as $teacher) {
            $testemails[] = $teacher->email;
        }

        foreach ($messages as $msg) {
            $this->assertContains($msg->to, $testemails);
        }
    }

    public function test_can_issue_user_without_grade_restrinction() {
        $cert = $this->create_instance();

        $this->assertNull($cert->testable_can_issue($this->students[0]));
    }

    /**      Not unit test
//     public function test_can_issue_with_grade_restrinction() {
//         $mock_cert = $this->get_certificate_mock(array('testable_check_user_can_access_certificate_instance'));

//         $mock_cert->expects($this->once())->
//                 method('testable_check_user_can_access_certificate_instance')->
//                 will($this->returnValue(true));

//         $this->assertNull($mock_cert->testable_can_issue($this->students[0]));
//     }

    public function test_can_issue_with_completion_restrinction() {
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $mock_cert = $this->get_certificate_mock(
                                                array('get_instance', 'get_course_time',
                                                    'testable_check_user_can_access_certificate_instance'), array(), $completionauto);

        $return_value = new stdClass();
        $return_value->requiredtime = 5;

        $mock_cert->expects($this->any())->method('get_instance')->will($this->returnValue($return_value));

        $mock_cert->expects($this->any())->method('get_course_time')->will($this->returnValue(10));

        $mock_cert->expects($this->any())->method('testable_check_user_can_access_certificate_instance')->will(
                                                                                                            $this->returnValue(true));

        $this->assertNull($mock_cert->testable_can_issue($this->students[0]));
    }

    public function test_cant_issue_with_grade_restrinction() {
        $mock_cert = $this->get_certificate_mock(array('testable_check_user_can_access_certificate_instance'));

        $mock_cert->expects($this->once())->method('testable_check_user_can_access_certificate_instance')->will(
                                                                                                                $this->returnValue(
                                                                                                                                false));

        $this->assertEquals(get_string('cantissue', 'simplecertificate'), $mock_cert->testable_can_issue($this->students[0]));
    }

    public function test_cant_issue_with_completion_restrinction() {
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);
        $mock_cert = $this->get_certificate_mock(
                                                array('get_instance', 'get_course_time',
                                                    'testable_check_user_can_access_certificate_instance'), array(), $completionauto);

        // /Removing Avaliability checks
        $mock_cert->expects($this->any())->method('testable_check_user_can_access_certificate_instance')->will(
                                                                                                            $this->returnValue(true));

        $return_value = new stdClass();
        $return_value->requiredtime = 5;

        $mock_cert->expects($this->any())->method('get_instance')->will($this->returnValue($return_value));

        $mock_cert->expects($this->any())->method('get_course_time')->will($this->returnValue(4));

        $a = new stdClass();
        $a->requiredtime = $mock_cert->get_instance()->requiredtime;
        $error_msg = get_string('requiredtimenotmet', 'simplecertificate', $a);

        $this->assertEquals($error_msg, $mock_cert->testable_can_issue($this->students[0]));
    }

    /**
     */
    private function get_certificate_mock(array $methods = null, array $params = array(), array $options = null) {
        // Only to get contexts variables.
        $cert = $this->create_instance($params, $options);

        $mockcert = $this->getMockBuilder('testable_simplecertificate')->setMethods($methods)->setConstructorArgs(array($cert->get_context(),
                                                                    $cert->get_course_module(),
                                                                    $cert->get_course()))->getMock();

        return $mockcert;
    }
}

