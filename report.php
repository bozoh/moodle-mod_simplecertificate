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
 * Handles viewing the report
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id   = required_param('id', PARAM_INT); // Course module ID.

if (!$cm = get_coursemodule_from_id('simplecertificate', $id)) {
    print_error('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('Course is misconfigured');
}

if (!$certificate = $DB->get_record('simplecertificate', array('id' => $cm->instance))) {
    print_error('Certificate ID was incorrect');
}


// Requires a course login.
require_course_login($course->id, false, $cm);


// Check capabilities.
$context = context_module::instance($cm->id);
require_capability('mod/simplecertificate:manage', $context);
$url = new moodle_url('/mod/simplecertificate/view.php', array('id' => $id, 'tab' => simplecertificate::ISSUED_CERTIFCADES_VIEW));
redirect($url);
die;
