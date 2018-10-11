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
 * Tests for mod/simplecertificate/textmarks/profile/locallib.php
 *
 * @package   simplecertificatetextmark_profile
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/tests/lib_test.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/profile/locallib.php');
require_once($CFG->dirroot . '/mod/simplecertificate/tests/generator.php');

/**
 * Unit tests for mod/simplecertificate/textmarks/profile/locallib.php
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificatetextmark_profile_locallib_testcase extends advanced_testcase {
    use simplecertificatetextmark_test_tools;
    // Use the generator helper.
    use mod_simplecertificate_test_generator;

    private $student;
    private $course;
    private $csvmatrixfilename;

    protected function setUp() {
        global $CFG;
        $this->course = $this->getDataGenerator()->create_course();
        $this->resetAfterTest();
    }

    /**
     * Tests if get_textmark_plugin get the right plugin
     */
    public function test_testable_get_textmark_plugin_get_profile_plugin() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('profile');
        $this->assertInstanceOf(simplecertificate_textmark_plugin::class, $plugin);
        $this->assertInstanceOf(simplecertificate_textmark_profile::class, $plugin);
        $this->assertEquals('Username Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin name
     */
    public function test_profile_textmark_get_name() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('profile');
        $this->assertEquals('Username Textmark Plugin', $plugin->get_name());
    }

    /**
     * Test if get the right plugin type
     */
    public function test_profile_textmark_get_type() {
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('profile');
        $this->assertEquals('profile', $plugin->get_type());
    }

    /**
     * @dataProvider get_all_textmarks
     * Test if has the right plugin textmarks
     */
    public function test_textmarks($name, $attribute, $formatter, $expected) {
        //TODO
        //$this->markTestSkipped('Very long test, skip during developing.');
        $smplcert = $this->create_instance($this->course);
        $plugin = new testable_simplecertificate_textmark_profile($smplcert);
        $this->assertEquals(
            $expected, !empty(
                $plugin->testable_is_valid_textmark($name, $attribute, $formatter)
            ));
    }

    /**
     * Test in get_certificate_text call get_textmark_plugin with
     * 'profile'
     *
     * @dataProvider get_textmarks
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_if_get_textmark_plugin_is_callabed_with_profile_param($certificatetext) {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student->id);

        $pluginmock = $this->createMock(simplecertificate_textmark_profile::class);

        $mocksmplcert = $this->create_mock_instance($this->course,
            [ 'get_textmark_plugin' ],
            [ 'certificatetext' => ['text' => $certificatetext]]
        );

        $mocksmplcert->expects($this->once())
            ->method('get_textmark_plugin')
            ->with($this->equalTo('profile'))
            ->willReturn($pluginmock);

        $pluginmock->expects($this->once())
            ->method('get_text')
            ->with($this->equalTo($certificatetext));

        $mocksmplcert->testable_get_certificate_text($mocksmplcert->get_issue());
    }

    /**
     * test textmarks replacements
     *
     * @dataProvider profile_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_profile_textmark_get_text(
        $certificatetext,
        $profilename,
        $profilevalue,
        $expected
    ) {

        $user = $this->getDataGenerator()->create_user([
            $profilename => $profilevalue,
        ]);
        $this->getDataGenerator()->enrol_user($this->course->id, $user->id, 'student');
        $this->setUser($user);
        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('profile');
        $result = $plugin->get_text($certificatetext);
        $this->assertEquals($expected, $result);
    }

    public function test_profile_textmark_get_text_custom_fields() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $DB->insert_record('user_info_field', [
            'shortname' => 'nickname', 'name' => 'Nickname', 'required' => 0,
            'visible' => 1, 'locked' => 0, 'categoryid' => 1, 'datatype' => 'text'
        ]);

        // Create some student accounts.
        $user = $this->getDataGenerator()->create_user();

        $nickname = random_string(random_int(3, 20));

        // Hermione has all available custom fields filled (of course she has).
        profile_save_data((object)['id' => $user->id, 'profile_field_nickname' => $nickname]);

        $this->getDataGenerator()->enrol_user($this->course->id, $user->id, 'student');
        $this->setUser($user);

        $smplcert = $this->create_instance($this->course);
        $plugin = $smplcert->testable_get_textmark_plugin('profile');

        $result = $plugin->get_text("{PROFILE:nickname}");
        $this->assertEquals($nickname, $result);

        $result = $plugin->get_text("{PROFILE:nickname:ucase}");
        $this->assertEquals(strtoupper($nickname), $result);

        $result = $plugin->get_text("{PROFILE:nickname:lcase}");
        $this->assertEquals(strtolower($nickname), $result);

        $result = $plugin->get_text("{PROFILE:nickname:ucasefirst}");
        $this->assertEquals(ucwords($nickname), $result);

    }

    // ================= DATA PROVIDERS ================

    public function get_all_textmarks() {
        return $this->get_all_plugins_textmarks(__DIR__ . '/fixtures/all_textmarks_matrix.csv');
    }

    public function get_valid_textmarks() {
        return $this->get_valid_plugins_textmarks(__DIR__ . '/fixtures/all_textmarks_matrix.csv');
    }

    /**
     * Dataprovider for the test_if_get_textmark_plugin_is_callabed_with_profile_param testcase
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
     * Dataprovider for the test_profile_textmark testcase
     *
     * @return array of testcases
     */
    public function profile_testcases() {
        $textmarks = $this->get_valid_textmarks();
        $testcases = array();
        foreach ($textmarks as $tm => [$name, $attr, $fmt, $valid]) {
            // TODO
            // I don't know how to test user image.
            if ($name == 'USERIMAGE' || $attr == 'userimage') {
                continue;
            }
            $user = $this->getDataGenerator()->create_user();
            $value = null;
            $expected = null;
            $attribute = null;

            if (!empty($attr)) {
                $value = $user->$attr;
                $attribute = $attr;
            } else {
                $n = strtolower($name);
                $value = $user->$n;
                $attribute = $n;
            }

            if (empty($value)) {
                if ($attribute === 'country') {
                    $value = 'BR';
                    $expected = get_string('BR', 'countries');
                } else {
                    $value = random_string(random_int(3, 10));
                }
            }

            if (empty($expected)) {
                $expected = $value;
            }

            switch ($fmt) {
                case simplecertificate_textmark_profile::LOWER_CASE_FORMATTER:
                    $expected = strtolower($expected);
                break;

                case simplecertificate_textmark_profile::UPPER_CASE_FORMATTER:
                    $expected = strtoupper($expected);
                break;

                case simplecertificate_textmark_profile::UPPER_CASE_FIRST_FORMATTER:
                    $expected = ucwords($expected);
                break;
            }
            $testcases[$tm] = array(
                '{' . $tm . '}',
                $attribute,
                $value,
                $expected
            );
        }

        // Test textmark with other text.
        $user = $this->getDataGenerator()->create_user();
        $testcases['Test textmark with other text'] = array(
            'Test profile: {PROFILE:email:ucase} ok',
            'email',
            $user->email,
            'Test profile: ' . strtoupper($user->email) . ' ok'
        );

        // Test textmark with other textmarks.
        $user = $this->getDataGenerator()->create_user();
        if (!empty($user->country)) {
            $countryname = get_string($user->country, 'countries');
            $testcases['Test textmark with other text'] = array(
                'Test profile: {PROFILE:country:ucase}, {PROFILE:country}, {OTHERTESTMARK} ok',
                'country',
                $user->country,
                'Test profile: ' . strtoupper($countryname) . ', ' . $countryname . ', {OTHERTESTMARK} ok',
            );
        }

        return $testcases;
    }


}

class testable_simplecertificate_textmark_profile extends simplecertificate_textmark_profile {

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