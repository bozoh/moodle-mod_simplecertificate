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
 * Genarator tests.
 *
 * @package    mod_simplecertificate
 * @copyright  2013 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Genarator tests class.
 *
 * @package    mod_simplecertificate
 * @copyright  2013 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplecertificate_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('simplecertificate', array('course' => $course->id)));
        $cert = $this->getDataGenerator()->create_module('simplecertificate', array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('simplecertificate', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('simplecertificate', array('course' => $course->id, 'id' => $cert->id)));

        $params = array('course' => $course->id, 'name' => 'One more certificate');
        $cert = $this->getDataGenerator()->create_module('simplecertificate', $params);
        $this->assertEquals(2, $DB->count_records('simplecertificate', array('course' => $course->id)));
        $this->assertEquals('One more certificate', $DB->get_field_select('simplecertificate', 'name', 'id = :id', array('id' => $cert->id)));
    }

   
}
