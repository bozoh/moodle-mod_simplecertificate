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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Simple Certificate module core interaction API
 * 
 * @package mod
 * @subpackage simplecertificate
 * @copyright Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/mod/simplecertificate/lib.php');
require_once ($CFG->dirroot . '/course/lib.php');
require_once ($CFG->dirroot . '/grade/lib.php');
require_once ($CFG->dirroot . '/grade/querylib.php');
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/user/profile/lib.php');

class simplecertificate {
    /**
     *  module constats using in file storage
     * @var CERTIFICATE_COMPONENT_NAME  base componete name
     * @var CERTIFICATE_IMAGE_FILE_AREA image filearea
     * @var CERTIFICATE_ISSUES_FILE_AREA issued certificates filearea
     */
    const CERTIFICATE_COMPONENT_NAME = 'mod_simplecertificate';
    const CERTIFICATE_IMAGE_FILE_AREA = 'image';
    const CERTIFICATE_ISSUES_FILE_AREA = 'issues';
   
    const OUTPUT_OPEN_IN_BROWSER = 0;
    const OUTPUT_FORCE_DOWNLOAD = 1;
    const OUTPUT_SEND_EMAIL = 2;
    
    // Date Options Const
    const CERT_ISSUE_DATE = -1;
    const COURSE_COMPLETATION_DATE = -2;
    
    // Grade Option Const
    const NO_GRADE = 0;
    const COURSE_GRADE = -1;
    
    // View const
    const DEFAULT_VIEW = 0;
    const ISSUED_CERTIFCADES_VIEW = 1;
    const BULK_ISSUE_CERTIFCADES_VIEW = 2;
    
    // pagination
    const SIMPLECERT_MAX_PER_PAGE = 200;
    
    /**
     *
     * @var stdClass the assignment record that contains the global settings for this simplecertificate instance
     */
    private $instance;
    
    /**
     *
     * @var context the context of the course module for this simplecertificate instance
     *      (or just the course if we are creating a new one)
     */
    private $context;
    
    /**
     *
     * @var stdClass the course this simplecertificate instance belongs to
     */
    private $course;
    
    /**
     *
     * @var stdClass the admin config for all simplecertificate instances
     */
    private $adminconfig;
    
    /**
     *
     * @var assign_renderer the custom renderer for this module
     */
    private $output;
    
    /**
     *
     * @var stdClass the course module for this simplecertificate instance
     */
    private $coursemodule;
    
    /**
     *
     * @var array cache for things like the coursemodule name or the scale menu -
     *      only lives for a single request.
     */
    private $cache;
    
    /**
     *
     * @var stdClass the current issued certificate
     */
    private $issuecert;

    /**
     * Constructor for the base simplecertificate class.
     * 
     * @param mixed $coursemodulecontext context|null the course module context
     *        (or the course context if the coursemodule has not been
     *        created yet).
     * @param mixed $coursemodule the current course module if it was already loaded,
     *        otherwise this class will load one from the context as required.
     * @param mixed $course the current course if it was already loaded,
     *        otherwise this class will load one from the context as required.
     */
    public function __construct($coursemodulecontext, $coursemodule = null, $course = null) {
        $this->context = $coursemodulecontext;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
        
        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();
    }

    /**
     * Add this instance to the database.
     * 
     * @param stdClass $formdata The data submitted from the form
     * @param mod_simplecertificate_mod_form $mform the form object to get files
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;
        
        // Add the database record.
        $update = $this->populate_simplecertificate_instance($formdata);
        $update->timecreated = time();
        $update->timemodified = $update->timecreated;
        
        $returnid = $DB->insert_record('simplecertificate', $update, true);
        
        $this->course = $DB->get_record('course', array('id' => $formdata->course), '*', MUST_EXIST);
        
        if (!$this->instance = $DB->get_record('simplecertificate', array('id' => $returnid), '*', MUST_EXIST)) {
            print_erro('certificatenot', 'simplecertificate');
        }
        
        return $returnid;
    }

    /**
     * Update this instance in the database.
     * 
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance(stdClass $formdata) {
        global $DB;
        
        $update = $this->populate_simplecertificate_instance($formdata);
        $update->timemodified = time();
        
        $result = $DB->update_record('simplecertificate', $update);
        
        if (!$DB->execute(
                        'UPDATE {simplecertificate_issues} SET haschange = 1 WHERE timedeleted is NULL AND certificateid = :certid', 
                        array('certid' => $this->get_instance()->id))) {
            print_error('cannotupdatemod', '', '', self::CERTIFICATE_COMPONENT_NAME, 
                        'Error update simplecertificate, markig issues
                     with has change');
        }
        
        if (!$this->instance = $DB->get_record('simplecertificate', array('id' => $update->id), '*', MUST_EXIST)) {
            print_erro('certificatenot', 'simplecertificate');
        }
        
        return $result;
    }

    /**
     * Delete this instance from the database.
     * 
     * @return bool false if an error occurs
     */
    public function delete_instance() {
        global $DB;
        try {
            if ($instance = $this->get_instance()) {
                // Delete issued certificates
                $this->remove_issues($this->get_instance());
                
                // Delete files associated with this certificate.
                $fs = get_file_storage();
                if (!$fs->delete_area_files($this->get_context()->id)) {
                    return false;
                }
                
                // Delete the instance.
                return $DB->delete_records('simplecertificate', array('id' => $this->get_instance()->id));
            }
            return true;
        }
        catch (moodle_exception $e) {
            print_error($e->errorcode, $e->module, $e->link, $e->a, $e->debuginfo);
        }
    }

