<?php

/**
 * Handles viewing a certificate
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>, Chardelle Busch, Mark Nelson <mark@moodle.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once("$CFG->dirroot/mod/simplecertificate/lib.php");
require_once("$CFG->libdir/pdflib.php");
require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (!$cm = get_coursemodule_from_id('simplecertificate', $id)) {
    print_error('Course Module ID was incorrect');
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');
}
if (!$certificate = $DB->get_record('simplecertificate', array('id'=> $cm->instance))) {
    print_error('course module is incorrect');
}

require_login($course->id, false, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/simplecertificate:view', $context);

// log update
add_to_log($course->id, 'simplecertificate', 'view', "view.php?id=$cm->id", $certificate->id, $cm->id);
$simplecertificate=new simplecertificate($certificate, $context);

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/simplecertificate/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

// Set the context
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if (($edit != -1) and $PAGE->user_allowed_editing()) {
     $USER->editing = $edit;
}

// Add block editing button
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $strsubmit = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $url = new moodle_url($CFG->wwwroot . '/mod/simplecertificate/view.php', array('id' => $cm->id, 'edit' => $editvalue));
    $PAGE->set_button($OUTPUT->single_button($url, $strsubmit));
}

// Check if the user can view the certificate
if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context)) {
    if ($simplecertificate->get_course_time() < $certificate->requiredtime) {
        $a = new stdClass;
        $a->requiredtime = $certificate->requiredtime;
        notice(get_string('requiredtimenotmet', 'simplecertificate', $a), "$CFG->wwwroot/course/view.php?id=$course->id");
        die;
    }
}

// Create new certificate record, or return existing record
$certrecord = $simplecertificate->get_issue($USER);


if (empty($action)) { // Not displaying PDF
    echo $OUTPUT->header();

    /// find out current groups mode
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/simplecertificate/view.php?id=' . $cm->id);
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    if (has_capability('mod/simplecertificate:manage', $context)) {
        $numusers = count(simplecertificate_get_issues($certificate->id, 'ci.timecreated ASC', $groupmode, $cm));
        $url = html_writer::tag('a', get_string('viewcertificateviews', 'simplecertificate', $numusers),
            array('href' => $CFG->wwwroot . '/mod/simplecertificate/report.php?id=' . $cm->id));
        echo html_writer::tag('div', $url, array('class' => 'reportlink'));
    }

    if (!empty($certificate->intro)) {
        echo $OUTPUT->box(format_module_intro('simplecertificate', $certificate, $cm->id), 'generalbox', 'intro');
    }

    if ($attempts = $simplecertificate->get_attempts()) {
        echo $simplecertificate->print_attempts($attempts);
    }
    if ($certificate->delivery == 0)    {
        $str = get_string('openwindow', 'simplecertificate');
    } elseif ($certificate->delivery == 1)    {
        $str = get_string('opendownload', 'simplecertificate');
    } elseif ($certificate->delivery == 2)    {
        $str = get_string('openemail', 'simplecertificate');
    }
    echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
    $linkname = get_string('getcertificate', 'simplecertificate');
    // Add to log, only if we are reissuing
    add_to_log($course->id, 'simplecertificate', 'view', "view.php?id=$cm->id", $certificate->id, $cm->id);

    $link = new moodle_url('/mod/simplecertificate/view.php', array ('id' => $cm->id, 'action' => 'get'));
    $button = new single_button($link, $linkname);
    $button->add_action(new popup_action('click', $link, 'view'.$cm->id, array('height' => 600, 'width' => 800)));

    echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
    echo $OUTPUT->footer($course);
    exit;
} else { // Output to pdf
    $simplecertificate->output_pdf($certrecord);
}


