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
 * List of certificates page.
 *
 * @package    mod_simplecertificate
 * @copyright  2023 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = optional_param('id', 0, PARAM_INT);

$title = get_string('certificatestitle', 'mod_simplecertificate');
if (!$courseid) {
    require_login(null, false);

    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_url('/mod/simplecertificate/certificates.php');
} else {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

    require_login($course);

    $coursename = format_string($course->fullname, true, ['context' => context_course::instance($course->id)]);
    $title = $coursename . ': ' . get_string('certificatestitle', 'mod_simplecertificate');

    $PAGE->set_context(context_course::instance($course->id));
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_heading($coursename);
    $PAGE->set_url('/mod/simplecertificate/certificates.php', ['id' => $course->id]);
}

$PAGE->set_title($title);

$PAGE->navbar->add(get_string('certificates', 'mod_simplecertificate'));

$viewrenderable = new \mod_simplecertificate\output\certificates($courseid);
$renderer = $PAGE->get_renderer('mod_simplecertificate');

echo $OUTPUT->header();

echo $renderer->render($viewrenderable);

echo $OUTPUT->footer();
