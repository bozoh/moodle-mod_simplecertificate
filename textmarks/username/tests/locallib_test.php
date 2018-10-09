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
 * Tests for mod/simplecertificate/textmarks/username/locallib.php
 *
 * @package   simplecertificatetextmark_username
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/tests/lib_test.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/username/locallib.php');
require_once($CFG->dirroot . '/mod/simplecertificate/tests/generator.php');

/**
 * Unit tests for mod/simplecertificate/textmarks/username/locallib.php
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificatetextmark_username_locallib_testcase extends advanced_testcase {
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
    public function test_testable_get_textmark_plugin_get_username_plugin() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('username');
        $this->assertInstanceOf(simplecertificate_textmark_plugin::class, $plugin);
        $this->assertInstanceOf(simplecertificate_textmark_username::class, $plugin);
        $this->assertEquals('Username Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin name
     */
    public function test_username_textmark_get_name() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('username');
        $this->assertEquals('Username Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin type
     */
    public function test_username_textmark_get_type() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('username');
        $this->assertEquals('username', $plugin->get_type());
    }

    /**
     * Test if get the right plugin textmarks
     * 
     * @dataProvider get_all_textmarks
     */
    public function test_textmarks($name, $attribute, $formatter, $expected) {
        $smplcert = $this->create_instance($this->course);
        $plugin = new testable_simplecertificate_textmark_username($smplcert);
        $this->assertEquals(
            $expected, !empty(
                $plugin->testable_is_valid_textmark($name, $attribute, $formatter)
            ));
    }

    /**
     * Test in get_certificate_text call get_textmark_plugin with
     * 'username'
     *
     * @dataProvider get_textmarks
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_if_get_textmark_plugin_is_callabed_with_username_param($certificatetext) {
        $this->setUser($this->student->id);
        $pluginmock = $this->createMock(simplecertificate_textmark_username::class);

        $mocksmplcert = $this->create_mock_instance($this->course, [
            'get_textmark_plugin'
        ], [
            'certificatetext' => ['text' => $certificatetext]
        ]);

        $mocksmplcert->expects($this->once())
            ->method('get_textmark_plugin')
            ->with($this->equalTo('username'))
            ->willReturn($pluginmock);

        $pluginmock->expects($this->once())
            ->method('get_text')
            ->with($this->equalTo($certificatetext));

        $mocksmplcert->testable_get_certificate_text($mocksmplcert->get_issue());
    }

    /**
     * Test submission_is_empty
     *
     * @dataProvider username_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_username_textmark_get_text($certificatetext, $firstname,
        $lastname, $expected) {

        $user = $this->getDataGenerator()->create_user([
            'firstname' => $firstname,
            'lastname' => $lastname
        ]);
        $this->getDataGenerator()->enrol_user($this->course->id, $user->id, 'student');
        $this->setUser($user);
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('username');
        $result = $plugin->get_text($certificatetext);
        $this->assertEquals($expected, $result);
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
     * Dataprovider for test_if_get_textmark_plugin_is_callabed_with_username_param
     *
     * @return array of testcases
     */
    public function get_textmarks() {
        $textmarks = $this->get_valid_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm => [$name, $attr, $fmt, $valid]) {
            $testcases[$tm] = array('{' . $tm . '}');
        }
        return $testcases;
    }


    /**
     * Dataprovider for the test_username_textmark_get_text testcase
     *
     * @return array of testcases
     */
    public function username_testcases() {
        $textmarks = $this->get_valid_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm => [$name, $attr, $fmt, $valid]) {
            $user = $this->get_rnd_names();
            $expected = '';

            switch($name) {
                // Test All fullname Textmark.
                case 'FULLNAME':
                    $expected = $user->fullname;
                break;
                case 'FIRSTNAME':
                    $expected = $user->firstname;
                break;

                case 'LASTNAME':
                    $expected = $user->lastname;
                break;
            }

            if (empty($expected)) {
                // it's username, so get attributes
                switch($attr) {
                    // Test All fullname Textmark.
                    case 'firstname':
                        $expected = $user->firstname;
                    break;

                    case 'lastname':
                        $expected = $user->lastname;
                    break;

                    default:
                        $expected = $user->fullname;
                    break;
                }
            }

            switch($fmt) {
                // Test All fullname Textmark.
                case 'ucase':
                    $expected = strtoupper($expected);
                break;
                case 'lcase':
                    $expected = strtolower($expected);
                break;

                case 'ucasefirst':
                    $expected = ucwords($expected);
                break;
            }

            $testcases[$tm] = array(
                '{' . $tm . '}',
                $user->firstname,
                $user->lastname,
                $expected
            );
        }

        // Test textmark with other text.
        $user = $this->get_rnd_names();
        $testcases['Test textmark with other text'] = array(
            'Test username: {USERNAME} ok',
            $user->firstname,
            $user->lastname,
            'Test username: ' . $user->fullname . ' ok'
        );

        // Test textmarks with other textmarks.
        $user = $this->get_rnd_names();
        $testcases['Test textmarks with other textmarks'] = array(
            'Test username: {USERNAME:lastname:ucase}, {USERNAME:firstname} {OTHERTEXTMARK} ok',
            $user->firstname,
            $user->lastname,
            'Test username: ' . strtoupper($user->lastname) . ', ' . $user->firstname . ' {OTHERTEXTMARK} ok',
        );

        return $testcases;
    }

    private function get_rnd_names() {
            $gen = $this->getDataGenerator();
            $record = array();
            $country = rand(0, 5);
            $firstname = rand(0, 4);
            $lastname = rand(0, 4);
            $female = rand(0, 1);
            $record['firstname'] = $gen->firstnames[($country * 10) + $firstname + ($female * 5)];
            $record['lastname'] = $gen->lastnames[($country * 10) + $lastname + ($female * 5)];
            $record['fullname'] = $record['firstname'] . ' ' . $record['lastname'];
            return (object)$record;
    }
}


class testable_simplecertificate_textmark_username extends simplecertificate_textmark_username {

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