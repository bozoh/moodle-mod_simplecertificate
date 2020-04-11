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
 * Verify an issued certificate by code
 *
 * @package mod
 * @subpackage simplecertificate
 * @copyright 2014 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('verify_form.php');
require_once('lib.php');

$code = optional_param('code', null, PARAM_ALPHANUMEXT); // Issed Code.

$context = context_system::instance();
$PAGE->set_url('/mod/simplecertificate/verify.php', array('code' => $code));
$PAGE->set_context($context);
$PAGE->set_title(get_string('certificateverification', 'simplecertificate'));
$PAGE->set_heading(get_string('certificateverification', 'simplecertificate'));
$PAGE->set_pagelayout('base');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificateverification', 'simplecertificate'));

$verifyform = new verify_form();

if (!$verifyform->get_data()) {
    if ($code) {
        $verifyform->set_data(array('code' => $code));
    }

    $verifyform->display();

} else {
    $issuedcert = get_issued_cert($code);

    $user = $DB->get_record('user', array('id' => $issuedcert->userid));
    if ($user) {
        $username = fullname($user);
    } else {
        $username = get_string('notavailable');
    }

    $strto = get_string('awardedto', 'simplecertificate');
    $strdate = get_string('issueddate', 'simplecertificate');
    $strcode = get_string('code', 'simplecertificate');

    $table = new html_table();
    $table->width = "95%";
    $table->tablealign = "center";
    $table->head = array(get_string('course'), $strto, $strdate, $strcode);
    $table->align = array("left", "left", "center", "center");
    $coursename = get_course_name($issuedcert);
    $table->data[] = array($coursename, $username,
            userdate($issuedcert->timecreated) . simplecertificate_print_issue_certificate_file($issuedcert), $issuedcert->code);
    echo html_writer::table($table);

    // Add to log.
    $event = \mod_simplecertificate\event\certificate_verified::create(array(
            'objectid' => $issuedcert->id,
            'context' => $context,
            'relateduserid' => $issuedcert->userid,
            'other' => array( 'issuedcertcode' => $issuedcert->code)
        )
    );
    $event->trigger();


}

echo $OUTPUT->footer();

function get_issued_cert($code = null) {
    global $DB;

    $issuedcert = $DB->get_record("simplecertificate_issues", array('code' => $code));
    if (!$issuedcert) {
        print_error('invalidcode', 'simplecertificate');
    }
    return $issuedcert;
}

/**
 * Try to get course name, or return 'course not found!'
 *
 * @param issuedcert Issued certificate object
 */
function get_course_name($issuedcert) {
    global $DB;

    if ($issuedcert->coursename) {
        return $issuedcert->coursename;
    }

    $cm = get_coursemodule_from_instance('simplecertificate', $issuedcert->certificateid);
    if ($cm) {
        $course = $DB->get_record('coruse', array('id' => $cm->course));
        if ($course) {
            return $course->fullname;
        }
    }

    return get_string('coursenotfound', 'simplecertificate');
}





