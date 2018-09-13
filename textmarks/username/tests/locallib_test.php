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
// require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');
// require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/username/locallib.php');
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
     * Tests if get_textmark_plugin get the right textmark plugin
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
     * Test in get_certificate_text call get_textmark_plugin with
     * 'username'
     *
     * @dataProvider textmark_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_if_get_textmark_plugin_is_callabed_with_username_param($certificatetext) {
        $this->setUser($this->student->id);
        $mocksmplcert = $this->create_mock_instance($this->course, [
                'get_textmark_plugin'
        ]);
        $mocksmplcert->expects($this->once())
            ->method('get_textmark_plugin')
            ->with($this->equalTo('username'));

        $mocksmplcert->testable_get_certificate_text($mocksmplcert->get_issue());
        $this->markTestIncomplete(
            'O teste está pronto, mas o get certificate text não'
        );
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
        // , [
        //     'certificatetext' => array('text' => $certificatetext),
        // ]);
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
        $user1 = $this->get_rnd_names();
        $user2 = $this->get_rnd_names();;
        $user3 = $this->get_rnd_names();;

        $retval = array(
            '{USERNAME}' => [
                '{USERNAME}',
                $user1->firstname,
                $user1->lastname,
                $user1->fullname
            ],
            '{USERNAME:firstname}' => [
                '{USERNAME:firstname}',
                $user2->firstname,
                $user2->lastname,
                $user2->firstname
            ],
            '{USERNAME:lastname}' => [
                '{USERNAME:lastname}',
                $user3->firstname,
                $user3->lastname,
                $user3->lastname
            ],
            'Test username: {USERNAME} ok' => [
                'Test username: {USERNAME} ok',
                $user3->firstname,
                $user3->lastname,
                'Test username: ' . $user3->fullname . ' ok',
            ],
            'Test username: {USERNAME:lastname} {USERNAME:firstname} {OTHERTEXTMARK} ok' => [
                'Test username: {USERNAME:lastname} {USERNAME:firstname} {OTHERTEXTMARK} ok',
                $user2->firstname,
                $user2->lastname,
                'Test username: ' . $user2->lastname . ' ' . $user2->firstname . ' {OTHERTEXTMARK} ok',
            ]
        );
        // 'Value 0' => [0, false],
        // 'String 0' => ['0', false],
        // 'Text' => ['Ai! laurië lantar lassi súrinen, yéni únótimë ve rámar aldaron!', false]
        return $retval;
    }

    /**
     * Dataprovider for the test_if_get_textmark_plugin_is_callabed_with_username_param
     * testcase
     *
     * @return array of testcases
     */
    public function textmark_testcases() {
        $retval = array(
            '{USERNAME}' => ['{USERNAME}'],
            '{USERNAME:firstname}' => ['{USERNAME:firstname}'],
            '{USERNAME:lastname}' => ['{USERNAME:lastname}'],
            '{USERNAME:lastname}' => ['{USERNAME:lastname}'],
        );
        // 'Value 0' => [0, false],
        // 'String 0' => ['0', false],
        // 'Text' => ['Ai! laurië lantar lassi súrinen, yéni únótimë ve rámar aldaron!', false]
        return $retval;
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
