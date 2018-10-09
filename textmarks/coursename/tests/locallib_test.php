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
 * Tests for mod/simplecertificate/textmarks/coursename/locallib.php
 *
 * @package   simplecertificatetextmark_coursename
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/tests/lib_test.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/coursename/locallib.php');
require_once($CFG->dirroot . '/mod/simplecertificate/tests/generator.php');

/**
 * Unit tests for mod/simplecertificate/textmarks/coursename/locallib.php
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificatetextmark_coursename_locallib_testcase extends advanced_testcase {
    use simplecertificatetextmark_test_tools;

    // Use the generator helper.
    use mod_simplecertificate_test_generator;

    private $student;
    private $course;

    protected function setUp() {
        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->resetAfterTest();
    }

    /**
     * Tests if get_textmark_plugin get the right plugin
     */
    public function test_testable_get_textmark_plugin_get_coursename_plugin() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('coursename');
        $this->assertInstanceOf(simplecertificate_textmark_plugin::class, $plugin);
        $this->assertInstanceOf(simplecertificate_textmark_coursename::class, $plugin);
        $this->assertEquals('Coursename Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin name
     */
    public function test_coursename_textmark_get_name() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('coursename');
        $this->assertEquals('Coursename Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin type
     */
    public function test_coursename_textmark_get_type() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('coursename');
        $this->assertEquals('coursename', $plugin->get_type());
    }

    /**
     * Test if get the right plugin textmarks
     * 
     * @dataProvider get_all_textmarks
     */
    public function test_textmarks($name, $attribute, $formatter, $expected) {
        $smplcert = $this->create_instance($this->course);
        $plugin = new testable_simplecertificate_textmark_coursename($smplcert);
        $this->assertEquals(
            $expected, !empty(
                $plugin->testable_is_valid_textmark($name, $attribute, $formatter)
            ));
    }

    /**
     * Test in get_certificate_text call get_textmark_plugin with
     * 'coursename'
     *
     * @dataProvider textmark_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_if_get_textmark_plugin_is_callabed_with_coursename_param($certificatetext) {
        $this->setUser($this->student->id);
        $pluginmock = $this->createMock(simplecertificate_textmark_coursename::class);

        $mocksmplcert = $this->create_mock_instance($this->course, [
            'get_textmark_plugin'
        ], [
            'certificatetext' => ['text' => $certificatetext]
        ]);

        $mocksmplcert->expects($this->once())
            ->method('get_textmark_plugin')
            ->with($this->equalTo('coursename'))
            ->willReturn($pluginmock);

        $pluginmock->expects($this->once())
            ->method('get_text')
            ->with($this->equalTo($certificatetext));

        $mocksmplcert->testable_get_certificate_text($mocksmplcert->get_issue());
    }


    // =================================================
    // ================= DATA PROVIDERS ================
    // =================================================

    public function get_all_textmarks() {
        return $this->get_all_plugins_textmarks(__DIR__ . '/fixtures/all_textmarks_matrix.csv');
    }

    public function get_valid_textmarks() {
        return $this->get_valid_plugins_textmarks(__DIR__ . '/fixtures/all_textmarks_matrix.csv');
    }

    /**
     * Test submission_is_empty
     *
     * @dataProvider coursename_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_coursename_textmark_get_text($certificatetext, $coursename,
        $expected) {

        $course = $this->getDataGenerator()->create_course([
            'fullname' => $coursename
        ]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($course->id, $user->id, 'student');
        $this->setUser($user);
        $smplcert = $this->create_instance($course);
        $plugin = $smplcert->testable_get_textmark_plugin('coursename');
        $result = $plugin->get_text($certificatetext);
        $this->assertEquals($expected, $result);
    }

    /**
     * Dataprovider for the test_coursename_textmark testcase
     *
     * [certificate text, course fullname, expected text]
     *
     * @return array of testcases
     */
    public function coursename_testcases() {
        $textmarks = $this->get_all_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm => $value) {
            $coursename = $this->get_rnd()->fullname;

            switch($tm) {
                // Test All fullname Textmark.
                case '{COURSENAME}':
                    $testcases[$tm] = array(
                        $tm,
                        $coursename,
                        $coursename,
                    );
                break;
                case '{COURSENAME:ucase}':
                    $testcases[$tm] = array(
                        $tm,
                        $coursename,
                        strtoupper($coursename)
                    );
                break;
                case '{COURSENAME:lcase}':
                    $testcases[$tm] = array(
                        $tm,
                        $coursename,
                        strtolower($coursename)
                    );
                break;
                case '{COURSENAME:ucasefirst}':
                    $testcases[$tm] = array(
                        $tm,
                        $coursename,
                        ucwords($coursename)
                    );
                break;
            }
        }

        // Test textmark with other text.
        $coursename = $this->get_rnd()->fullname;
        $testcases['Test textmark with other text'] = array(
            'Test coursename: {COURSENAME} ok',
            $coursename,
            'Test coursename: ' . $coursename . ' ok'
        );

        // Test textmarks with other textmarks.
        $coursename = $this->get_rnd()->fullname;
        $testcases['Test textmarks with other textmarks'] = array(
            'Test coursename: {COURSENAME:ucase}, {COURSENAME:ucasefirst} {OTHERTEXTMARK} ok',
            $coursename,
            'Test coursename: ' . strtoupper($coursename) . ', ' . ucwords($coursename)
                . ' {OTHERTEXTMARK} ok',
        );

        return $testcases;
    }

    /**
     * Dataprovider for the test_if_get_textmark_plugin_is_callabed_with_coursename_param
     * testcase
     *
     * @return array of testcases
     */
    public function textmark_testcases() {
        $textmarks = $this->get_all_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm => $value) {
            $testcases[$tm] = array('{'. $tm . '}');
        }
        return $testcases;
    }

    private function get_rnd() {
            return $this->getDataGenerator()->create_course();
    }
}

class testable_simplecertificate_textmark_coursename extends simplecertificate_textmark_coursename {

    public function testable_is_valid_textmark($name, $attribute = null, $formatter = null) {
        return $this->is_valid_textmark($name, $attribute, $formatter);
    }

    public function testable_get_attributes() {
        return $this->get_attributes();
    }

    public function testable_get_formatters() {
        return $this->get_formatters();
    }

    public function testable_get_replace_text($name, $attribute = null, $formatter = null) {
        return $this->get_formatters($name, $attribute, $formatter);
    }
}