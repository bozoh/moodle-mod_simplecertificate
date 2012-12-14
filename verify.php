<?php

/**
 * Verify an issued certificate by code
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$code = required_param('code', PARAM_ALPHANUMEXT); // Issed Code

if (!$code) {
    print_error(get_string('invalidcode','simplecertificate'));
}

if (!$issuedcert = $DB->get_record("simplecertificate_issues", array('code' => $code))) {
    print_error(get_string('invalidcode','simplecertificate'));
}

if ($user = $DB->get_record('user', array('id'=>$issuedcert->userid))) {
    $username = fullname($user);
} else {
    $username = $issuedcer->username;
}


$context = context_system::instance();
$PAGE->set_url('/mod/simplecertificate/verify.php', array('code' => $code));
$PAGE->set_context($context);
$PAGE->set_title(get_string('certificateverification', 'simplecertificate'));
$PAGE->set_heading(get_string('certificateverification', 'simplecertificate'));
$PAGE->set_pagelayout('base');
//TODO put re-capctha
$strto = get_string('awardedto', 'simplecertificate');
$strdate = get_string('issueddate', 'simplecertificate');
$strcourse = get_string('course');
$strcode = get_string('code', 'simplecertificate');

//Add to log
add_to_log($context->instanceid, 'simplecertificate', 'verify', "verify.php?code=$code", '$issuedcert->id');

$table = new html_table();
$table->width = "95%";
$table->tablealign = "center";
$table->head  = array($strto, $strcourse, $strdate, $strcode);
$table->align = array("center", "center", "center", "center");
$table->data[] = array ($username, $issuedcert->coursename, userdate($issuedcert->timecreated), $issuedcert->code);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('certificateverification', 'simplecertificate'));
echo html_writer::table($table);
echo $OUTPUT->footer();