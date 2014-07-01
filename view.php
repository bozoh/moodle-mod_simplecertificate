<?php

/**
 * Handles viewing a certificate
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>, Chardelle Busch, Mark Nelson <mark@moodle.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("$CFG->dirroot/mod/simplecertificate/lib.php");
require_once("$CFG->libdir/pdflib.php");
require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");

$id = required_param('id', PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$tab = optional_param('tab', simplecertificate::DEFAULT_VIEW, PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('simplecertificate', 'perpage'), PARAM_INT);
$orderby = optional_param('orderby', 'username', PARAM_RAW);
$issuelist = optional_param('issuelist', null, PARAM_ALPHA);
$selectedusers = optional_param_array('selectedusers', null, PARAM_INT);


if (!$cm = get_coursemodule_from_id( 'simplecertificate', $id)) {
	print_error('Course Module ID was incorrect');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
	print_error('course is misconfigured');
}

if (!$certificate = $DB->get_record('simplecertificate', array('id' => $cm->instance))) {
	print_error('course module is incorrect');
}

$context = context_module::instance ($cm->id);
$url = new moodle_url('/mod/simplecertificate/view.php', array (
		'id' => $cm->id,
		'tab' => $tab,
		'page' => $page,
		'perpage' => $perpage,
));

if ($type) {
	$url->param('type', $type);
}

if ($orderby) {
	$url->param ('orderby', $orderby);
}

if ($action) {
	$url->param ('action', $action);
}

if ($issuelist) {
	$url->param ('issuelist', $issuelist);
}

// Initialize $PAGE, compute blocks
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_cm($cm);

require_login( $course->id, false, $cm);
require_capability('mod/simplecertificate:view', $context);
$canmanage = has_capability('mod/simplecertificate:manage', $context);



// log update
$simplecertificate = new simplecertificate($context, $cm, $course);
$simplecertificate->set_instance($certificate);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

switch ($tab) {
	case $simplecertificate::ISSUED_CERTIFCADES_VIEW :
		$simplecertificate->view_issued_certificates($url);
	break;
	
	case $simplecertificate::BULK_ISSUE_CERTIFCADES_VIEW :
		$simplecertificate->view_bulk_certificates($url, $selectedusers);
	break;
	
	default :
		$simplecertificate->view_default($url, $canmanage);
	break;
}
