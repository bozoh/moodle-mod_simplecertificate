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
 * Simple Certificate module core interaction API
 *
 * @package mod
 * @subpackage simplecertificate
 * @copyright Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplecertificate/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');


use core_availability\info;
use core_availability\info_module;
use core\message\inbound\private_files_handler;


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

    // Date Options Const.
    const CERT_ISSUE_DATE = -1;
    const COURSE_COMPLETATION_DATE = -2;
    const COURSE_START_DATE = -3;

    // Grade Option Const.
    const NO_GRADE = 0;
    const COURSE_GRADE = -1;

    // View const.
    const DEFAULT_VIEW = 0;
    const ISSUED_CERTIFCADES_VIEW = 1;
    const BULK_ISSUE_CERTIFCADES_VIEW = 2;

    // Pagination.
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
            print_error('certificatenot', 'simplecertificate');
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
            print_error('certificatenot', 'simplecertificate');
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
            if ($this->get_instance()) {
                // Delete issued certificates.
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
        } catch (moodle_exception $e) {
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
                    if (!$this->remove_issue($issue)) {
                        // TODO add exception msg.
                        throw new moodle_exception('TODO');
                    }
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove an issue certificate
     *
     * @param stdClass $issue Issue certificate object
     * @param boolean $movecertfile Move the certificate file to usuer private folder (defaul true)
     * @return bool true if removed
     */
    protected function remove_issue(stdClass $issue,  $movecertfile = true) {
        global $DB;

        // Try to move certificate to users private file area.
        try {
            // Try to get issue file.
            if (!$this->issue_file_exists($issue)) {
                throw new moodle_exception('filenotfound', 'simplecertificate', null, null, 'issue id:[' . $issue->id . ']');
            }
            $fs = get_file_storage();

            // Do not use $this->get_issue_file($issue), it has many functions calls.
            $file = $fs->get_file_by_hash($issue->pathnamehash);

            // Try get user context.
            if (!$userctx = context_user::instance($issue->userid)) {
                throw new moodle_exception('usercontextnotfound', 'simplecertificate',
                                null, null, 'userid [' . $issue->userid . ']');
            }

            // Check if it's to move certificate file or not.
            if ($movecertfile) {
                $coursename = $issue->coursename;

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
            } else {
                $file->delete();
            }
        } catch (moodle_exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
            $issue->pathnamehash = '';
        }
        try {
            if ($movecertfile) {
                $issue->timedeleted = time();
                return $DB->update_record('simplecertificate_issues', $issue);
            } else {
                return $DB->delete_records('simplecertificate_issues', array('id' => $issue->id));
            }
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

        if (!isset($this->instance)) {
            if ($cm = $this->get_course_module()) {
                $params = array('id' => $cm->instance);
                $this->instance = $DB->get_record('simplecertificate', $params, '*', MUST_EXIST);
            }
            if (!$this->instance) {
                throw new coding_exception('Improper use of the simplecertificate class. ' .
                                'Cannot load the simplecertificate record.');
            }
        }
        if (empty($this->instance->coursename)) {
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

        if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
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

        // Clear image filearea.
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

        return $update;
    }

    /**
     * Save upload files in $fileinfo array and return the filename
     *
     * @param string $formitemid Upload file form id
     * @param array $fileinfo The file info array, where to store uploaded file
     * @return string filename
     */
    private function save_upload_file($formitemid, array $fileinfo) {
        // Clear file area.
        if (empty($fileinfo['itemid'])) {
            $fileinfo['itemid'] = '';
        }

        $fs = get_file_storage();
        $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        file_save_draft_area_files($formitemid, $fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                $fileinfo['itemid']);
        // Get only files, not directories.
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

        return array('contextid' => $contextid, // ID of context
                          'component' => self::CERTIFICATE_COMPONENT_NAME, // Usually = table name.
                          'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA, // Usually = table name.
                          'itemid' => 1, // Usually = ID of row in table.
                          'filepath' => '/'); // Any path beginning and ending in /.
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
     * Get the temporary filearea, used to store user
     * profile photos to make the certiticate
     *
     * @param int/object $context The module context
     * @return the temporary fileinfo
     */
    public static function get_certificate_tmp_fileinfo($context) {

        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        return array('contextid' => $contextid,
                            'component' => self::CERTIFICATE_COMPONENT_NAME,
                            'filearea' => 'tmp',
                            'itemid' => 0,
                            'filepath' => '/');
    }

    /**
     * Get issued certificate object, if it's not exist, it will be create
     *
     * @param mixed User obj or id
     * @param boolean Issue teh user certificate if it's not exists (default = true)
     * @return stdClass the issue certificate object
     */
    public function get_issue($user = null, $issueifempty = true) {
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

        // Check if certificate has already issued.
        // Trying cached first.

        // The cache issue is from this user ?
        $created = false;
        if (!empty($this->issuecert) && $this->issuecert->userid == $userid) {
            if (empty($this->issuecert->haschange)) {
                // ...haschange is marked, if no return from cache.
                return $this->issuecert;
            } else {
                // ...haschange is maked, must update.
                $issuedcert = $this->issuecert;
            }
            // Not in cache, trying get from database.
        } else if (!$issuedcert = $DB->get_record('simplecertificate_issues',
                        array('userid' => $userid, 'certificateid' => $this->get_instance()->id, 'timedeleted' => null))) {
            // Not in cache and not in DB, create new certificate issue record.

            if (!$issueifempty) {
                // Not create a new one, only check if exists.
                return null;
            }

            // Mark as created.
            $created = true;
            $issuedcert = new stdClass();
            $issuedcert->certificateid = $this->get_instance()->id;
            $issuedcert->coursename = format_string($this->get_instance()->coursename, true);
            $issuedcert->userid = $userid;
            $issuedcert->haschange = 1;
            $formatedcoursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formatedcertificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuedcert->certificatename = format_string($formatedcoursename . '-' . $formatedcertificatename, true);
            $issuedcert->timecreated = time();
            $issuedcert->code = $this->get_issue_uuid();
            // Avoiding not null restriction.
            $issuedcert->pathnamehash = '';

            if (has_capability('mod/simplecertificate:manage', $this->context, $userid)) {
                $issuedcert->id = 0;
            } else {
                $issuedcert->id = $DB->insert_record('simplecertificate_issues', $issuedcert);

                // Email to the teachers and anyone else.
                if (!empty($this->get_instance()->emailteachers)) {
                    $this->send_alert_email_teachers();
                }

                if (!empty($this->get_instance()->emailothers)) {
                    $this->send_alert_email_others();
                }
            }
        }

        // If cache or db issued certificate is maked as haschange, must update.
        if (!empty($issuedcert->haschange) && !$created) { // Check haschange, if so, reissue.
            $formatedcoursename = str_replace('-', '_', $this->get_instance()->coursename);
            $formatedcertificatename = str_replace('-', '_', $this->get_instance()->name);
            $issuedcert->certificatename = format_string($formatedcoursename . '-' . $formatedcertificatename, true);
            $DB->update_record('simplecertificate_issues', $issuedcert);
        }

        // Caching to avoid unessecery db queries.
        $this->issuecert = $issuedcert;
        return $issuedcert;
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
        global $OUTPUT;

        echo $OUTPUT->heading(get_string('summaryofattempts', 'simplecertificate'));

        // Prepare table header.
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
        // One row for each attempt.
        foreach ($attempts as $attempt) {
            $row = array();

            // Prepare strings for time taken and date completed.
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
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // If certgrade = 0 return nothing.
        if (empty($this->get_instance()->certgrade)) { // No grade.
            return '';
        }

        switch ($this->get_instance()->certgrade) {
            case self::COURSE_GRADE: // Course grade.
                if ($courseitem = grade_item::fetch_course_item($this->get_course()->id)) {
                    $grade = new grade_grade(array('itemid' => $courseitem->id, 'userid' => $userid));
                    $courseitem->gradetype = GRADE_TYPE_VALUE;
                    $coursegrade = new stdClass();

                    $decimals = $courseitem->get_decimals();
                    // String used.
                    $coursegrade->points = grade_format_gradevalue($grade->finalgrade, $courseitem, true, GRADE_DISPLAY_TYPE_REAL,
                                                                $decimals);
                    $coursegrade->percentage = grade_format_gradevalue($grade->finalgrade, $courseitem, true,
                                                                    GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals);
                    $coursegrade->letter = grade_format_gradevalue($grade->finalgrade, $courseitem, true,
                                                                GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);
                }
            break;

            default: // Module grade.
                     // Get grade from a specific module, stored at certgrade.
                if ($modinfo = $this->get_mod_grade($this->get_instance()->certgrade, $userid)) {
                    // String used.
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

        if ($gradeitem = grade_get_grades($this->get_course()->id, 'mod', $module->name, $cm->instance, $userid)) {
            $item = new grade_item();
            $itemproperties = reset($gradeitem->items);
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
     * Generate a UUID
     * you can verify the generated code in:
     * http://www.famkruithof.net/uuid/uuidgen?typeReq=-1
     *
     * @return string UUID
     */
    protected function get_issue_uuid() {
        global $CFG;
        require_once($CFG->libdir . '/horde/framework/Horde/Support/Uuid.php');
        return (string)new Horde_Support_Uuid();
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    protected function get_teachers() {
        global $CFG, $DB;
        $teachers = array();

        if (!empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);
        } else {
            list($coursecontactroles, $trash) = get_roles_with_cap_in_context($this->get_context(), 'mod/simplecertificate:manage');
        }
        foreach ($coursecontactroles as $roleid) {
            $roleid = (int)$roleid;
            $role = $DB->get_record('role', array('id' => $roleid));
            if ($users = get_role_users($roleid, $this->context, true)) {
                foreach ($users as $teacher) {
                    $manager = new stdClass();
                    $manager->user = $teacher;
                    $manager->username = fullname($teacher);
                    $manager->rolename = role_get_name($role, $this->get_context());
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
        if (!empty($this->get_instance()->emailteachers) && ($teachers = $this->get_teachers())) {
                $emailteachers = array();
            foreach ($teachers as $teacher) {
                $emailteachers[] = $teacher->user->email;
            }
                $this->send_alert_emails($emailteachers);

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
        global $USER, $CFG;

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

                    // Getting email body plain text.
                    $posttext = get_string('emailteachermail', 'simplecertificate', $info) . "\n";

                    // Getting email body html.
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

        // Default orientation is Landescape.
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
        global $CFG;

        // Check if certificate file is already exists, if issued has changes, it will recreated.
        if (empty($issuecert->haschange) && $this->issue_file_exists($issuecert) && !$isbulk) {
            return false;
        }

        if (empty($pdf)) {
            $pdf = $this->create_pdf_object();
        }

        $pdf->AddPage();

        // Getting certificare image.
        $fs = get_file_storage();

        // Get first page image file.
        if (!empty($this->get_instance()->certificateimage)) {
            // Prepare file record object.
            $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
            $firstpageimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'],
                            $fileinfo['filearea'],
                            $fileinfo['itemid'], $fileinfo['filepath'],
                            $this->get_instance()->certificateimage);
            // Read contents.
            if ($firstpageimagefile) {
                $tmpfilename = $firstpageimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'first_image_');
                $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                @unlink($tmpfilename);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate', $this->get_instance()->certificateimage));
            }
        }

        // Writing text.
        $pdf->SetXY($this->get_instance()->certificatetextx, $this->get_instance()->certificatetexty);
        $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->get_instance()->certificatetext), 0, 0, 0,
                            true, 'C');

        // Print QR code in first page (if enable).
        if (!empty($this->get_instance()->qrcodefirstpage) && !empty($this->get_instance()->printqrcode)) {
            $this->print_qrcode($pdf, $issuecert->code);
        }

        if (!empty($this->get_instance()->enablesecondpage)) {
            $pdf->AddPage();
            if (!empty($this->get_instance()->secondimage)) {
                // Prepare file record object.
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                // Get file.
                $secondimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid'], $fileinfo['filepath'], $this->get_instance()->secondimage);

                // Read contents.
                if (!empty($secondimagefile)) {
                    $tmpfilename = $secondimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'second_image_');
                    $pdf->Image($tmpfilename, 0, 0, $this->get_instance()->width, $this->get_instance()->height);
                    @unlink($tmpfilename);
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
            // Add certificade code using QRcode, in a new page (to print in the back).
            if (empty($this->get_instance()->enablesecondpage)) {
                // If secondpage is disabled, create one.
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
        $style = array('border' => 2, 'vpadding' => 'auto', 'hpadding' => 'auto',
                       'fgcolor' => array(0, 0, 0),  // Black.
                       'bgcolor' => array(255, 255, 255), // White.
                       'module_width' => 1, // Width of a single module in points.
                       'module_height' => 1); // Height of a single module in points.

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
        global $DB, $CFG;

        // Check if file exist.
        // If issue certificate has no change, it's must has a file.
        if (empty($issuecert->haschange)) {
            if ($this->issue_file_exists($issuecert)) {
                return $this->get_issue_file($issuecert);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate'));
                return false;
            }
        } else {
            // Cache issued cert, to avoid db queries.
            $this->issuecert = $issuecert;
            if (!$pdf = $this->create_pdf($this->get_issue($issuecert->userid))) {
                // TODO add can't create certificate file error.
                print_error('TODO');
                return false;
            }

            // This avoid function calls loops.
            $issuecert->haschange = 0;

            // Remove old file, if exists.
            if ($this->issue_file_exists($issuecert)) {
                $file = $this->get_issue_file($issuecert);
                $file->delete();
            }

            // Prepare file record object.
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

            if (!empty($CFG->forceloginforprofileimage)) {
                $this->remove_user_image($issuecert->userid);
            }

            $issuecert->pathnamehash = $file->get_pathnamehash();

            // Verify if user is a manager, if not, update issuedcert.
            if (!has_capability('mod/simplecertificate:manage',
                $this->context, $issuecert->userid) && !$DB->update_record('simplecertificate_issues',
                    $issuecert)) {
                    print_error('cannotupdatemod', 'error', null, 'simplecertificate_issue');
                    return false;
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

        // Make the HTML version more XHTML happy  (&amp;).
        $messagehtml = text_to_html($message);

        // Get generated certificate file.
        if ($file = $this->get_issue_file($issuecert)) { // Put in a tmp dir, for e-mail attachament.
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
        $selectreaders = $manager->get_readers('\core\log\sql_reader');
        $reader = reset($selectreaders);

        // This can take a log time to process, but it's accurate
        // it's can be done by get only first and last log entry creation time,
        // but it's far more inaccurate,  could have an option to choose.
        set_time_limit(0);
        $totaltime = 0;
        $sql = "action = 'viewed' AND target = 'course' AND courseid = :courseid AND userid = :userid";

        if ($logs = $reader->get_events_select($sql, array('courseid' => $this->get_course()->id, 'userid' => $userid),
                                            'timecreated ASC', '', '')) {
            foreach ($logs as $log) {
                if (empty($login)) {
                    // For the first time $login is not set so the first log is also the first login.
                    $login = $log->timecreated;
                    $lasthit = $log->timecreated;
                }
                $delay = $log->timecreated - $lasthit;

                if (!($delay > ($CFG->sessiontimeout))) {
                    // The difference between the last log and the current log is more than
                    // the timeout.
                    // Register session value so that we have found a new session!
                    $totaltime += $delay;
                }
                // Now the actual log became the previous log for the next cycle.
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
                    send_stored_file($file, 10, 0, true, array('filename' => $file->get_filename(), 'dontdie' => true));
                break;

                case self::OUTPUT_SEND_EMAIL:
                    $this->send_certificade_email($issuecert);
                    echo $OUTPUT->header();
                    echo $OUTPUT->box(get_string('emailsent', 'simplecertificate') . '<br>' . $OUTPUT->close_window_button(),
                                    'generalbox', 'notice');
                    echo $OUTPUT->footer();
                break;

                // OUTPUT_OPEN_IN_BROWSER.
                default: // Open in browser.
                    send_stored_file($file, 10, 0, false, array('dontdie' => true));
                break;
            }

            if (has_capability('mod/simplecertificate:manage', $this->context, $issuecert->userid)) {
                $file->delete();
            }
        } else {
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
        global $DB, $CFG;

        if (!$user = get_complete_user_data('id', $issuecert->userid)) {
            print_error('nousersfound', 'moodle');
        }

        // If no text set get firstpage text.
        if (empty($certtext)) {
            $certtext = $this->get_instance()->certificatetext;
        }
        $certtext = format_text($certtext, FORMAT_HTML, array('noclean' => true));

        $a = new stdClass();
        $a->username = strip_tags(fullname($user));
        $a->idnumber = strip_tags($user->idnumber);
        $a->firstname = strip_tags($user->firstname);
        $a->lastname = strip_tags($user->lastname);
        $a->email = strip_tags($user->email);
        $a->icq = strip_tags($user->icq);
        $a->skype = strip_tags($user->skype);
        $a->yahoo = strip_tags($user->yahoo);
        $a->aim = strip_tags($user->aim);
        $a->msn = strip_tags($user->msn);
        $a->phone1 = strip_tags($user->phone1);
        $a->phone2 = strip_tags($user->phone2);
        $a->institution = strip_tags($user->institution);
        $a->department = strip_tags($user->department);
        $a->address = strip_tags($user->address);
        $a->city = strip_tags($user->city);

        // Add userimage url only if have a picture.
        if ($user->picture > 0) {
            $a->userimage = $this->get_user_image_url($user);
        } else {
            $a->userimage = '';
        }

        if (!empty($user->country)) {
            $a->country = get_string($user->country, 'countries');
        } else {
            $a->country = '';
        }

        // Formatting URL, if needed.
        $url = $user->url;
        if (!empty($url) && strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }
        $a->url = $url;

        // Getting user custom profiles fields.
        $userprofilefields = $this->get_user_profile_fields($user->id);
        foreach ($userprofilefields as $key => $value) {
            $key = 'profile_' . $key;
            $a->$key = strip_tags($value);
        }
        // The course name never change form a certificate to another, useless
        // text mark and atribbute, can be removed.
        $a->coursename = strip_tags($this->get_instance()->coursename);
        $a->grade = $this->get_grade($user->id);
        $a->date = $this->get_date($issuecert, $user->id);
        $a->outcome = $this->get_outcome($user->id);
        $a->certificatecode = $issuecert->code;

        // This code stay here only beace legacy support, coursehours variable was removed
        // see issue 61 https://github.com/bozoh/moodle-mod_simplecertificate/issues/61.
        if (isset($this->get_instance()->coursehours)) {
            $a->hours = strip_tags($this->get_instance()->coursehours . ' ' . get_string('hours', 'simplecertificate'));
        } else {
            $a->hours = '';
        }

        $teachers = $this->get_teachers();
        if (empty($teachers)) {
            $teachers = '';
        } else {
            $t = array();
            foreach ($teachers as $teacher) {
                $t[] = content_to_text($teacher->rolename . ': ' . $teacher->username, FORMAT_MOODLE);
            }
            $a->teachers = implode("<br>", $t);
        }

        // Fetch user actitivy restuls.
        $a->userresults = $this->get_user_results($issuecert->userid);

        // Get User role name in course.
        if ($userrolename = get_user_roles_in_course($user->id, $this->get_course()->id)) {
            $a->userrolename = content_to_text($userrolename, FORMAT_MOODLE);
        } else {
            $a->userrolename = '';
        }

        // Get user enrollment start date
        // see funtion  enrol_get_enrolment_end($courseid, $userid), which get enddate, not start.
        $sql = "SELECT ue.timestart
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
              WHERE ue.userid = :userid AND e.status = :enabled AND u.deleted = 0";

        $params = array('enabled' => ENROL_INSTANCE_ENABLED, 'userid' => $user->id, 'courseid' => $this->get_course()->id);

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
            // Due #148 bug, i must disable filters, because activities names {USERRESULTS}
            // will be replaced by actitiy link, don't make sense put activity link
            // in the certificate, only activity name and grade
            // para=> false to remove the <div> </div>  form strings.
            $replace[] = (string)$value;
        }

        if ($search) {
            $certtext = str_replace($search, $replace, $certtext);
        }

        // Clear not setted  textmark.
        $certtext = preg_replace('[\{(.*)\}]', "", $certtext);
        return $this->remove_links(format_text($certtext, FORMAT_MOODLE));

    }

    // Auto link filter puts links in the certificate text,
    // and it's must be removed. See #111.
    protected function remove_links($htmltext) {
        global $CFG;
        require_once($CFG->libdir.'/htmlpurifier/HTMLPurifier.safe-includes.php');
        require_once($CFG->libdir.'/htmlpurifier/locallib.php');

        // This code is in weblib.php (purify_html function).
        $config = HTMLPurifier_Config::createDefault();
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        $version = empty($CFG->version) ? 0 : $CFG->version;
        $cachedir = "$CFG->localcachedir/htmlpurifier/$version";
        if (!file_exists($cachedir)) {
            // Purging of caches may remove the cache dir at any time,
            // luckily file_exists() results should be cached for all existing directories.
            $purifiers = array();
            $caches = array();
            gc_collect_cycles();

            make_localcache_directory('htmlpurifier', false);
            check_dir_exists($cachedir);
        }
        $config->set('Cache.SerializerPath', $cachedir);
        $config->set('Cache.SerializerPermissions', $CFG->directorypermissions);
        $config->set('HTML.ForbiddenElements', array('script', 'style', 'applet', 'a'));
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($htmltext);
    
    }
    
    protected function remove_user_image($userid) {
        $filename = 'f1-' . $userid;

        $fileinfo = self::get_certificate_tmp_fileinfo($this->get_context());
        $fs = get_file_storage();

        if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $filename)) {
            // Got it,  now remove it.
            $file->delete();
        }
    }

    /**
     * Return user profile image URL
     */
    protected function get_user_image_url($user) {
        global $CFG;

        // Beacuse bug #141 forceloginforprofileimage=enabled
        // i must check if this contiguration is enalbe and by pass it.
        $path = '/';
        $filename = 'f1';
        $usercontext = context_user::instance($user->id, IGNORE_MISSING);
        if (empty($CFG->forceloginforprofileimage)) {
            // Not enable so it's very easy.
            $url = moodle_url::make_pluginfile_url($usercontext->id, 'user', 'icon', null, $path, $filename);
            $url->param('rev', $user->picture);
        } else {

            // It's enable, so i must copy the profile image to somewhere else, so i can get the image;
            // Try to get the profile image file.
            $fs = get_file_storage();
            if ((!$file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.png'))
            && (!$file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $filename . '.jpg'))) {
                    // I Can't get the file, sorry.
                    return '';
            }

            // With the file, now let's copy to plugin filearea.
            $fileinfo = self::get_certificate_tmp_fileinfo($this->get_context()->id);

            // Since f1 is the same name for all user, i must to rename the file, i think
            // add userid, since it's unique.
            $fileinfo['filename'] = 'f1-' . $user->id;

            // I must verify if image is already copied, or i get an error.
            // This file will be removed  as soon as certificate file is generated.
            if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
                // File don't exists yet, so, copy to tmp file area.
                $fs->create_file_from_storedfile($fileinfo, $file);
            }

            // Now creating the image URL.
            $url = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                    null, $fileinfo['filepath'], $fileinfo['filename']);
        }
        return '<img src="' . $url->out() . '"  width="100" height="100" />';
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

        // Get date format.
        if (empty($this->get_instance()->certdatefmt)) {
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->get_instance()->certdatefmt;
        }

        // Set to current time.
        $date = time();

        // Set certificate issued date.
        if ($this->get_instance()->certdate == self::CERT_ISSUE_DATE) {
            $date = $issuecert->timecreated;
        }

        // Get the course start date.
        if ($this->get_instance()->certdate == self::COURSE_START_DATE) {
            $sql = "SELECT id, startdate FROM {course} c
              WHERE c.id = :courseid";

            $coursestartdate = $DB->get_record_sql($sql, array('courseid' => $this->get_course()->id));
            $date = $coursestartdate->startdate;
        }

        // Get the enrolment end date.
        if ($this->get_instance()->certdate == self::COURSE_COMPLETATION_DATE) {
            $sql = "SELECT MAX(c.timecompleted) as timecompleted FROM {course_completions} c
                 WHERE c.userid = :userid AND c.course = :courseid";

            $timecompleted = $DB->get_record_sql($sql, array('userid' => $issuecert->userid,
                            'courseid' => $this->get_course()->id));
            if ($timecompleted && !empty($timecompleted->timecompleted)) {
                $date = $timecompleted->timecompleted;
            }
        // Get the module grade date.
        } else if ($this->get_instance()->certdate > 0
            && $modinfo = $this->get_mod_grade($this->get_instance()->certdate, $issuecert->userid)) {
                $date = $modinfo->dategraded;

        }

        return userdate($date, $format);
    }

    /**
     *  Return all actitity grades, in the format:
     *  Grade Item Name: grade<br>
     *
     * @param int $userid the user id, if none are supplied, gets $USER->id
     */
    protected function get_user_results($userid = null) {
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $items = grade_item::fetch_all(array('courseid' => $this->course->id));
        if (empty($items)) {
            return '';
        }

        //Sorting grade itens by sortorder
        usort($items, function($a, $b) {
            $a_sortorder = $a->sortorder;
            $b_sortorder = $b->sortorder;
            if ($a_sortorder == $b_sortorder) {
                return 0;
            }
            return ($a_sortorder < $b_sortorder) ? -1 : 1;
        });

        $retval = '';
        foreach ($items as $id => $item) {
            // Do not include grades for course itens.
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
        global $COURSE;

        // Get all outcomes in course.
        $gradeseq = new grade_tree($COURSE->id, false, true, '', false);
        if ($gradeitems = $gradeseq->items) {
            // List of item for menu.
            $printoutcome = array();
            foreach ($gradeitems as $gradeitem) {
                if (!empty($gradeitem->outcomeid)) {
                    $itemmodule = $gradeitem->itemmodule;
                    $printoutcome[$gradeitem->id] = $itemmodule . ': ' . $gradeitem->get_name();
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
        global $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        if ($this->get_instance()->outcome > 0
            && $gradeitem = new grade_item(array('id' => $this->get_instance()->outcome))) {

            $outcomeinfo = new stdClass();
            $outcomeinfo->name = $gradeitem->get_name();
            $outcome = new grade_grade(array('itemid' => $gradeitem->id, 'userid' => $userid));
            $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_REAL);
            return $outcomeinfo->name . ': ' . $outcomeinfo->grade;
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
                        require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
                        $newfield = 'profile_field_' . $field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        if ($formfield->is_visible() && !$formfield->is_empty()) {
                            if ($field->datatype == 'checkbox') {
                                $usercustomfields->{$field->shortname} = ($formfield->data == 1 ? get_string('yes') : get_string('no'));
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
        global $USER, $CFG;

        if (empty($user)) {
            $user = $USER;
        }

        if (has_capability('mod/simplecertificate:manage', $this->context, $user)) {
            return 'Manager user';
        }

        if ($chkcompletation) {
            $completion = new completion_info($this->course);
            if ($completion->is_enabled($this->coursemodule) && $this->get_instance()->requiredtime) {
                if ($this->get_course_time($user) < $this->get_instance()->requiredtime) {
                    $a = new stdClass();
                    $a->requiredtime = $this->get_instance()->requiredtime;
                    return get_string('requiredtimenotmet', 'simplecertificate', $a);
                }
                // Mark as complete.
                $completion->update_state($this->coursemodule, COMPLETION_COMPLETE, $user->id);
            }

            if ($CFG->enableavailability
                && !$this->check_user_can_access_certificate_instance($user->id)) {
                    return get_string('cantissue', 'simplecertificate');

            }
            return null;
        }
    }

    /**
     * get full user status of on certificate instance (if it can view/access)
     * this method helps the unit test (easy to mock)
     * @param int $userid
     */
    protected function check_user_can_access_certificate_instance($userid) {
        return info_module::is_user_visible($this->get_course_module(), $userid, false);
    }

    /**
     * Verify if cetificate file exists
     *
     * @param stdClass $issuecert Issued certificate object
     * @return true if exist
     */
    protected function issue_file_exists(stdClass $issuecert) {
        $fs = get_file_storage();

        // Check for file first.
        return $fs->file_exists_by_hash($issuecert->pathnamehash);
    }

    // View methods.
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

    // Default view.
    public function view_default(moodle_url $url, $canmanage) {
        global $CFG, $OUTPUT, $USER;

        if (!$url->get_param('action')) {

            echo $OUTPUT->header();

            if ($canmanage) {
                $this->show_tabs($url);
            }

            // Check if the user can view the certificate.
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
                // Create new certificate record, or return existing record.
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

                $link = new moodle_url('/mod/simplecertificate/view.php',
                                array('id' => $this->coursemodule->id, 'action' => 'get'));
                $button = new single_button($link, $linkname);
                $button->add_action(new popup_action('click', $link, 'view' . $this->coursemodule->id,
                                                    array('height' => 600, 'width' => 800)));

                echo html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:center'));
            }
            echo $OUTPUT->footer();
        } else { // Output to pdf.
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

        // Get all users that can manage this certificate to exclude them from the report.
        $certmanagers = get_users_by_capability($this->context, 'mod/simplecertificate:manage', 'u.id');

        $sql = "SELECT u.*, ci.code, ci.timecreated ";
        $sql .= "FROM {user} u INNER JOIN {simplecertificate_issues} ci ON u.id = ci.userid ";
        $sql .= "WHERE u.deleted = 0 AND ci.certificateid = :certificateid AND timedeleted IS NULL ";
        $sql .= "ORDER BY {$sort}";
        $issedusers = $DB->get_records_sql($sql, array('certificateid' => $this->get_instance()->id));

        // Now exclude all the certmanagers.
        foreach ($issedusers as $id => $user) {
            if (!empty($certmanagers[$id])) { // Exclude certmanagers.
                unset ($issedusers[$id]);
            }
        }

        // If groupmembersonly used, remove users who are not in any group.
        if (!empty($issedusers) && !empty($CFG->enablegroupings) && $this->coursemodule->groupmembersonly
            && $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $issedusers = array_intersect($issedusers, array_keys($groupingusers));
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
                        // Remove this user as it isn't in the group!
                        unset($issedusers[$id]);
                    }
                }
            }
        }
        return $issedusers;
    }

    // Issued certificates view.
    public function view_issued_certificates(moodle_url $url, array $selectedusers = null) {
        global $OUTPUT, $CFG, $DB;

        // Declare some variables.
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
        $action = $url->get_param('action');
        $usercount = 0;

        if (!$selectedusers) {
            $users = $this->get_issued_certificate_users($orderby, $groupmode);
            $usercount = count($users);
        } else {
            list($sqluserids, $params) = $DB->get_in_or_equal($selectedusers);
            $sql = "SELECT * FROM {user} WHERE id $sqluserids";
            // Adding sort.
            $sort = '';
            $override = new stdClass();
            $override->firstname = 'firstname';
            $override->lastname = 'lastname';
            $fullnamelanguage = get_string('fullnamedisplay', '', $override);
            if (($CFG->fullnamedisplay == 'firstname lastname') || ($CFG->fullnamedisplay == 'firstname') ||
            ($CFG->fullnamedisplay == 'language' && $fullnamelanguage == 'firstname lastname')) {
                $sort = " ORDER BY firstname, lastname";
            } else { // ...($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname').
                $sort = " ORDER BY lastname, firstname";
            }
            $users = $DB->get_records_sql($sql . $sort, $params);
        }

        if (!$action) {
            echo $OUTPUT->header();
            $this->show_tabs($url);

            if ($groupmode) {
                groups_get_activity_group($this->coursemodule, true);
            }

            groups_print_activity_menu($this->coursemodule, $url);

            if (!$users) {
                $OUTPUT->notification(get_string('nocertificatesissued', 'simplecertificate'));
                echo $OUTPUT->footer();
                exit();
            }

            // Create the table for the users.
            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";

            $table->head = array(' ', get_string('fullname'), get_string('grade'));
            $table->align = array("left", "left", "center");
            $table->size = array('1%', '89%', '10%');

            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";
            $table->head = array(' ', $strto, $strdate, $strgrade, $strcode);
            $table->align = array("left", "left", "left", "center", "center");
            $table->size = array('1%', '54%', '10%', '5%', '30%');

            $users = array_slice($users, intval($page * $perpage), $perpage);

            foreach ($users as $user) {
                $user_cert = $this->get_issue($user);
                $name = $OUTPUT->user_picture($user) . fullname($user);
                $chkbox = html_writer::checkbox('selectedusers[]', $user->id, false);
                $date = userdate($user_cert->timecreated) . simplecertificate_print_issue_certificate_file($user_cert);
                $code = $user->code;
                $table->data[] = array($chkbox, $name, $date, $this->get_grade($user->id), $code);
            }

            // Create table to store buttons.
            $tablebutton = new html_table();
            $tablebutton->attributes['class'] = 'downloadreport';

            $deleteselectedbutton = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'delete', 'type' => 'selected')),
                            get_string('deleteselected', 'simplecertificate'));
            $deleteallbutton = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'delete', 'type' => 'all')),
                            get_string('deleteall', 'simplecertificate'));
            $btndownloadods = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'ods')),
                            get_string("downloadods"));
            $btndownloadxls = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'xls')),
                            get_string("downloadexcel"));
            $btndownloadtxt = $OUTPUT->single_button(
                            $url->out_as_local_url(false, array('action' => 'download', 'type' => 'txt')),
                            get_string("downloadtext"));
            $tablebutton->data[] = array($deleteselectedbutton,
                                   $deleteallbutton,
                                   $btndownloadods,
                                   $btndownloadxls,
                                   $btndownloadtxt
            );

            echo '<br />';
            echo '<form id="bulkissue" name="bulkissue" method="post" action="view.php">';
            echo html_writer::table($table);
            echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
            echo html_writer::tag('div', html_writer::table($tablebutton), array('style' => 'margin:auto; width:50%'));
            echo '</form>';

        } else {
            $type = $url->get_param('type');
            $url->remove_params('action', 'type', 'selectedusers');
            // Override $users param if no user are selected, but clicks in delete selected.
            switch ($action) {
                case 'delete':
                    switch ($type) {
                        case  'all':
                            // Override $users param, if there is a selected users, but it clicks on delete all.
                            if ($selectedusers) {
                                $users = $this->get_issued_certificate_users($orderby, $groupmode);
                            }
                        break;

                        case 'selected':
                            // No user selected, add an empty array to avoid errors.
                            if (!$selectedusers) {
                                $users = array();
                            }
                        break;
                    }
                    foreach ($users as $user) {
                        $issuedcert = $this->get_issue($user);
                        // If it's issued, then i remove.
                        if ($issuedcert) {
                            $this->remove_issue($issuedcert, false);
                        }
                    }
                break;

                case 'download':
                    $page = $perpage = 0;

                    // Override $users param, if there is a selected users.
                    $users = $this->get_issued_certificate_users($orderby, $groupmode);

                    // Calculate file name.
                    $filename = clean_filename($this->get_instance()->coursename . '-' .
                                     strip_tags(format_string($this->get_instance()->name, true)) . '.' .
                                     strip_tags(format_string($type, true)));

                    switch ($type) {
                        case 'ods':
                            require_once("$CFG->libdir/odslib.class.php");

                            // Creating a workbook.
                            $workbook = new MoodleODSWorkbook("-");
                            // Send HTTP headers.
                            $workbook->send(format_text($filename, true));
                            // Creating the first worksheet.
                            $myxls = $workbook->add_worksheet($strreport);

                            // Print names of all the fields.
                            $myxls->write_string(0, 0, get_string("fullname"));
                            $myxls->write_string(0, 1, get_string("idnumber"));
                            $myxls->write_string(0, 2, get_string("group"));
                            $myxls->write_string(0, 3, $strdate);
                            $myxls->write_string(0, 4, $strgrade);
                            $myxls->write_string(0, 5, $strcode);

                            // Generate the data for the body of the spreadsheet.
                            $i = 0;
                            $row = 1;
                            if ($users) {
                                foreach ($users as $user) {
                                    $myxls->write_string($ro6w, 0, fullname($user));
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
                            // Close the workbook.
                            $workbook->close();
                        break;

                        case 'xls':
                            require_once("$CFG->libdir/excellib.class.php");

                            // Creating a workbook.
                            $workbook = new MoodleExcelWorkbook("-");
                            // Send HTTP headers.
                            $workbook->send(format_text($filename, true));
                            // Creating the first worksheet.
                            $myxls = $workbook->add_worksheet($strreport);

                            // Print names of all the fields.
                            $myxls->write_string(0, 0, get_string("fullname"));
                            $myxls->write_string(0, 1, get_string("idnumber"));
                            $myxls->write_string(0, 2, get_string("group"));
                            $myxls->write_string(0, 3, $strdate);
                            $myxls->write_string(0, 4, $strgrade);
                            $myxls->write_string(0, 5, $strcode);

                            // Generate the data for the body of the spreadsheet.
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
                            // Close the workbook.
                            $workbook->close();
                        break;

                        // ...txt.
                        default:

                            header("Content-Type: application/download\n");
                            header("Content-Disposition: attachment; filename=\"" . format_text($filename, true) . "\"");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
                            header("Pragma: public");

                            // Print names of all the fields.
                            echo get_string("fullname") . "\t" . get_string("idnumber") . "\t";
                            echo get_string("group") . "\t";
                            echo $strdate . "\t";
                            echo $strgrade . "\t";
                            echo $strcode . "\n";

                            // Generate the data for the body of the spreadsheet.
                            $i = 0;
                            $row = 1;
                            if ($users) {
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
                            }
                        break;
                    }
                break;
            }
            redirect($url);
        }
        echo $OUTPUT->footer();
    }

    public function view_bulk_certificates(moodle_url $url, array $selectedusers = null) {
        global $OUTPUT, $CFG, $DB;

        $coursectx = context_course::instance($this->get_course()->id);

        $page = $url->get_param('page');
        $perpage = $url->get_param('perpage');
        $issuelist = $url->get_param('issuelist');
        $action = $url->get_param('action');
        $type = $url->get_param('type');
        $groupid = 0;
        $groupmode = groups_get_activity_groupmode($this->coursemodule);
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->coursemodule, true);
        }

        $pagestart = intval($page * $perpage);
        $usercount = 0;
        if (!$selectedusers) {
            $users = get_enrolled_users($coursectx, '', $groupid);
            $usercount = count($users);
        } else {
            list($sqluserids, $params) = $DB->get_in_or_equal($selectedusers);
            $sql = "SELECT * FROM {user} WHERE id $sqluserids";
            // Adding sort.
            $sort = '';
            $override = new stdClass();
            $override->firstname = 'firstname';
            $override->lastname = 'lastname';
            $fullnamelanguage = get_string('fullnamedisplay', '', $override);
            if (($CFG->fullnamedisplay == 'firstname lastname') || ($CFG->fullnamedisplay == 'firstname') ||
             ($CFG->fullnamedisplay == 'language' && $fullnamelanguage == 'firstname lastname')) {
                $sort = " ORDER BY firstname, lastname";
            } else {
                $sort = " ORDER BY lastname, firstname";
            }
            $users = $DB->get_records_sql($sql . $sort, $params);
        }

        if (!$action) {
            echo $OUTPUT->header();
            $this->show_tabs($url);

            groups_print_activity_menu($this->coursemodule, $url);

            // Add to  values to constants.
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
                    'zip' => get_string('multipdf', 'simplecertificate'),
                    'email' => get_string('sendtoemail', 'simplecertificate'));
            echo html_writer::select($selectoptions, 'type', $type);
            $table = new html_table();
            $table->width = "95%";
            $table->tablealign = "center";
            $table->head = array(' ', get_string('fullname'), get_string('grade'));
            $table->align = array("left", "left", "center");
            $table->size = array('1%', '89%', '10%');

            // BUG #157, the paging is afecting download files,
            // so only apply paging when displaying users.
            $users = array_slice($users, $pagestart, $perpage);

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

            // Calculate file name.
            $filename = str_replace(' ', '_',
                                    clean_filename(
                                                $this->get_instance()->coursename . ' ' .
                                                 get_string('modulenameplural', 'simplecertificate') . ' ' .
                                                 strip_tags(format_string($this->get_instance()->name, true)) . '.' .
                                                 strip_tags(format_string($type, true))));

            switch ($type) {

                // One zip with all certificates in separated files.
                case 'zip':
                    $filesforzipping = array();
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuedcert = $this->get_issue($user);
                            if ($file = $this->get_issue_file($issuedcert)) {
                                $fileforzipname = $file->get_filename();
                                $filesforzipping[$fileforzipname] = $file;
                            } else {
                                print_error(get_string('filenotfound', 'simplecertificate'));
                            }
                        }
                    }

                    $tempzip = $this->create_temp_file('issuedcertificate_');

                    // Zipping files.
                    $zipper = new zip_packer();
                    if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
                        // Send file and delete after sending.
                        send_temp_file($tempzip, $filename);
                    }
                 break;

                case 'email':
                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            $issuedcert = $this->get_issue($user);
                            if ($this->get_issue_file($issuedcert)) {
                                $this->send_certificade_email($issuedcert);
                            } else {
                                print_error('filenotfound', 'simplecertificate');
                            }
                        }
                    }
                    $url->remove_params('action', 'type');
                    redirect($url, get_string('emailsent', 'simplecertificate'), 5);
                 break;

                // One pdf with all certificates.
                default:
                    $pdf = $this->create_pdf_object();

                    foreach ($users as $user) {
                        $canissue = $this->can_issue($user, $issuelist != 'allusers');
                        if (empty($canissue)) {
                            // To one pdf file.
                            $issuedcert = $this->get_issue($user);
                            $this->create_pdf($issuedcert, $pdf, true);

                            // Save certificate PDF.
                            if (!$this->issue_file_exists($issuedcert)) {
                                // To force file creation.
                                $issuedcert->haschage = true;
                                $this->get_issue_file($issuedcert);
                            }
                        }
                    }
                    $pdf->Output($filename, 'D');

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
        if ($action) {
                $event = \mod_simplecertificate\event\course_module_viewed::create(
                       array(
                            'objectid' => $this->get_course_module()->instance,
                            'context' => $this->get_context(),
                            'other' => array('certificatecode' => $this->get_issue()->code)));
                       $event->add_record_snapshot('course', $this->get_course());
        }

        if (!empty($event)) {
            $event->trigger();
        }
    }
}
