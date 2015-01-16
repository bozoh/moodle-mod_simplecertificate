<?php

/**
 * Verify an issued certificate by code
 * 
 * @package mod
 * @subpackage simplecertificate
 * @copyright 2014 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ('verify_form.php');
require_once ('lib.php');

// optional_param('id', $USER->id, PARAM_INT);
$code = optional_param('code', null, PARAM_ALPHANUMEXT); // Issed Code

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
    if ($code)
        $verifyform->set_data(array('code' => $code));
    
    $verifyform->display();

} else {
    $issuedcert = get_issued_cert($code);
    
    if ($user = $DB->get_record('user', array('id' => $issuedcert->userid))) {
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
    //Try to get coursename
    if(!$coursename = $DB->get_field('simplecertificate', 'coursename', array('id' => $issuedcert->certificateid))) {
        $coursename = get_string('coursenotfound', 'simplecertificate');
    }
    $table->data[] = array($coursename, $username, 
            userdate($issuedcert->timecreated) . simplecertificate_print_issue_certificate_file($issuedcert), $issuedcert->code);
    echo html_writer::table($table);
    
    // Add to log
    //add_to_log($context->instanceid, 'simplecertificate', 'verify', "verify.php?code=$code", '$issuedcert->id');
    
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
    
    if (!$issuedcert = $DB->get_record("simplecertificate_issues", array('code' => $code))) {
        print_error(get_string('invalidcode', 'simplecertificate'));
    }
    return $issuedcert;
}


	
