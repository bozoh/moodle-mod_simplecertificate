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
     */
    public function test_textmarks() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('username');
        $expected = $this->get_all_plugins_textmarks();
        $textmarks = $plugin->get_textmarks();
        $this->assertCount(count($expected), $textmarks);
        foreach ($textmarks as $tm) {
            $this->assertContains($tm, $expected);
        }
    }

    /**
     * Test in get_certificate_text call get_textmark_plugin with
     * 'username'
     *
     * @dataProvider textmark_testcases
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

    /**
     * Dataprovider for the test_username_textmark testcase
     *
     * @return array of testcases
     */
    public function username_testcases() {
        $textmarks = $this->get_all_plugins_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm) {
            $user = $this->get_rnd_names();

            switch($tm) {
                // Test All fullname Textmark.
                case '{USERNAME}':
                case '{USERNAME:fullname}':
                case '{FULLNAME}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        $user->fullname
                    );
                break;
                case '{USERNAME:ucase}':
                case '{USERNAME:fullname:ucase}':
                case '{FULLNAME:ucase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtoupper($user->fullname)
                    );
                break;
                case '{USERNAME:lcase}':
                case '{USERNAME:fullname:lcase}':
                case '{FULLNAME:lcase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtolower($user->fullname)
                    );
                break;
                case '{USERNAME:ucasefirst}':
                case '{USERNAME:fullname:ucasefirst}':
                case '{FULLNAME:ucasefirst}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        ucwords($user->fullname)
                    );
                break;

                // Test All firstname Textmark.
                case '{USERNAME:firstname}':
                case '{FIRSTNAME}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        $user->firstname
                    );
                break;
                case '{USERNAME:firstname:ucase}':
                case '{FIRSTNAME:ucase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtoupper($user->firstname)
                    );
                break;
                case '{USERNAME:firstname:lcase}':
                case '{FIRSTNAME:lcase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtolower($user->firstname)
                    );
                break;
                case '{USERNAME:firstname:ucasefirst}':
                case '{FIRSTNAME:ucasefirst}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        ucwords($user->firstname)
                    );
                break;

                // Test All lastname Textmark.
                case '{USERNAME:lastname}':
                case '{lastname}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        $user->lastname
                    );
                break;
                case '{USERNAME:lastname:ucase}':
                case '{lastname:ucase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtoupper($user->lastname)
                    );
                break;
                case '{USERNAME:lastname:lcase}':
                case '{lastname:lcase}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        strtolower($user->lastname)
                    );
                break;
                case '{USERNAME:lastname:ucasefirst}':
                case '{lastname:ucasefirst}':
                    $testcases[$tm] = array(
                        $tm,
                        $user->firstname,
                        $user->lastname,
                        ucwords($user->lastname)
                    );
                break;
            }
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

    /**
     * Dataprovider for the test_if_get_textmark_plugin_is_callabed_with_username_param
     * testcase
     *
     * @return array of testcases
     */
    public function textmark_testcases() {
        $textmarks = $this->get_all_plugins_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm) {
            $testcases[$tm] = [$tm];
        }
        return $testcases;
    }

    private function get_all_plugins_textmarks() {
        return array(
            '{USERNAME}',
            '{USERNAME:ucase}',
            '{USERNAME:lcase}',
            '{USERNAME:ucasefirst}',
            '{USERNAME:fullname}',
            '{USERNAME:fullname:ucase}',
            '{USERNAME:fullname:lcase}',
            '{USERNAME:fullname:ucasefirst}',
            '{FULLNAME}',
            '{FULLNAME:ucase}',
            '{FULLNAME:lcase}',
            '{FULLNAME:ucasefirst}',
            '{USERNAME:firstname}',
            '{USERNAME:firstname:ucase}',
            '{USERNAME:firstname:lcase}',
            '{USERNAME:firstname:ucasefirst}',
            '{FIRSTNAME}',
            '{FIRSTNAME:ucase}',
            '{FIRSTNAME:lcase}',
            '{FIRSTNAME:ucasefirst}',
            '{USERNAME:lastname}',
            '{USERNAME:lastname:ucase}',
            '{USERNAME:lastname:lcase}',
            '{USERNAME:lastname:ucasefirst}',
            '{LASTNAME}',
            '{LASTNAME:ucase}',
            '{LASTNAME:lcase}',
            '{LASTNAME:ucasefirst}'
        );
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