    /**
     * Remove all issued certificates for specified certificate id
     * 
     * @param mixed stdClass/null $certificateisntance certificate object, certificate id or null
     */
    protected function remove_issues($certificateisntance = null) {
        global $DB;
        try {
            if (empty($certificateisntance)) {
                $certificateisntance = $this->get_instance();
            } 
            
            if ($issues = $DB->get_records_select('simplecertificate_issues', 
                                                'certificateid = :certificateid AND timedeleted is NULL', 
                                                array('certificateid' => $certificateisntance->id))) {
                
                foreach ($issues as $issue) {
                    if (!$this->remove_issue($issue, $certificateisntance)) {
                        // TODO add exception msg
                        throw new moodle_exception('TODO');
                    }
                }
            }
        }
        catch (Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     * Remove an issue certificate
     * 
     * @param stdClass $issue Issue certificate object
     * @return bool true if removed
     */
    protected function remove_issue(stdClass $issue, stdClass $certinstance = null) {
        global $DB;
        
        // Try to move certificate to users private file area
        try {
            // Try to get issue file
            if (!$this->issue_file_exists($issue)) {
                throw new moodle_exception('filenotfound', 'simplecertificate', null, null, 
                                        'issue id:[' . $issue->id . ']');
            }
            $fs = get_file_storage();
            
            //Do not use $this->get_issue_file($issue), it has many functions calls
            $file = $fs->get_file_by_hash($issue->pathnamehash);
            
            // Try get user context
            if (!$userctx = context_user::instance($issue->userid)) {
                throw new moodle_exception('usercontextnotfound', 'simplecertificate', null, null, 'userid [' . $issue->userid . ']');
            }
            
            // Try get coursename
            if (empty($certinstance)) {
                if (!$coursename = $DB->get_field('simplecertificate', 'coursename', array('id' => $issue->certificateid), 
                                                IGNORE_MISSING)) {
                    // Can't take, try get course
                    if (!$course = $this->get_course()) {
                        error_log("Can't find course");
                        // TODO add exception msg
                        throw new moodle_exception('TODO');
                    } else {
                        $coursename = $course->fullname;
                    }
                }
            } else {
                // Has certificate instance
                $coursename = $certinstance->coursename;
            }
            
            // Assembling users filearea fileinfo
            $fileinfo = array(
                    'contextid' => $userctx->id, 
                    'component' => 'user', 
                    'filearea' => 'private', 
                    'itemid' => $issue->certificateid, 
                    'filepath' => '/certificates/' . $coursename . '/', 
                    'filename' => $file->get_filename());
            
            if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], 
                                $fileinfo['filepath'], $fileinfo['filename'])) {
                if ($newfile = $fs->create_file_from_storedfile($fileinfo, $file)) {
                    $issue->pathnamehash = $newfile->get_pathnamehash();
                } else {
                    throw new moodle_exception('cannotsavefile', null, null, null, $file->get_filename());
                }
            }
        } catch (moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
            $issue->pathnamehash = '';
        }
        try {
            $issue->timedeleted = time();
            return $DB->update_record('simplecertificate_issues', $issue);
        } catch (Exception $e) {
            throw $e;
        }
    
    }

    /**
     * Get the settings for the current instance of this certificate
     * 
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($cm = $this->get_course_module()) {
            $params = array('id' => $cm->instance);
            $this->instance = $DB->get_record('simplecertificate', $params, '*', MUST_EXIST);
        }
        if (!$this->instance) {
            throw new coding_exception('Improper use of the simplecertificate class. ' . 'Cannot load the simplecertificate record.');
        }
        
        if (!isset($this->instance->coursename)) {
            $this->instance->coursename = $this->get_course()->fullname;
        }
        return $this->instance;
    }

    /**
     * Get context module.
     * 
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course.
     * 
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;
        
        if ($this->course) {
            return $this->course;
        }
        
        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('course', $params, '*', MUST_EXIST);
        
        return $this->course;
    }

    /**
     * Get the context of the current course.
     * 
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the simplecertificate class. ' . 'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course module.
     * 
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        
        if (!$this->context) {
            return null;
        }
        
        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('simplecertificate', $this->context->instanceid, 0, false, MUST_EXIST);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Set the submitted form data.
     * 
     * @param stdClass $data The form data (instance)
     */
    public function set_instance(stdClass $data) {
        $this->instance = $data;
    }

    /**
     * Set the context.
     * 
     * @param context $context The new context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Set the course data.
     * 
     * @param stdClass $course The course data
     */
    public function set_course(stdClass $course) {
        $this->course = $course;
    }

    /**
     *
     * @param stdClass $formdata The data submitted from the form
     * @param mod_simplecertificate_mod_form $mform The form object to get files
     * @return stdClass The simplecertificate instance object
     */
    private function populate_simplecertificate_instance(stdclass $formdata) {
        global $USER;
        
        //Clear image filearea
        $fs = get_file_storage();
        $fs->delete_area_files($this->get_context()->id, self::CERTIFICATE_COMPONENT_NAME, self::CERTIFICATE_IMAGE_FILE_AREA);
        // Creating a simplecertificate instace object.
        $update = new stdClass();
        
        if (isset($formdata->certificatetext['text'])) {
            $update->certificatetext = $formdata->certificatetext['text'];
            if (!isset($formdata->certificatetextformat)) {
                $update->certificatetextformat = $formdata->certificatetext['format'];
            }
            unset($formdata->certificatetext);
        }
        
        if (isset($formdata->secondpagetext['text'])) {
            $update->secondpagetext = $formdata->secondpagetext['text'];
            if (!isset($formdata->secondpagetextformat)) {
                $update->secondpagetextformat = $formdata->secondpagetext['format'];
            }
            unset($formdata->secondpagetext);
        }
        
        if (isset($formdata->certificateimage)) {
            if (!empty($formdata->certificateimage)) {
                $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
                $formdata->certificateimage = $this->save_upload_file($formdata->certificateimage, $fileinfo);
            }
        } else {
            $formdata->certificateimage = null;
        }
        
        if (isset($formdata->secondimage)) {
            if (!empty($formdata->secondimage)) {
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                $formdata->secondimage = $this->save_upload_file($formdata->secondimage, $fileinfo);
            }
        } else {
            $formdata->secondimage = null;
        }
        
        foreach ($formdata as $name => $value) {
            $update->{$name} = $value;
        }
        
        if (isset($formdata->instance)) {
            $update->id = $formdata->instance;
            unset($update->instance);
        }
        
        if (empty($update->coursename)) {
            $update->coursename = $this->get_course()->fullname;
        }
        
        return $update;
    }

    /**
     * Save upload files in $fileinfo array and return the filename
     * 
     * @param string $form_item_id Upload file form id
     * @param array $fileinfo The file info array, where to store uploaded file
     * @return string filename
     */
    private function save_upload_file($form_item_id, array $fileinfo) {
        // Clear file area
        if (empty($fileinfo['itemid'])) {
            $fileinfo['itemid'] = '';
        }
        
        $fs = get_file_storage();
        $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        file_save_draft_area_files($form_item_id, $fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                $fileinfo['itemid']);
        // Get only files, not directories
        $files = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], '', 
                                    false);
        $file = array_shift($files);
        return $file->get_filename();
    }

    /**
     * Get the first page background image fileinfo
     * 
     * @param mixed $context The module context object or id
     * @return the first page background image fileinfo
     */
    public static function get_certificate_image_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }
        
        $fileinfo = array(
                'contextid' => $contextid,         // ID of context
                'component' => self::CERTIFICATE_COMPONENT_NAME,         // usually = table name
                'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA,         // usually = table name
                'itemid' => 1,         // usually = ID of row in table
                'filepath' => '/'
        );        // any path beginning and ending in /
       
        return $fileinfo;
    }

    /**
     * Get the second page background image fileinfo
     * 
     * @param mixed $context The module context object or id
     * @return the second page background image fileinfo
     */
    public static function get_certificate_secondimage_fileinfo($context) {
        
        $fileinfo = self::get_certificate_image_fileinfo($context);
        $fileinfo['itemid'] = 2;
        return $fileinfo;
    }

    /**
     * Get issued certificate object, if it's not exist, it will be create
     * 
     * @param mixed User obj or id
     * @return stdClass the issue certificate object
     */
    public function get_issue($user = null) {
        global $DB, $USER;
        
        if (empty($user)) {
            $userid = $USER->id;
        } else {
            if (is_object($user)) {
                $userid = $user->id;
            } else {
                $userid = $user;
            }
        }
        
        // Check if certificate has already issued
        // Trying cached first
            
        // The cache issue is from this user ?
        $created = false;
        if (!empty($this->issuecert) && $this->issuecert->userid == $userid) {
            if (empty($this->issuecert->haschange)) {
                    // haschange is marked, if no return from cache
                return $this->issuecert;
            } else {
                // haschange is maked, must update
                $issuecert = $this->issuecert;
            }
            // Not in cache, trying get from database
        } else if (!$issuecert = $DB->get_record('simplecertificate_issues', 
                                                array('userid' => $userid, 'certificateid' => $this->get_instance()->id, 
                                                        'timedeleted' => null))) {
            // Not in cache and not in DB, create new certificate issue record
            // Mark as created
            $created = true;
            $issuecert = new stdClass();
            $issuecert->certificateid = $this->get_instance()->id;
            $issuecert->userid = $userid;
            $issuecert->haschange = 1;
            $formated_coursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formated_certificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuecert->certificatename = format_string($formated_coursename . '-' . $formated_certificatename, true);
            $issuecert->timecreated = time();
            $issuecert->code = $this->get_issue_uuid();
            // Avoiding not null restriction;
            $issuecert->pathnamehash = '';
            
            if (has_capability('mod/simplecertificate:manage', $this->context, $userid)) {
                $issuecert->id = 0;
            } else {
                $issuecert->id = $DB->insert_record('simplecertificate_issues', $issuecert);
                
                // Email to the teachers and anyone else
                if (!empty($this->get_instance()->emailteachers)) {
                    $this->send_alert_email_teachers();
                }
                
                if (!empty($this->get_instance()->emailothers)) {
                    $this->send_alert_email_others();
                }
            }
        }
        
        //If cache or db issued certificate is maked as haschange, must update
        if (!empty($issuecert->haschange) && !$created) { //Check haschange, if so, reissue
            $formated_coursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formated_certificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuecert->certificatename = format_string($formated_coursename . '-' . $formated_certificatename, true);
            $DB->update_record('simplecertificate_issues', $issuecert);
        }
        
        //Caching to avoid unessecery db queries
        $this->issuecert = $issuecert;
        return $issuecert;
    }

    /**
     * Returns a list of previously issued certificates--used for reissue.
     * 
     * @param int $certificateid
     * @return stdClass the attempts else false if none found
     */
    public function get_attempts() {
        global $DB, $USER;
        
        $sql = "SELECT *
                FROM {simplecertificate_issues} i
                WHERE certificateid = :certificateid
                AND userid = :userid AND timedeleted IS NULL";
        
        if ($issues = $DB->get_records_sql($sql, array('certificateid' => $this->get_instance()->id, 'userid' => $USER->id))) {
            return $issues;
        }
        
        return false;
    }

    /**
     * Prints a table of previously issued certificates--used for reissue.
     * 
     * @param stdClass $attempts
     * @return string the attempt table
     */
    public function print_attempts($attempts) {
        global $OUTPUT, $DB;
        
        echo $OUTPUT->heading(get_string('summaryofattempts', 'simplecertificate'));
        
        // Prepare table header
        $table = new html_table();
        $table->class = 'generaltable';
        $table->head = array(get_string('issued', 'simplecertificate'));
        $table->align = array('left');
        $table->attributes = array("style" => "width:20%; margin:auto");
        $gradecolumn = $this->get_instance()->certgrade;
        
        if ($gradecolumn) {
            $table->head[] = get_string('grade');
            $table->align[] = 'center';
            $table->size[] = '';
        }
        // One row for each attempt
        foreach ($attempts as $attempt) {
            $row = array();
            
            // prepare strings for time taken and date completed
            $datecompleted = userdate($attempt->timecreated);
            $row[] = $datecompleted;
            
            if ($gradecolumn) {
                $attemptgrade = $this->get_grade();
                $row[] = $attemptgrade;
            }
            
            $table->data[$attempt->id] = $row;
        }
        
        echo html_writer::table($table);
    }

    /**
     * Returns the grade to display for the certificate.
     * 
     * @param int $userid
     * @return string the grade result
     */
    protected function get_grade($userid = null) {
        global $USER, $DB;
        
        if (empty($userid)) {
            $userid = $USER->id;
        }
        
        //If certgrade = 0 return nothing
        if (empty($this->get_instance()->certgrade)) { //No grade
            return '';
        }
        
        switch ($this->get_instance()->certgrade) {
            case self::COURSE_GRADE: //Course grade
                if  ($course_item = grade_item::fetch_course_item($this->get_course()->id)) {
                    $grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid));
                    $course_item->gradetype = GRADE_TYPE_VALUE;
                    $coursegrade = new stdClass();
                    // String used
                    $coursegrade->points = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_REAL, 
                                                                $decimals = 2);
                    $coursegrade->percentage = grade_format_gradevalue($grade->finalgrade, $course_item, true, 
                                                                    GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
                    $coursegrade->letter = grade_format_gradevalue($grade->finalgrade, $course_item, true, 
                                                                GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);
                }
            break;
            
            default: // Module grade
                     //Get grade from a specific module, stored at certgrade
                if ($modinfo = $this->get_mod_grade($this->get_instance()->certgrade, $userid)) {
                    // String used
                    $coursegrade = new stdClass();
                    $coursegrade->points = $modinfo->points;
                    $coursegrade->percentage = $modinfo->percentage;
                    $coursegrade->letter = $modinfo->letter;
                    break;
                }
        }
        
        return $this->get_formated_grade($coursegrade);
    }

    private function get_formated_grade(stdClass $coursegrade) {
        if (empty($coursegrade)) {
            return '';
        }
        
        switch ($this->get_instance()->gradefmt) {
            case 1:
                return $coursegrade->percentage;
            break;
            
            case 3:
                return $coursegrade->letter;
            break;
            
            default:
                return $coursegrade->points;
            break;
        }
    }

    /**
     * Prepare to print an activity grade.
     * 
     * @param int $moduleid
     * @param int $userid
     * @return stdClass bool the mod object if it exists, false otherwise
     */
    protected function get_mod_grade($moduleid, $userid) {
        global $DB;
        
        $cm = $DB->get_record('course_modules', array('id' => $moduleid));
        $module = $DB->get_record('modules', array('id' => $cm->module));
        
        if ($grade_item = grade_get_grades($this->get_course()->id, 'mod', $module->name, $cm->instance, $userid)) {
            $item = new grade_item();
            $itemproperties = reset($grade_item->items);
            foreach ($itemproperties as $key => $value) {
                $item->$key = $value;
            }
            $modinfo = new stdClass();
            $modinfo->name = utf8_decode($DB->get_field($module->name, 'name', array('id' => $cm->instance)));
            $grade = $item->grades[$userid]->grade;
            $item->gradetype = GRADE_TYPE_VALUE;
            $item->courseid = $this->get_course()->id;
            
            $modinfo->points = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
            $modinfo->percentage = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
            $modinfo->letter = grade_format_gradevalue($grade, $item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);
            
            if ($grade) {
                $modinfo->dategraded = $item->grades[$userid]->dategraded;
            } else {
                $modinfo->dategraded = time();
            }
            return $modinfo;
        }
        
        return false;
    }

    /**
     * Generate a version 1 UUID (time based)
     * you can verify the generated code in:
     * http://www.famkruithof.net/uuid/uuidgen?typeReq=-1
     * 
     * @return string UUID_v1
     */
    protected function get_issue_uuid() {
        global $CFG;
        require_once ($CFG->dirroot . '/mod/simplecertificate/lib/lib.uuid.php');
        $UUID = UUID::mint(UUID::VERSION_1, self::CERTIFICATE_COMPONENT_NAME);
        return $UUID->__toString();
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     * 
     * @return array the teacher array
     */
    protected function get_teachers() {
        global $CFG, $USER, $DB;
        $teachers = array();
        
        if (!empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);
        } else {
            list($coursecontactroles, $trash) = get_roles_with_cap_in_context($this->get_context(), 'mod/simplecertificate:manage');
        }
        foreach ($coursecontactroles as $roleid) {
            $role = $DB->get_record('role', array('id' => $roleid));
            $roleid = (int)$roleid;
            if ($users = get_role_users($roleid, $this->context, true)) {
                foreach ($users as $teacher) {
                    if ($teacher->id == $USER->id) {
                        continue; // do not send self
                    }// do not send self
                    $manager = new stdClass();
                    $manager->user = $teacher;
                    $manager->username = fullname($teacher);
                    $teachers[$teacher->id] = $manager;
                }
            }
        }
        return $teachers;
    }

    /**
     * Alerts teachers by email of received certificates.
     * First checks whether the option to email teachers is set for this certificate.
     */
    protected function send_alert_email_teachers() {
        if (!empty($this->get_instance()->emailteachers)) {
            if ($teachers = $this->get_teachers()) {
                $emailteachers = array();
                foreach ($teachers as $teacher) {
                    $emailteachers[] = $teacher->user->email;
                }
                $this->send_alert_emails($emailteachers);
            }
        }
    }

    /**
     * Alerts others by email of received certificates.
     * First checks whether the option to email others is set for this certificate.
     */
    protected function send_alert_email_others() {
        if (!empty($this->get_instance()->emailothers)) {
            $others = explode(',', $this->get_instance()->emailothers);
            if ($others) {
                $this->send_alert_emails($others);
            }
        }
    }

    /**
     * Send Alerts email of received certificates
     * 
     * @param array $emails emails arrays
     */
    protected function send_alert_emails($emails) {
        global $USER, $CFG, $DB;
        
        if (!empty($emails)) {
            
            $url = new moodle_url($CFG->wwwroot . '/mod/simplecertificate/view.php', 
                                array('id' => $this->coursemodule->id, 'tab' => self::ISSUED_CERTIFCADES_VIEW));
            
            foreach ($emails as $email) {
                $email = trim($email);
                if (validate_email($email)) {
                    $destination = new stdClass();
                    $destination->email = $email;
                    $destination->id = rand(-10, -1);
                    
                    $info = new stdClass();
                    $info->student = fullname($USER);
                    $info->course = format_string($this->get_instance()->coursename, true);
                    $info->certificate = format_string($this->get_instance()->name, true);
                    $info->url = $url->out();
                    $from = $info->student;
                    $postsubject = get_string('awardedsubject', 'simplecertificate', $info);
                    
                    //Getting email body plain text
                    $posttext = get_string('emailteachermail', 'simplecertificate', $info) . "\n";
                    
                    //Getting email body html
                    $posthtml = '<font face="sans-serif">';
                    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'simplecertificate', $info) . '</p>';
                    $posthtml .= '</font>';
                    
                    @email_to_user($destination, $from, $postsubject, $posttext, $posthtml); // If it fails, oh well, too bad.
                }// If it fails, oh well, too bad.
            }
        }
    }

    /**
     * Create PDF object using parameters
     * 
     * @return PDF
     */
    protected function create_pdf_object() {
        
        //Default orientation is Landescape
        $orientation = 'L';
        
        if ($this->get_instance()->height > $this->get_instance()->width) {
            $orientation = 'P';
        }
        
        // Remove commas to avoid a bug in TCPDF where a string containing a commas will result in two strings.
        $keywords = get_string('keywords', 'simplecertificate') . ',' . format_string($this->get_instance()->coursename, true);
        $keywords = str_replace(",", " ", $keywords); // Replace commas with spaces.
        $keywords = str_replace("  ", " ", $keywords); // Replace two spaces with one.

        $pdf = new pdf($orientation, 'mm', array($this->get_instance()->width, $this->get_instance()->height), true, 'UTF-8');
        $pdf->SetTitle($this->get_instance()->name);
        $pdf->SetSubject($this->get_instance()->name . ' - ' . $this->get_instance()->coursename);
        $pdf->SetKeywords($keywords);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setFontSubsetting(true);
        $pdf->SetMargins(0, 0, 0, true);
        
        return $pdf;
    }

    /**
     * Create certificate PDF file
     * 
     * @param stdClass $issuecert The issue certifcate obeject
     * @param PDF $pdf A PDF object, if null will create one
     * @param bool $isbulk Tell if it is a bulk operation or not
     * @return mixed PDF object or error
     */
    protected function create_pdf(stdClass $issuecert, $pdf = null, $isbulk = false) {
        global $DB, $CFG;
        
        //Check if certificate file is already exists, if issued has changes, it will recreated
        if (empty($issuecert->haschange) && $this->issue_file_exists($issuecert) && !$isbulk) {
            return false;
        }
        
        if (empty($pdf)) {
            $pdf = $this->create_pdf_object();
        }
        
        $pdf->AddPage();
        
        //Getting certificare image
        $fs = get_file_storage();
        
        // Get first page image file
        if (!empty($this->get_instance()->certificateimage)) {
            // Prepare file record object
            $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
            $firstpageimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                                $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->certificateimage);
            // Read contents
            if ($firstpageimagefile) {
                $temp_filename = $firstpageimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'first_image_');
                $pdf->Image($temp_filename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                @unlink($temp_filename);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate', $this->get_instance()->certificateimage));
            }
        }
        
        //Writing text
        $pdf->SetXY($this->get_instance()->certificatetextx, $this->get_instance()->certificatetexty);
        $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->get_instance()->certificatetext), 0, 0, 0, 
                            true, 'C');
        
        //Print QR code in first page (if enable)
        if (!empty($this->get_instance()->qrcodefirstpage) && !empty($this->get_instance()->printqrcode)) {
            $this->print_qrcode($pdf, $issuecert->code);
        }
        
        if (!empty($this->get_instance()->enablesecondpage)) {
            $pdf->AddPage();
            if (!empty($this->get_instance()->secondimage)) {
                // Prepare file record object
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                // Get file
                $secondimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
                                                $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->secondimage);
                
                // Read contents
                if (!empty($secondimagefile)) {
                    $temp_filename = $secondimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'second_image_');
                    $pdf->Image($temp_filename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                    @unlink($temp_filename);
                } else {
                    print_error(get_string('filenotfound', 'simplecertificate', $this->get_instance()->secondimage));
                }
            }
            if (!empty($this->get_instance()->secondpagetext)) {
                $pdf->SetXY($this->get_instance()->secondpagex, $this->get_instance()->secondpagey);
                $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->get_instance()->secondpagetext), 0, 
                                    0, 0, true, 'C');
            }
        }
        
        if (!empty($this->get_instance()->printqrcode) && empty($this->get_instance()->qrcodefirstpage)) {
            //Add certificade code using QRcode, in a new page (to print in the back)
            if (empty($this->get_instance()->enablesecondpage)) {
                //If secondpage is disabled, create one
                $pdf->AddPage();
            }
            $this->print_qrcode($pdf, $issuecert->code);
        
            }
        return $pdf;
    }

    /**
     * Put a QR code in cerficate pdf object
     * 
     * @param pdf $pdf The pdf object
     * @param string $code The certificate code
     */
    protected function print_qrcode($pdf, $code) {
        global $CFG;
        $style = array('border' => 2, 'vpadding' => 'auto', 'hpadding' => 'auto', 'fgcolor' => array(0, 0, 0),  //black
                'bgcolor' => array(255, 255, 255),                 //white
                'module_width' => 1,                 // width of a single module in points
                'module_height'=> 1);        // height of a single module in points
        
        $codeurl = new moodle_url("$CFG->wwwroot/mod/simplecertificate/verify.php");
        $codeurl->param('code', $code);
        
        $pdf->write2DBarcode($codeurl->out(false), 'QRCODE,M', $this->get_instance()->codex, $this->get_instance()->codey, 50, 50, 
                            $style, 'N');
        $pdf->SetXY($this->get_instance()->codex, $this->get_instance()->codey + 49);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell(50, 0, $code, 'LRB', 0, 'C', true, '', 2);
    }

    /**
     * Save a certificate pdf file
     * 
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return stored_file if successful, false otherwise
     */
    protected function save_pdf(stdClass $issuecert) {
        global $DB;
        
        // Check if file exist
        //if issue certificate has no change, it's must has a file
        if (empty($issuecert->haschange)) {
            if ($this->issue_file_exists($issuecert)) {
                return $this->get_issue_file($issuecert);
            } else {
                error_log(get_string('filenotfound', 'simplecertificate'));
                print_error(get_string('filenotfound', 'simplecertificate'));
                return false;
            }
        } else {
            //Cache issued cert, to avoid db queries
            $this->issuecert = $issuecert;
            if (!$pdf = $this->create_pdf($this->get_issue($issuecert->userid))) {
                //TODO add can't create certificate file error
                print_error('TODO');
                return false;
            }
            
            //This avoid function calls loops
            $issuecert->haschange = 0;
            
            //Remove old file, if exists
            if ($this->issue_file_exists($issuecert)) {
                $file = $this->get_issue_file($issuecert);
                $file->delete();
            }
            
            // Prepare file record object
            $context = $this->get_context();
            $filename = str_replace(' ', '_', clean_filename($issuecert->certificatename . ' ' . $issuecert->id . '.pdf'));
            $fileinfo = array('contextid' => $context->id, 
                    'component' => self::CERTIFICATE_COMPONENT_NAME, 
                    'filearea' => self::CERTIFICATE_ISSUES_FILE_AREA, 
                    'itemid' => $issuecert->id, 
                    'filepath' => '/', 
                    'mimetype' => 'application/pdf', 
                    'userid' => $issuecert->userid, 
                    'filename' => $filename
            );
            
            $fs = get_file_storage();
            if (!$file = $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'))) {
                print_error('cannotsavefile', 'error', '', $fileinfo['filename']);
                return false;
            }
            
            $issuecert->pathnamehash = $file->get_pathnamehash();
            
            // Verify if user is a manager, if not, update issuedcert
            if (!has_capability('mod/simplecertificate:manage', $this->context, $issuecert->userid)) {
                if (!$DB->update_record('simplecertificate_issues', $issuecert)) {
                    print_error('cannotupdatemod', 'error', null, 'simplecertificate_issue');
                    return false;
                }
            }
             return $file;
        }
    }

    /**
     * Sends the student their issued certificate as an email
     * attachment.
     * 
     * @param $issuecert The issue certificate object
     */
    protected function send_certificade_email(stdClass $issuecert) {
        global $DB, $CFG;
        
        if (!$user = $DB->get_record('user', array('id' => $issuecert->userid))) {
            print_error('nousersfound', 'moodle');
        }
        
        $info = new stdClass();
        $info->username = format_string(fullname($user), true);
        $info->certificate = format_string($issuecert->certificatename, true);
        $info->course = format_string($this->get_instance()->coursename, true);
        
        $subject = get_string('emailstudentsubject', 'simplecertificate', $info);
        $message = get_string('emailstudenttext', 'simplecertificate', $info) . "\n";
        
        // Make the HTML version more XHTML happy  (&amp;)
        $messagehtml = text_to_html($message);
        
        // Get generated certificate file
        if ($file = $this->get_issue_file($issuecert)) { //put in a tmp dir, for e-mail attachament
            $fullfilepath = $this->create_temp_file($file->get_filename());
            $file->copy_content_to($fullfilepath);
            $relativefilepath = str_replace($CFG->dataroot . DIRECTORY_SEPARATOR, "", $fullfilepath);
            
            if (strpos($relativefilepath, DIRECTORY_SEPARATOR, 1) === 0) {
                $relativefilepath = substr($relativefilepath, 1);
            }
            
            if (!empty($this->get_instance()->emailfrom)) {
                $from = core_user::get_support_user();
                $from->email = format_string($this->get_instance()->emailfrom, true);
            } else {
                $from = format_string($this->get_instance()->emailfrom, true);
            }
            
            $ret = email_to_user($user, $from, $subject, $message, $messagehtml, $relativefilepath, $file->get_filename());
            @unlink($fullfilepath);
            
            return $ret;
        } else {
            error_log(get_string('filenotfound', 'simplecertificate'));
            print_error(get_string('filenotfound', 'simplecertificate'));
        }
    }

    /**
     * Return a stores_file object with issued certificate PDF file or false otherwise
     * 
     * @param stdClass $issuecert Issued certificate object
     * @return mixed <stored_file, boolean>
     */
    public function get_issue_file(stdClass $issuecert) {
        if (!empty($issuecert->haschange)) {
            return $this->save_pdf($issuecert);
        }
        
        if (!$this->issue_file_exists($issuecert)) {
            return false;
        }
        
        $fs = get_file_storage();
        return $fs->get_file_by_hash($issuecert->pathnamehash);
    }

    /**
     * Get the time the user has spent in the course
     * 
     * @param int $userid User ID (default= $USER->id)
     * @return int the total time spent in seconds
     */
    public function get_course_time($user = null) {
        global $CFG, $USER;
        
        if (empty($user)) {
            $userid = $USER->id;
        } else {
            if (is_object($user)) {
                $userid = $user->id;
            } else {
                $userid = $user;
            }
        }
        $manager = get_log_manager();
        $selectreaders = $manager->get_readers('\core\log\sql_select_reader');
        $reader = reset($selectreaders);

        //This can take a log time to process, but it's accurate
        // it's can be done by get only first and last log entry creation time, 
        // but it's far more inaccurate,  could have an option to choose.
        set_time_limit(0);
        $totaltime = 0;
        $sql = "action = 'viewed' AND target = 'course' AND courseid = :courseid AND userid = :userid";
        
        if ($logs = $reader->get_events_select($sql, array('courseid' => $this->get_course()->id, 'userid' => $userid), 
            'timecreated ASC', '', '')) {
            foreach ($logs as $log) {
                if (empty($login)) {
                    // For the first time $login is not set so the first log is also the first login
                    $login = $log->timecreated;
                    $lasthit = $log->timecreated;
                }
                $delay = $log->timecreated - $lasthit;
                
                if (!($delay > ($CFG->sessiontimeout))) {
                    // The difference between the last log and the current log is more than
                    // the timeout 
                    //Register session value so that we have found a new session!
                    $totaltime += $delay;
                }
                // Now the actual log became the previous log for the next cycle
                $lasthit = $log->timecreated;
            }
        }
        return $totaltime / 60;
        
    }

    /**
     * Delivery the issue certificate
     * 
     * @param stdClass $issuecert The issued certificate object
     */
    public function output_pdf(stdClass $issuecert) {
        global $OUTPUT;
        
        if ($file = $this->get_issue_file($issuecert)) {
            switch ($this->get_instance()->delivery) {
                case self::OUTPUT_FORCE_DOWNLOAD:
                    send_stored_file($file, 10, 0, true, array('filename' => $file->get_filename(), 'dontdie' => true)); //force download
                break;// force download
                
                case self::OUTPUT_SEND_EMAIL:
                    $this->send_certificade_email($issuecert);
                    echo $OUTPUT->header();
                    echo $OUTPUT->box(get_string('emailsent', 'simplecertificate') . '<br>' . $OUTPUT->close_window_button(), 
                                    'generalbox', 'notice');
                    echo $OUTPUT->footer(); //send email
                break;
                
                case self::OUTPUT_OPEN_IN_BROWSER:
                    send_stored_file($file, 10, 0, false, array('dontdie' => true)); // open in browser
                break;// open in browser
          }
            
            if (has_capability('mod/simplecertificate:manage', $this->context, $issuecert->userid)) {
                $file->delete();
            }
        } else {
            error_log(get_string('filenotfound', 'simplecertificate'));
            print_error(get_string('filenotfound', 'simplecertificate'));
        }
    }

    /**
     * Substitutes the certificate text variables
     * 
     * @param stdClass $issuecert The issue certificate object
     * @param string $certtext The certificate text without substitutions
     * @return string Return certificate text with all substutions
     */
    protected function get_certificate_text($issuecert, $certtext = null) {
        global $OUTPUT, $DB, $CFG;
        
        if (!$user = get_complete_user_data('id', $issuecert->userid)) {
            print_error('nousersfound', 'moodle');
        }
        
        //If no text set get firstpage text
        if (empty($certtext)) {
            $certtext = $this->get_instance()->certificatetext;
        }
        $certtext = format_text($certtext, FORMAT_HTML, array('noclean' => true));
        
        $a = new stdClass();
        $a->username = fullname($user);
        $a->idnumber = $user->idnumber;
        $a->firstname = $user->firstname;
        $a->lastname = $user->lastname;
        $a->email = $user->email;
        $a->icq = $user->icq;
        $a->skype = $user->skype;
        $a->yahoo = $user->yahoo;
        $a->aim = $user->aim;
        $a->msn = $user->msn;
        $a->phone1 = $user->phone1;
        $a->phone2 = $user->phone2;
        $a->institution = $user->institution;
        $a->department = $user->department;
        $a->address = $user->address;
        $a->city = $user->city;
        
        //Add userimage url
        $a->userimage = $OUTPUT->user_picture($user, array('size' => 1, 'popup' => false));
        
        if (!empty($user->country)) {
            $a->country = get_string($user->country, 'countries');
        } else {
            $a->country = '';
        }
        
        //Formatting URL, if needed
        $url = $user->url;
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }
        $a->url = $url;
        
        //Getting user custom profiles fields
        $userprofilefields = $this->get_user_profile_fields($user->id);
        foreach ($userprofilefields as $key => $value) {
            $key = 'profile_' . $key;
            $a->$key = $value;
        }
        
        $a->coursename = format_string($this->get_instance()->coursename, true);
        $a->grade = $this->get_grade($user->id);
        $a->date = $this->get_date($issuecert, $user->id);
        $a->outcome = $this->get_outcome($user->id);
        $a->certificatecode = $issuecert->code;
        
        // this code stay here only beace legacy supporte, coursehours variable was removed
        //see issue 61 https://github.com/bozoh/moodle-mod_simplecertificate/issues/61
        if (isset($this->get_instance()->coursehours)) {
            $a->hours = format_string($this->get_instance()->coursehours . ' ' . get_string('hours', 'simplecertificate'), true);
        } else {
            $a->hours = '';
        }
        
        try {
            if ($course = $this->get_course()) {
                require_once ($CFG->libdir . '/coursecatlib.php');
                $courseinlist = new course_in_list($course);
                if ($courseinlist->has_course_contacts()) {
                    $t = array();
                    foreach ($courseinlist->get_course_contacts() as $userid => $coursecontact) {
                        $t[] = $coursecontact['rolename'] . ': ' . $coursecontact['username'];
                    }
                    $a->teachers = implode("<br>", $t);
                } else {
                    $a->teachers = '';
                }
            } else {
                $a->teachers = '';
            }
        }
        catch (Exception $e) {
            $a->teachers = '';
        }
        
        //Fetch user actitivy restuls
        $a->userresults = $this->get_user_results($issuecert->userid);
        
        //Get User role name in course
        if (!$a->userrolename = get_user_roles_in_course($user->id, $course->id)) {
            $a->userrolename = '';
        }
        
        // Get user enrollment start date
        // see funtion  enrol_get_enrolment_end($courseid, $userid), which get enddate, not start
        $sql = "SELECT ue.timestart
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
              WHERE ue.userid = :userid AND e.status = :enabled AND u.deleted = 0";
        
        $params = array('enabled'=>ENROL_INSTANCE_ENABLED, 'userid'=>$user->id, 'courseid'=>$course->id);
       
        if ($timestart = $DB->get_field_sql($sql, $params)) {
            $a->timestart = userdate($timestart, $this->get_instance()->timestartdatefmt);
        } else {
            $a->timestart = '';
        }
        
        $a = (array)$a;
        $search = array();
        $replace = array();
        foreach ($a as $key => $value) {
            $search[] = '{' . strtoupper($key) . '}';
            //Some variables can't use format_string
            if (strtoupper($key) == 'USERIMAGE' || strtoupper($key) == 'URL') {
                $replace[] = $value;
            } else {
                $replace[] = format_string((string)$value, true);
            }
        }
        
        if ($search) {
            $certtext = str_replace($search, $replace, $certtext);
        }
        
        //Clear not setted custom profile fiedls {PROFILE_xxxx}
        return preg_replace('[\{PROFILE_(.*)\}]', "", $certtext);
    
        }

    /**
     * Returns the date to display for the certificate.
     * 
     * @param stdClass $issuecert The issue certificate object
     * @param int $userid
     * @return string the date
     */
    protected function get_date(stdClass $issuecert) {
        global $DB;
        
        // Get date format
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }
        
        //Set to current time
        $date = time();
        
        // Set certificate issued date
        if ($this->get_instance()->certdate == self::CERT_ISSUE_DATE) {
            $date = $issuecert->timecreated;
        }
        
        // Get the enrolment end date
        if ($this->get_instance()->certdate == self::COURSE_COMPLETATION_DATE) {
            $sql = "SELECT MAX(c.timecompleted) as timecompleted FROM {course_completions} c
                 WHERE c.userid = :userid AND c.course = :courseid";
            
            if ($timecompleted = $DB->get_record_sql($sql, 
                                                    array('userid' => $issuecert->userid, 'courseid' => $this->get_course()->id))) {
                if (!empty($timecompleted->timecompleted)) {
                    $date = $timecompleted->timecompleted;
                }
            }
            // Get the module grade date
        } else if ($this->get_instance()->certdate > 0) {
            if ($modinfo = $this->get_mod_grade($this->get_instance()->certdate, $issuecert->userid)) {
                $date = $modinfo->dategraded;
            }
        }
        
        return userdate($date, $format);
    }

    protected function get_user_results($userid = null) {
        global $USER;
        
        if (empty($userid)) {
            $userid = $USER->id;
        }
        
        $items = grade_item::fetch_all(array('courseid' => $this->course->id));
        if (empty($items)) {
            return '';
        }
        
        $retval = '';
        foreach ($items as $id => $item) {
            // Do not include grades for course itens
           if ($item->itemtype != 'mod') {
                continue;
            }
            $cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance);
            $usergrade = $this->get_formated_grade($this->get_mod_grade($cm->id, $userid));
            $retval = $item->itemname . ": $usergrade<br>" . $retval;
        }
        return $retval;
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     * 
     * @return array
     */
    protected function get_outcomes() {
        global $COURSE, $DB;
        
        // get all outcomes in course
        $grade_seq = new grade_tree($COURSE->id, false, true, '', false);
        if ($grade_items = $grade_seq->items) {
            // list of item for menu
            $printoutcome = array();
            foreach ($grade_items as $grade_item) {
                if (!empty($grade_item->outcomeid)) {
                    $itemmodule = $grade_item->itemmodule;
                    $printoutcome[$grade_item->id] = $itemmodule . ': ' . $grade_item->get_name();
                }
            }
        }
        if (!empty($printoutcome)) {
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
     * Returns the outcome to display on the certificate
     * 
     * @return string the outcome
     */
    protected function get_outcome($userid) {
        global $USER, $DB;
        
        if (empty($userid)) {
            $userid = $USER->id;
        }
        
        if ($this->get_instance()->outcome > 0) {
            if ($grade_item = new grade_item(array('id' => $this->get_instance()->outcome))) {
                $outcomeinfo = new stdClass();
                $outcomeinfo->name = $grade_item->get_name();
                $outcome = new grade_grade(array('itemid' => $grade_item->id, 'userid' => $userid));
                $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $grade_item, true, GRADE_DISPLAY_TYPE_REAL);
                return $outcomeinfo->name . ': ' . $outcomeinfo->grade;
            }
        }
        
        return '';
    }

    protected function create_temp_file($file) {
        global $CFG;
        
        $path = make_temp_directory(self::CERTIFICATE_COMPONENT_NAME);
        return tempnam($path, $file);
    }

    protected function get_user_profile_fields($userid) {
        global $CFG, $DB;
        
        $usercustomfields = new stdClass();
        if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
            foreach ($categories as $category) {
                if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                    foreach ($fields as $field) {
                        require_once ($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                        $newfield = 'profile_field_' . $field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        if ($formfield->is_visible() and !$formfield->is_empty()) {
                            if ($field->datatype == 'checkbox') {
                                $usercustomfields->{$field->shortname} = ($formfield->data == 1 ? get_string('yes') : get_string(
                                                                                                                                'no'));
                            } else {
                                $usercustomfields->{$field->shortname} = $formfield->display_data();
                            }
                        } else {
                            $usercustomfields->{$field->shortname} = '';
                        }
                    }
                }
            }
        }
        return $usercustomfields;
    }

    /**
     * Verify if user meet issue conditions
     * 
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    protected function can_issue($user = null, $chkcompletation = true) {
        global $DB, $USER, $CFG;
        
        if (empty($user)) {
            $user = $USER;
        }
        
        if (has_capability('mod/simplecertificate:manage', $this->context, $user)) {
            return get_string('cantissue', 'simplecertificate');
        }
        
        if ($chkcompletation) {
            $completion = new completion_info($this->course);
            if ($completion->is_enabled($this->coursemodule) && $this->get_instance()->requiredtime) {
                if ($this->get_course_time($user) < $this->get_instance()->requiredtime) {
                    $a = new stdClass();
                    $a->requiredtime = $this->get_instance()->requiredtime;
                    return get_string('requiredtimenotmet', 'simplecertificate', $a);
                }
                // Mark as complete
                $completion->update_state($this->coursemodule, COMPLETION_COMPLETE, $user->id);
            }
            
            if ($CFG->enableavailability) {
                $modinfo = get_fast_modinfo($this->get_course());
                $cm = $modinfo->get_cm($this->get_course_module()->id);
                if (!$cm->uservisible) {
                    if ($cm->availableinfo) {
                        return $cm->availableinfo;
                    } else {
                        return get_string('cantissue', 'simplecertificate');
                    }
                }
                return null;
            }
        }
    }

    /**
     * Verify if cetificate file exists
     * 
     * @param stdClass $issuecert Issued certificate object
     * @return true if exist
     */
    protected function issue_file_exists(stdClass $issuecert) {
        $fs = get_file_storage();
        
        // Check for file first
        return $fs->file_exists_by_hash($issuecert->pathnamehash);
    }
    
    // View methods
    protected function show_tabs(moodle_url $url) {
        global $OUTPUT, $CFG;
        
        $tabs[] = new tabobject(self::DEFAULT_VIEW, $url->out(false, array('tab' => self::DEFAULT_VIEW)), 
                                get_string('standardview', 'simplecertificate'));
        
        $tabs[] = new tabobject(self::ISSUED_CERTIFCADES_VIEW, $url->out(false, array('tab' => self::ISSUED_CERTIFCADES_VIEW)), 
                                get_string('issuedview', 'simplecertificate'));
        
        $tabs[] = new tabobject(self::BULK_ISSUE_CERTIFCADES_VIEW, 
                                $url->out(false, array('tab' => self::BULK_ISSUE_CERTIFCADES_VIEW)), 
                                get_string('bulkview', 'simplecertificate'));
        
        if (!$url->get_param('tab')) {
            $tab = self::DEFAULT_VIEW;
        } else {
            $tab = $url->get_param('tab');
        }
        
        echo $OUTPUT->tabtree($tabs, $tab);
    
        }
    
    // Default view
    public function view_default(moodle_url $url, $canmanage) {
        global $CFG, $OUTPUT, $USER;
        
        if (!$url->get_param('action')) {
            
            echo $OUTPUT->header();
            
            if ($canmanage) {
                $this->show_tabs($url);
            }
            
            // Check if the user can view the certificate
            if (!$canmanage && $msg = $this->can_issue($USER)) {
                notice($msg, $CFG->wwwroot . '/course/view.php?id=' . $this->get_course()->id, $this->get_course());
                die();
            }
            
            if (!empty($this->get_instance()->intro)) {
                echo $OUTPUT->box(format_module_intro('simplecertificate', $this->get_instance(), $this->coursemodule->id), 
                                'generalbox', 'intro');
            }
            
            if ($attempts = $this->get_attempts()) {
                echo $this->print_attempts($attempts);
            }
            
            if (!$canmanage) {
               $this->add_to_log('view');
            }
            
            if ($this->get_instance()->delivery != 3 || $canmanage) {
                // Create new certificate record, or return existing record
                $certrecord = $this->get_issue();
                switch ($this->get_instance()->delivery) {
                    case self::OUTPUT_FORCE_DOWNLOAD:
                        $str = get_string('opendownload', 'simplecertificate');
                    break;
                    
                    case self::OUTPUT_SEND_EMAIL:
                        $str = get_string('openemail', 'simplecertificate');
                    break;
                    
                    default:
                        $str = get_string('openwindow', 'simplecertificate');
                    break;
                }
                
                echo html_writer::tag('p', $str, array('style' => 'text-align:center'));
                $linkname = get_string('getcertificate', 'simplecertificate');
                
                $link = new moodle_url('/mod/simplecertificate/view.php', array('id' => $this->coursemodule->id, 'action' => 'get'));
                $button = new single_button($link, $linkname);
                $button->add_action(
                                    new popup_action('click', $link, 'view' . $this->coursemodule->id, 
                                                    array('height' => 600, 'width' => 800)));
                
                echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
            }
            echo $OUTPUT->footer();
        } else { // Output to pdf
             if ($this->get_instance()->delivery != 3 || $canmanage) {
                $this->output_pdf($this->get_issue($USER));
            }
        }
    }

    protected function get_issued_certificate_users($sort = 'username', $groupmode = 0) {
        global $CFG, $DB;
        
        if ($sort == 'username') {
            $sort = $DB->sql_fullname() . ' ASC';
        } else if ($sort == 'issuedate') {
            $sort = 'ci.timecreated ASC';
        } else {
            $sort = '';
        }
        
        // get all users that can manage this certificate to exclude them from the report.
        $certmanagers = get_users_by_capability($this->context, 'mod/simplecertificate:manage', 'u.id');
        
        $issedusers = $DB->get_records_sql(
                                        "SELECT u.*, ci.code, ci.timecreated 
            FROM {user} u INNER JOIN {simplecertificate_issues} ci ON u.id = ci.userid 
            WHERE u.deleted = 0 
            AND ci.certificateid = :certificateid 
            AND timedeleted IS NULL 
            ORDER BY {$sort}", 
                                        array('certificateid' => $this->get_instance()->id));
        
        // now exclude all the certmanagers.
        foreach ($issedusers as $id => $user) {
            if (!empty($certmanagers[$id])) { //exclude certmanagers.
                unset ($issedusers[$id]);
            }
        }
        
        // if groupmembersonly used, remove users who are not in any group
        if (!empty($issedusers) and !empty($CFG->enablegroupings) and $this->coursemodule->groupmembersonly) {
            if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
                $issedusers = array_intersect($issedusers, array_keys($groupingusers));
            }
        }
        
        if ($groupmode) {
            $currentgroup = groups_get_activity_group($this->coursemodule);
            if ($currentgroup) {
                $groupusers = groups_get_members($currentgroup, 'u.*');
                if (empty($groupusers)) {
                    return array();
                }
                foreach ($issedusers as $id => $unused) {
                    if (empty($groupusers[$id])) {
                        // remove this user as it isn't in the group!
                        unset ($issedusers[$id]);
                    }
                }
            }
        }
        return $issedusers;
    }
    
    // Issued certificates view
    public function view_issued_certificates(moodle_url $url) {
        global $OUTPUT, $DB, $CFG;
        
        // Declare some variables
        $strcertificates = get_string('modulenameplural', 'simplecertificate');
        $strcertificate = get_string('modulename', 'simplecertificate');
        $strto = html_writer::link($url->out(false, array('orderby' => 'username')), get_string('awardedto', 'simplecertificate'));
        $strdate = html_writer::link($url->out(false, array('orderby' => 'issuedate')), 
                                    get_string('receiveddate', 'simplecertificate'));
        $strgrade = get_string('grade', 'simplecertificate');
        $strcode = get_string('code', 'simplecertificate');
        $strreport = get_string('report', 'simplecertificate');
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        $page = $url->get_param('page');
        $perpage = $url->get_param('perpage');
        $orderby = $url->get_param('orderby');
        $usercount = 0;
        
        $users = $this->get_issued_certificate_users($orderby, $groupmode);
        if ($users) {
            $usercount = count($users);
            $users = array_slice($users, intval($page * $perpage), $perpage);
        }
        
        if (!$url->get_param('action')) {
            echo $OUTPUT->header();
            $this->show_tabs($url);
            
            if ($groupmode) {
                groups_get_activity_group($this->coursemodule, true);
            }
            
            groups_print_activity_menu($this->coursemodule, $url);
            
            if (!$users) {
                notify(get_string('nocertificatesissued', 'simplecertificate'));
                echo $OUTPUT->footer();
                exit();
            }
            
            // Create the table for the users
            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";
            $table->head = array($strto, $strdate, $strgrade, $strcode);
            $table->align = array("left", "left", "center", "center");
            foreach ($users as $user) {
                $name = $OUTPUT->user_picture($user) . fullname($user);
                $date = userdate($user->timecreated) . simplecertificate_print_issue_certificate_file($this->get_issue($user));
                $code = $user->code;
                $table->data[] = array($name, $date, $this->get_grade($user->id), $code);
            }
            
            // Create table to store buttons
            $tablebutton = new html_table();
            $tablebutton->attributes['class'] = 'downloadreport';
            
            $btndownloadods = $OUTPUT->single_button($url->out_as_local_url(false, array('action' => 'download', 'type' => 'ods')), 
                                                    get_string("downloadods"));
            $btndownloadxls = $OUTPUT->single_button($url->out_as_local_url(false, array('action' => 'download', 'type' => 'xls')), 
                                                    get_string("downloadexcel"));
            $btndownloadtxt = $OUTPUT->single_button($url->out_as_local_url(false, array('action' => 'download', 'type' => 'txt')), 
                                                    get_string("downloadtext"));
            $tablebutton->data[] = array($btndownloadods, $btndownloadxls, $btndownloadtxt);
            
            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo '<br />';
            echo html_writer::table($table);
            echo html_writer::tag('div', html_writer::table($tablebutton), array('style' => 'margin:auto; width:50%'));
        
            } else if ($url->get_param('action') == 'download') {
            $page = $perpage = 0;
            $type = $url->get_param('type');
            // Calculate file name
            $filename = clean_filename(
                                    $this->get_instance()->coursename . '-' .
                                     strip_tags(format_string($this->get_instance()->name, true)) . '.' .
                                     strip_tags(format_string($type, true)));
            
            switch ($type) {
                case 'ods':
                    require_once ("$CFG->libdir/odslib.class.php");
                    
                    // Creating a workbook
                    $workbook = new MoodleODSWorkbook("-");
                    // Send HTTP headers
                    $workbook->send(format_text($filename, true));
                    // Creating the first worksheet
                    $myxls = $workbook->add_worksheet($strreport);
                    
                    // Print names of all the fields
                    $myxls->write_string(0, 0, get_string("fullname"));
                    $myxls->write_string(0, 1, get_string("idnumber"));
                    $myxls->write_string(0, 2, get_string("group"));
                    $myxls->write_string(0, 3, $strdate);
                    $myxls->write_string(0, 4, $strgrade);
                    $myxls->write_string(0, 5, $strcode);
                    
                    // Generate the data for the body of the spreadsheet
                    $i = 0;
                    $row = 1;
                    if ($users) {
                        foreach ($users as $user) {
                            $myxls->write_string($row, 0, fullname($user));
                            $studentid = (!empty($user->idnumber)) ? $user->idnumber : " ";
                            $myxls->write_string($row, 1, $studentid);
                            $ug2 = '';
                            if ($usergrps = groups_get_all_groups($this->get_course()->id, $user->id)) {
                                foreach ($usergrps as $ug) {
                                    $ug2 = $ug2 . $ug->name;
                                }
                            }
                            $myxls->write_string($row, 2, $ug2);
                            $myxls->write_string($row, 3, userdate($user->timecreated));
                            $myxls->write_string($row, 4, $this->get_grade($user->id));
                            $myxls->write_string($row, 5, $user->code);
                            $row++;
                        }
                        $pos = 5;
                    }
                    // Close the workbook
                    $workbook->close();
                break;
                
                case 'xls':
                    require_once ("$CFG->libdir/excellib.class.php");
                    
                    // Creating a workbook
                    $workbook = new MoodleExcelWorkbook("-");
                    // Send HTTP headers
                    $workbook->send(format_text($filename, true));
                    // Creating the first worksheet
                    $myxls = $workbook->add_worksheet($strreport);
                    
                    // Print names of all the fields
                    $myxls->write_string(0, 0, get_string("fullname"));
                    $myxls->write_string(0, 1, get_string("idnumber"));
                    $myxls->write_string(0, 2, get_string("group"));
                    $myxls->write_string(0, 3, $strdate);
                    $myxls->write_string(0, 4, $strgrade);
                    $myxls->write_string(0, 5, $strcode);
                    
                    // Generate the data for the body of the spreadsheet
                    $i = 0;
                    $row = 1;
                    if ($users) {
                        foreach ($users as $user) {
                            $myxls->write_string($row, 0, fullname($user));
                            $studentid = (!empty($user->idnumber)) ? $user->idnumber : " ";
                            $myxls->write_string($row, 1, $studentid);
                            $ug2 = '';
                            if ($usergrps = groups_get_all_groups($this->get_course()->id, $user->id)) {
                                foreach ($usergrps as $ug) {
                                    $ug2 = $ug2 . $ug->name;
                                }
                            }
                            $myxls->write_string($row, 2, $ug2);
                            $myxls->write_string($row, 3, userdate($user->timecreated));
                            $myxls->write_string($row, 4, $this->get_grade($user->id));
                            $myxls->write_string($row, 5, $user->code);
                            $row++;
                        }
                        $pos = 5;
                    }
                    // Close the workbook
                    $workbook->close();
                break;
                
                case 'txt':
                    
                    header("Content-Type: application/download\n");
                    header("Content-Disposition: attachment; filename=\"" . format_text($filename, true) . "\"");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
                    header("Pragma: public");
                    
                    // Print names of all the fields
                    echo get_string("fullname") . "\t" . get_string("idnumber") . "\t";
                    echo get_string("group") . "\t";
                    echo $strdate . "\t";
                    echo $strgrade . "\t";
                    echo $strcode . "\n";
                    
                    // Generate the data for the body of the spreadsheet
                    $i = 0;
                    $row = 1;
                    if ($users)
                        foreach ($users as $user) {
                            echo fullname($user);
                            $studentid = " ";
                            if (!empty($user->idnumber)) {
                                $studentid = $user->idnumber;
                            }
                            echo "\t" . $studentid . "\t";
                            $ug2 = '';
                            if ($usergrps = groups_get_all_groups($this->get_course()->id, $user->id)) {
                                foreach ($usergrps as $ug) {
                                    $ug2 = $ug2 . $ug->name;
                                }
                            }
                            echo $ug2 . "\t";
                            echo userdate($user->timecreated) . "\t";
                            echo $this->get_grade($user->id) . "\t";
                            echo $user->code . "\n";
                            $row++;
                        }
                break;
            }
            exit();
        }
        echo $OUTPUT->footer();
    }

    public function view_bulk_certificates(moodle_url $url, array $selectedusers = null) {
        global $OUTPUT, $CFG, $DB;
        
        $course_context = context_course::instance($this->get_course()->id);
        
        $page = $url->get_param('page');
        $perpage = $url->get_param('perpage');
        $issuelist = $url->get_param('issuelist');
        $action = $url->get_param('action');
        $groupid = 0;
        $groupmode = groups_get_activity_groupmode($this->coursemodule);
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->coursemodule, true);
        }
        
        $page_start = intval($page * $perpage);
        $usercount = 0;
        if (!$selectedusers) {
            $users = get_enrolled_users($course_context, '', $groupid);
            $usercount = count($users);
            $users = array_slice($users, $page_start, $perpage);
        } else {
            list($sqluserids, $params) = $DB->get_in_or_equal($selectedusers);
            $sql = "SELECT * FROM {user} WHERE id $sqluserids";
            //Adding sort
            $sort = '';
            $override = new stdClass();
            $override->firstname = 'firstname';
            $override->lastname = 'lastname';
            $fullnamelanguage = get_string('fullnamedisplay', '', $override);
            if (($CFG->fullnamedisplay == 'firstname lastname') or ($CFG->fullnamedisplay == 'firstname') or
             ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname')) {
                $sort = " ORDER BY firstname, lastname";
            } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
                $sort = " ORDER BY lastname, firstname";
            }
            $users = $DB->get_records_sql($sql . $sort, $params);
        }
        
        if (!$action) {
            echo $OUTPUT->header();
            $this->show_tabs($url);
            
            groups_print_activity_menu($this->coursemodule, $url);
            
            $selectoptions = array('completed' => get_string('completedusers', 'simplecertificate'), 
                    'allusers' => get_string('allusers', 'simplecertificate'));
            $select = new single_select($url, 'issuelist', $selectoptions, $issuelist);
            $select->label = get_string('showusers', 'simplecertificate');
            echo $OUTPUT->render($select);
            echo '<br>';
            echo '<form id="bulkissue" name="bulkissue" method="post" action="view.php">';
            
            echo html_writer::label(get_string('bulkaction', 'simplecertificate'), 'menutype', true);
            echo '&nbsp;';
            $selectoptions = array('pdf' => get_string('onepdf', 'simplecertificate'), 
                    'zip' => get_string('multipdf', 'simplecertificate'), 'email' => get_string('sendtoemail', 'simplecertificate'));
            echo html_writer::select($selectoptions, 'type', 'pdf');
            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";
            
            //strgrade
            $table->head = array(' ', get_string('fullname'), get_string('grade'));
            $table->align = array("left", "left", "center");
            $table->size = array('1%', '89%', '10%');
            foreach ($users as $user) {
                $canissue = $this->can_issue($user, $issuelist != 'allusers');
                if (empty($canissue)) {
                    $chkbox = html_writer::checkbox('selectedusers[]', $user->id, false);
                    $name = $OUTPUT->user_picture($user) . fullname($user);
                    $table->data[] = array($chkbox, $name, $this->get_grade($user->id));
                }
            }
            
            $downloadbutton = $OUTPUT->single_button($url->out_as_local_url(false, array('action' => 'download')), 
                                                    get_string('bulkbuttonlabel', 'simplecertificate'));
            
            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo '<br />';
            echo html_writer::table($table);
            echo html_writer::tag('div', $downloadbutton, array('style' => 'text-align: center'));
            echo '</form>';
        
            } else if ($action == 'download') {
            $type = $url->get_param('type');
            
            // Calculate file name
            $filename = str_replace(' ', '_', 
                                    clean_filename(
                                                $this->get_instance()->coursename . ' ' .
                                                 get_string('modulenameplural', 'simplecertificate') . ' ' .
                                                 strip_tags(format_string($this->get_instance()->name, true)) . '.' .
                                                 strip_tags(format_string($type, true))));
            
            switch ($type) {
                //One pdf with all certificates
                case 'pdf':
                    $pdf = $this->create_pdf_object();
                    
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            //To one pdf file
                            $issuecert = $this->get_issue($user);
                            $this->create_pdf($issuecert, $pdf, true);
                            
                            //Save certificate PDF
                            if (!$this->issue_file_exists($issuecert)) {
                                //To force file creation
                                $issuecert->haschage = true;
                                $this->get_issue_file($issuecert);
                            }
                        }
                    }
                    $pdf->Output($filename, 'D');
                
                break;
                
                //One zip with all certificates in separated files
                case 'zip':
                    $filesforzipping = array();
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuecert = $this->get_issue($user);
                            if ($file = $this->get_issue_file($issuecert)) {
                                $fileforzipname = $file->get_filename();
                                $filesforzipping[$fileforzipname] = $file;
                            } else {
                                error_log(get_string('filenotfound', 'simplecertificate'));
                                print_error(get_string('filenotfound', 'simplecertificate'));
                            }
                        }
                    }
                    
                    $tempzip = $this->create_temp_file('issuedcertificate_');
                    
                    //zipping files
                    $zipper = new zip_packer();
                    if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
                        //send file and delete after sending.
                        send_temp_file($tempzip, $filename);
                    }
                break;
                
                case 'email':
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuecert = $this->get_issue($user);
                            if ($this->get_issue_file($issuecert)) {
                                $this->send_certificade_email($issuecert);
                            } else {
                                error_log(get_string('filenotfound', 'simplecertificate'));
                                print_error('filenotfound', 'simplecertificate');
                            }
                        }
                    }
                    $url->remove_params('action', 'type');
                    redirect($url, get_string('emailsent', 'simplecertificate'), 5);
                break;
            }
            exit();
        }
        echo $OUTPUT->footer();
    }

    /**
     * Util function to loggin
     * 
     * @param string $action Log action
     */
    private function add_to_log($action) {
        switch ($action) {
            default:
                // Default is view action
                
                $event = \mod_simplecertificate\event\course_module_viewed::create(
                       array(
                            'objectid' => $this->get_course_module()->instance, 
                            'context' => $this->get_context(),
                            'other' => array('certificatecode' => $this->get_issue()->code)));
                $event->add_record_snapshot('course', $this->get_course());
            
            break;
        };
        
        if (!empty($event)) {
            $event->trigger();
        }
    } 
}
