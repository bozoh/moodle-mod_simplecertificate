<?php

// This file is part of Certificate module for Moodle - http://moodle.org/
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
 * Simple Certificate module core interaction API
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>, Mark Nelson <mark@moodle.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');



/**
 * Adds a simplecertificate instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 * @param stdClass $data
 * @param mod_assign_mod_form $form
 * @return int The instance id of the new simplecertificate
 */
function simplecertificate_add_instance(stdclass $data) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
    
    $context = context_module::instance($data->coursemodule);
    $simplecertificate = new simplecertificate($context, null, null);
    
    return $simplecertificate->add_instance($data);

}

/**
 * Update certificate instance.
 *
 * @param stdClass $certificate
 * @return bool true
 */
function simplecertificate_update_instance(stdclass $data) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

    $context = context_module::instance($data->coursemodule);
    $simplecertificate = new simplecertificate($context, null, null);
    
    return $simplecertificate->update_instance($data);

}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id
 * @return bool true if successful
 */
function simplecertificate_delete_instance($id) {
    global $DB, $CFG;

    global $CFG;
    require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
    $cm = get_coursemodule_from_instance('simplecertificate', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    
    $simplecertificate = new simplecertificate($context, null, null);
    return $simplecertificate->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified certificate
 * and clean up any related data.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function simplecertificate_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'simplecertificate');
    $status = array();

    if (!empty($data->reset_certificate)) {
        $timedeleted = time();
        $certificates = $DB->get_records('simplecertificate', array('course' => $data->courseid));

        foreach ($certificates as $certificate) {
            $issuecertificates = $DB->get_records('simplecertificate_issues', array('certificateid' => $certificate->id, 'timedeleted' => null));

            foreach ($issuecertificates as $issuecertificate) {
                $issuecertificate->timedeleted = $timedeleted;
                if (!$DB->update_record('simplecertificate_issues', $issuecertificate)) {
                    print_erro(get_string('cantdeleteissue','simplecertificate'));
                }
            }
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('modulenameplural', 'simplecertificate'), 'error' => false);
    }

    // Updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('simplecertificate', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the certificate.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param $mform form passed by reference
 */
function simplecertificate_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'simplecertificateheader', get_string('modulenameplural', 'simplecertificate'));
    $mform->addElement('advcheckbox', 'reset_simplecertificate', get_string('deletissuedcertificates', 'simplecertificate'));
}

/**
 * Course reset form defaults.
 *
 * Written by Jean-Michel Vedrine
 *
 * @param stdClass $course
 * @return array
 */
function simplecertificate_reset_course_form_defaults($course) {
    return array('reset_simplecertificate' => 1);
}

/**
 * Returns information about received certificate.
 * Used for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $certificate
 * @return stdClass the user outline object
 */
function simplecertificate_user_outline($course, $user, $mod, $certificate) {
    global $DB;

    $result = new stdClass;
    if ($issue = $DB->get_record('simplecertificate_issues', array('certificateid' => $certificate->id, 'userid' => $user->id, 'timedeleted' => null ))) {
        $result->info = get_string('issued', 'simplecertificate');
        $result->time = $issue->timecreated;
    } else {
        $result->info = get_string('notissued', 'simplecertificate');
    }

    return $result;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of certificate.
 *
 * @param int $certificateid
 * @return stdClass list of participants
 */
function simplecertificate_get_participants($certificateid) {
    global $DB;

    $sql = "SELECT DISTINCT u.id, u.id
            FROM {user} u, {simplecertificate_issues} a
            WHERE a.certificateid = :certificateid
            AND u.id = a.userid
            AND timedeleted IS NULL";
    return  $DB->get_records_sql($sql, array('certificateid' => $certificateid));
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function simplecertificate_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Function to be run periodically according to the moodle cron
 */
function simplecertificate_cron () {
    global $CFG, $DB;
    mtrace('Removing old issed certificates... ');
    $lifetime = get_config('simplecertificate', 'certlifetime');

    if ($lifetime <= 0 ) {
        return true;
    }

    $month = 2629744;
    $timenow   = time();
    $delta = $lifetime * $month;
    $timedeleted = $timenow - $delta;


    if (!$DB->delete_records_select('simplecertificate_issues', 'timedeleted <= ?', array($timedeleted))) {
        return false;
    }
    mtrace('done');
    return true;
}

/**
 * Serves certificate issues files, only in admin page.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool|nothing false if file not found, does not return anything if found - just send the file
 */
function simplecertificate_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;

   // require_login(null, false);
    require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
    require_once($CFG->libdir.'/filelib.php');
        
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    
    //Get issued certificate
    $issuedcertid = (int)array_shift($args);
    if (!$issuedcert = $DB->get_record('simplecertificate_issues', array('id' => $issuedcertid))){
        print_error('issuedcertificatenotfound','simplecertificate');
        return false;
    }
    
    $fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuedcert);
    $fs = get_file_storage();
    
    //Verify if file exists
    if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
        $fileinfo['filepath'], $fileinfo['filename'])){
        print_error(get_string('filenotfound', 'simplecertificate', $fileinfo['filename']));
        return false;
    }
    
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
                          $fileinfo['filepath'], $fileinfo['filename']);
    
    $url = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                           $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'], true);
    add_to_log($course->id, 'simplecertificate', 'download', $url->out_as_local_url(false), get_string('issueddownload', 'simplecertificate', $issuedcert->id), $cm->id, $USER->id);
    
    send_stored_file($file, 0, 0, true, $options);
}

