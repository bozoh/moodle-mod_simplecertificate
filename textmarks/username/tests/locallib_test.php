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
require_once($CFG->dirroot . '/mod/simplecertificate/tests/generator.php');

/**
 * Unit tests for mod/simplecertificate/textmarks/username/locallib.php
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificatetextmark_username_locallib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_simplecertificate_generator;

    /**
     * Test submission_is_empty
     *
     * @dataProvider username_testcases
     * @param string $certificatetext The certificate text
     * @param bool $expected The expected return value
     */
    public function test_username_textmark($certificatetext) {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $expected = null;
        switch($certificatetext) {
            case "{USERNAME:firstname}":
                $expected = $student->firstname;
            break;
            case "{USERNAME:lastname}":
                $expected = $student->lastname;
            break;
            // ...{USERNAME}
            default:
                $expected = fullname($student);
            break;
        }
        $smplcert = $this->create_instance($course, [
                'certificatetext' => $certificatetext,
            ]
        );

        $this->setUser($student->id);

        $plugin = $smplcert->get_textmark_plugin('username');
        $result = $plugin->get_text();
        $this->assertTrue($result === $expected);
    }

    /**
     * Dataprovider for the test_username_textmark testcase
     *
     * @return array of testcases
     */
    public function username_testcases() {
        return [
            '{USERNAME}' => ['{USERNAME}'],
            '{USERNAME:firstname}' => ['{USERNAME:firstname}', true],
            '{USERNAME:lastname}' => ['{USERNAME:lastname}', true],
            // 'Value 0' => [0, false],
            // 'String 0' => ['0', false],
            // 'Text' => ['Ai! laurië lantar lassi súrinen, yéni únótimë ve rámar aldaron!', false]
        ];
    }
}
