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
 * Base class for unit tests for mod_simplecertificate.
 *
 * @package    mod_simplecertificate
 * @category   phpunit
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
require_once(__DIR__ . '/fixtures/testable_simplecertificate.php');

use PHPUnit\Framework\MockObject\Generator as MockGenerator;
use PHPUnit\Framework\MockObject\Matcher\AnyInvokedCount as AnyInvokedCountMatcher;
use PHPUnit\Framework\MockObject\Matcher\InvokedAtIndex as InvokedAtIndexMatcher;
use PHPUnit\Framework\MockObject\Matcher\InvokedAtLeastCount as InvokedAtLeastCountMatcher;
use PHPUnit\Framework\MockObject\Matcher\InvokedAtLeastOnce as InvokedAtLeastOnceMatcher;
use PHPUnit\Framework\MockObject\Matcher\InvokedAtMostCount as InvokedAtMostCountMatcher;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ConsecutiveCalls as ConsecutiveCallsStub;
use PHPUnit\Framework\MockObject\Stub\Exception as ExceptionStub;
use PHPUnit\Framework\MockObject\Stub\ReturnArgument as ReturnArgumentStub;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback as ReturnCallbackStub;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf as ReturnSelfStub;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\MockObject\Stub\ReturnValueMap as ReturnValueMapStub;

/**
 * Generator helper trait.
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait mod_simplecertificate_test_generator {

    /**
     * Convenience function to create a testable instance of an simplecertificate.
     *
     * @param array $params Array of parameters to pass to the generator
     * @return testable_simplecertificate Testable wrapper around the simplecertificate
     *         class.
     */
    protected function create_instance($course, $params = [], $options = []) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
        $params['course'] = $course->id;
        $instance = $generator->create_instance($params, $options);
        $cm = get_coursemodule_from_instance('simplecertificate', $instance->id);
        $context = context_module::instance($cm->id);

        return new testable_simplecertificate($context, $cm, $course);
    }


    /**
     * Convenience function to create a stubs of simplecertificate
     * methods.
     *
     * @param course $course A course
     * @param array $methods The methods to be stubbed
     * @param array $params Array of parameters to pass to the generator
     * @param array $options Array of options to pass to the generator
     * @return testable_simplecertificate Testable wrapper around the simplecertificate
     *         class class with stubbed methods.
     */
    protected function create_mock_instance($course, $methods = [], $params = [], $options = []) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_simplecertificate');
        $params['course'] = $course->id;
        $instance = $generator->create_instance($params, $options);
        $cm = get_coursemodule_from_instance('simplecertificate', $instance->id);
        $context = context_module::instance($cm->id);

        $smplcertmock = $this->getMockBuilder(testable_simplecertificate::class)
            ->setConstructorArgs(array($context, $cm, $course))
            ->setMethods($methods)
            ->getMock();

        return $smplcertmock;
    }

}
