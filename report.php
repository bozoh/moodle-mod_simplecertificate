<?php

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

$id   = required_param('id', PARAM_INT); // Course module ID

if (!$cm = get_coursemodule_from_id('simplecertificate', $id)) {
    print_error('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('Course is misconfigured');
}

if (!$certificate = $DB->get_record('simplecertificate', array('id'=> $cm->instance))) {
    print_error('Certificate ID was incorrect');
}


// Requires a course login
require_course_login($course->id, false, $cm);


// Check capabilities
$context = context_module::instance($cm->id);
require_capability('mod/simplecertificate:manage', $context);
$url = new moodle_url('/mod/simplecertificate/view.php', array('id'=>$id, 'tab'=>simplecertificate::ISSUED_CERTIFCADES_VIEW));
redirect($url);
die;