/**
 * Get the course outcomes for for mod_form print outcome.
 *
 * @return array
 */
function simplecertificate_get_outcomes() {
    global $COURSE, $DB;

    // get all outcomes in course
    $grade_seq = new grade_tree($COURSE->id, false, true, '', false);
    if ($grade_items = $grade_seq->items) {
        // list of item for menu
        $printoutcome = array();
        foreach ($grade_items as $grade_item) {
            if (isset($grade_item->outcomeid)){
                $itemmodule = $grade_item->itemmodule;
                $printoutcome[$grade_item->id] = $itemmodule . ': ' . $grade_item->get_name();
            }
        }
    }
    if (isset($printoutcome)) {
        $outcomeoptions['0'] = get_string('no');
        foreach ($printoutcome as $key => $value) {
            $outcomeoptions[$key] = $value;
        }
    } else {
        $outcomeoptions['0'] = get_string('nooutcomes', 'simplecertificate');
    }

    return $outcomeoptions;
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function simplecertificate_get_view_actions() {
    return array('view', 'view all', 'view report', 'verify');
}

/**
 * Used for course participation report (in case certificate is added).
 *
 * @return array
 */
function simplecertificate_get_post_actions() {
    return array('received');
}

/**
 * Update the event if it exists, else create
 */
function simplecertificate_send_event($certificate){
    global $DB, $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    if ($event= $DB->get_record('event', array('modulename'=>'simplecertificate', 'instance'=>$certificate->id))) {
    	$calendarevent = calendar_event::load($event->id);
        $calendarevent->name = $certificate->name;
        $calendarevent->update($calendarevent);
    } else {
        $event = new stdClass;
        $event->name = $certificate->name;
        $event->description = '';
        $event->courseid = $certificate->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->modulename  = 'simplecertificate';
        $event->instance = $certificate->id;
        calendar_event::create($event);
    }
}

/**
 * Returns certificate text options
 *
 * @param module context
 * @return array
 */
function simplecertificate_get_editor_options(stdclass $context) {
    return array(
            'subdirs'=>0,
            'maxbytes'=>0,
            'maxfiles'=>0,
            'changeformat'=>0,
            'context'=>$context,
            'noclean'=>0,
            'trusttext'=>0);
}

/**
 * Get all the modules
 *
 * @return array
*/ 
function simplecertificate_get_mods (){
    global $COURSE, $CFG, $DB;

    $grademodules = array();
    $items = grade_item::fetch_all(array('courseid'=>$COURSE->id));
    $items = $items ? $items : array();

    foreach($items as $id=>$item) {
    	// Do not include grades for course itens
    	if ($item->itemtype != 'mod') {
    		continue;
    	}
    	$cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance);
    	$grademodules[$cm->id] = $item->get_name();
    }
    asort($grademodules);
    return $grademodules;
}

/**
 * Search through all the modules for grade dates for mod_form.
 *
 * @return array
 */
function simplecertificate_get_date_options() {
	global $CFG;
	require_once(dirname(__FILE__) . '/locallib.php');
	$dateoptions[simplecertificate::CERT_ISSUE_DATE] = get_string('issueddate', 'simplecertificate');
    $dateoptions[simplecertificate::COURSE_COMPLETATION_DATE] = get_string('completiondate', 'simplecertificate');
    return $dateoptions + simplecertificate_get_mods();  
}

/**
 * Search through all the modules for grade data for mod_form.
 *
 * @return array
 */
function simplecertificate_get_grade_options() {
	require_once(dirname(__FILE__) . '/locallib.php');
    $gradeoptions[simplecertificate::NO_GRADE] = get_string('nograde');
    $gradeoptions[simplecertificate::COURSE_GRADE] = get_string('coursegrade', 'simplecertificate');
    return $gradeoptions + simplecertificate_get_mods();
}


/**
 * Print issed file certificate link
 * @param stdClass $issuecert The issued certificate object
 * @return string file link url
 */
function simplecertificate_print_issue_certificate_file(stdClass $issuecert) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

    $output = '';

    $fs = get_file_storage();
    $fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuecert);
    if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
    $fileinfo['filepath'], $fileinfo['filename'])) {
        return $output;
    }

    $link = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                            null, $fileinfo['filepath'], $fileinfo['filename'], true);

    $mimetype = $fileinfo['mimetype'];
    $output = '<img src="' . $OUTPUT->pix_url(file_mimetype_icon($mimetype)) . '" height="16" width="16" alt="' . $mimetype .
            '" />&nbsp;' . '<a href="' . $link->out(false) . '" target="_blank" >' . s($fileinfo['filename']) . '</a>';

    $output .= '<br />';
    $output = '<div class="files">' . $output . '</div>';

    return $output;
}