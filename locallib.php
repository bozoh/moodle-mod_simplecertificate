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
 * @copyright  Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/mod/simplecertificate/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/pdflib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class simplecertificate {

    const CERTIFICATE_IMAGE_FILE_AREA = 'image';
    const CERTIFICATE_ISSUES_FILE_AREA = 'issues';
    const CERTIFICATE_COMPONENT_NAME = 'mod_simplecertificate';
    const OUTPUT_OPEN_IN_BROWSER = 0;
    const OUTPUT_FORCE_DOWNLOAD = 1;
    const OUTPUT_SEND_EMAIL = 2;

    public $id;
    public $name;
    public $intro;
    public $introformat;
    public $timecreated;
    public $timemodified;
    public $width;
    public $height;
    public $certificateimage;
    public $certificatetext;
    public $certificatetextformat;
    public $certificatetextx;
    public $certificatetexty;
    public $coursehours;
    public $outcome;
    public $coursename;
    public $certdate;
    public $certdatefmt;
    public $emailfrom;
    public $emailothers;
    public $emailteachers;
    public $savecert;
    public $reportcert;
    public $delivery;
    public $requiredtime;
    public $certgrade;
    public $gradefmt;
    public $codex;
    public $codey;
    public $disablecode;
    public $enablesecondpage;
    public $secondpagex;
    public $secondpagey;
    public $secondpagetext;
    public $secondpagetextformat;
    public $course;
    public $coursemodule;
    public $context;
    private $orientation = '';
    private $cm;

    public function __construct(stdclass $dbrecord, stdclass $context = null) {
        global $DB;

        foreach ($dbrecord as $field => $value) {
            if (property_exists('simplecertificate', $field)) {
                $this->{$field} = $value;
            }
        }

        if (empty($this->coursemodule)) {
            $this->cm = get_coursemodule_from_instance('simplecertificate', $this->id);
            $this->coursemodule = $this->cm->id;
        } else {
            $this->cm = get_coursemodule_from_id('simplecertificate', $this->coursemodule);
        }

        if (is_null($context)) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->coursemodule);
        } else {
            $this->context = $context;
        }

        if (empty($this->coursename)) {
            $course = $DB->get_record('course', array('id' => $this->course));
            $this->coursename = $course->fullname;
        }

        if ($this->height > $this->width) {
            $this->orientation = 'P';
        } else {
            $this->orientation = 'L';
        }
    }

    public static function get_certificate_image_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        $fileinfo = array(
            'contextid' => $contextid, // ID of context
            'component' => self::CERTIFICATE_COMPONENT_NAME, // usually = table name
            'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA, // usually = table name
            'itemid' => 0, // usually = ID of row in table
            'filepath' => '/'           // any path beginning and ending in /
        );
        return $fileinfo;
    }

    public static function get_certificate_issue_fileinfo($userid, $issueid, $context) {

        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        $fileinfo = array(
            'contextid' => $contextid, // ID of context
            'component' => self::CERTIFICATE_COMPONENT_NAME, // usually = table name
            'filearea' => self::CERTIFICATE_ISSUES_FILE_AREA, // usually = table name
            'itemid' => $issueid, // usually = ID of row in table
            'filepath' => '/', // any path beginning and ending in /
            'mimetype' => 'application/pdf', // any filename
            'userid' => $userid
        );


        return $fileinfo;
    }

    /**
     * Inserts preliminary user data when a certificate is viewed.
     * Prevents form from issuing a certificate upon browser refresh.
     *
     * @param stdClass $course
     * @param stdClass $user
     * @param stdClass $certificate
     * @param stdClass $cm
     * @return stdClass the newly created certificate issue
     */
    function get_issue($user) {
        global $DB;
        // Check if there is an issue already, should only ever be one, timedeleted must be null
        if (!$certissue = $DB->get_record('simplecertificate_issues', array('userid' => $user->id, 'certificateid' => $this->id, 'timedeleted' => null))) {
            // Create new certificate issue record
            $certissue = new stdClass();
            $certissue->certificateid = $this->id;
            $certissue->userid = $user->id;
            $certissue->username = fullname($user);
            $certissue->coursename = format_string($this->coursename, true);
            $certissue->timecreated = time();
            $certissue->code = $this->get_issue_uuid();

            if (!has_capability('mod/simplecertificate:manage', $this->context)) {
                $certissue->id = $DB->insert_record('simplecertificate_issues', $certissue);
            } else {
                $certissue->id = rand(0, 4);
            }

            // Email to the teachers and anyone else
            if ($this->emailteachers != 0)
                $this->send_alert_email_teachers();

            if (!empty($this->emailothers))
                $this->send_alert_email_others();
        }
        return $certissue;
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
        if ($issues = $DB->get_records_sql($sql, array('certificateid' => $this->id, 'userid' => $USER->id))) {
            return $issues;
        }

        return false;
    }

    /**
     * Prints a table of previously issued certificates--used for reissue.
     *
     * @param stdClass $course
     * @param stdClass $certificate
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
        $gradecolumn = $this->certgrade;
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
    public function get_grade($userid = null) {
        global $USER, $DB;

        if (empty($this->certgrade))
            return '';

        if (empty($userid)) {
            $userid = $USER->id;
        }

        switch ($this->certgrade) {
            case 1 :  //Course grade
                if ($course_item = grade_item::fetch_course_item($this->course)) {
                    $grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid));
                    $course_item->gradetype = GRADE_TYPE_VALUE;
                    $coursegrade = new stdClass;
                    // String used
                    $coursegrade->points = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2);
                    $coursegrade->percentage = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2);
                    $coursegrade->letter = grade_format_gradevalue($grade->finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0);
                }
                break;

            default : //Module grade
                if ($modinfo = $this->get_mod_grade($this->certgrade, $userid)) {
                    // String used
                    $coursegrade = new stdClass;
                    $coursegrade->points = $modinfo->points;
                    $coursegrade->percentage = $modinfo->percentage;
                    $coursegrade->letter = $modinfo->letter;
                    break;
                }
        }

        if (!is_null($coursegrade)) {
            switch ($this->gradefmt) {
                case 1 :
                    return $coursegrade->percentage;
                    break;
                case 2 :
                    return $coursegrade->points;
                    break;
                case 3 :
                    return $coursegrade->letter;
                    break;
            }
        }

        return '';
    }

    /**
     * Prepare to print an activity grade.
     *
     * @param int $moduleid
     * @param int $userid
     * @return stdClass|bool return the mod object if it exists, false otherwise
     */
    private function get_mod_grade($moduleid, $userid) {
        global $DB;

        $cm = $DB->get_record('course_modules', array('id' => $moduleid));
        $module = $DB->get_record('modules', array('id' => $cm->module));

        if ($grade_item = grade_get_grades($this->course, 'mod', $module->name, $cm->instance, $userid)) {
            $item = new grade_item();
            $itemproperties = reset($grade_item->items);
            foreach ($itemproperties as $key => $value) {
                $item->$key = $value;
            }
            $modinfo = new stdClass;
            $modinfo->name = utf8_decode($DB->get_field($module->name, 'name', array('id' => $cm->instance)));
            $grade = $item->grades[$userid]->grade;
            $item->gradetype = GRADE_TYPE_VALUE;
            $item->courseid = $this->course;

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
    private function get_issue_uuid() {
        global $CFG;
        require_once (dirname(__FILE__) . '/lib.uuid.php');
        $UUID = UUID::mint(UUID::VERSION_1, self::CERTIFICATE_COMPONENT_NAME);
        return $UUID->__toString();
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    private function get_teachers() {
        global $CFG, $USER, $DB;
        $teachers = array();

        if (!empty($CFG->coursecontact)) {
            $coursecontactroles = explode(',', $CFG->coursecontact);

            foreach ($coursecontactroles as $roleid) {
                $role = $DB->get_record('role', array('id' => $roleid));
                $roleid = (int) $roleid;
                if ($users = get_role_users($roleid, $this->context, true)) {
                    foreach ($users as $teacher) {
                        if ($teacher->id == $USER->id) {
                            continue; // do not send self
                        }
                        $teachers[$teacher->id] = $teacher;
                    }
                }
            }
        } else {
            $users = get_users_by_capability($this->context, 'mod/simplecertificate:manage', '', '', '', '', '', '', false, false);

            foreach ($users as $teacher) {
                if ($teacher->id == $USER->id) {
                    continue; // do not send self
                }
                $teachers[$teacher->id] = $teacher;
            }
        }

        return $teachers;
    }

    /**
     * Alerts teachers by email of received certificates. First checks
     * whether the option to email teachers is set for this certificate.
     *
     */
    private function send_alert_email_teachers() {
        if ($teachers = $this->get_teachers()) {
            $emailteachers = array();
            foreach ($teachers as $teacher) {
                $emailteachers[] = $teacher->email;
            }
            $this->send_alert_emails($emailteachers);
        }
    }

    /**
     * Alerts others by email of received certificates. First checks
     * whether the option to email others is set for this certificate.
     * Uses the email_teachers info.
     * Code suggested by Eloy Lafuente
     *
     */
    private function send_alert_email_others() {
        if ($this->emailothers) {
            $others = explode(',', $this->emailothers);
            if ($others)
                $this->send_alert_emails($others);
        }
    }

    private function send_alert_emails($emails) {
        global $USER, $CFG, $DB;

        if ($emails) {
            $strawarded = get_string('awarded', 'simplecertificate');
            foreach ($emails as $email) {
                $email = trim($email);
                if (validate_email($email)) {
                    $destination = new stdClass;
                    $destination->email = $email;

                    $info = new stdClass;
                    $info->student = fullname($USER);
                    $info->course = format_string($this->coursename, true);
                    $info->certificate = format_string($this->name, true);
                    $info->url = $CFG->wwwroot . '/mod/simplecertificate/report.php?id=' . $this->cm->id;
                    $from = $info->student;
                    $postsubject = $strawarded . ': ' . $info->student . ' -> ' . $this->name;

                    //Getting email body plain text
                    $posttext = get_string('emailteachermail', 'simplecertificate', $info) . "\n";

                    //Getting email body html
                    $posthtml = '<font face="sans-serif">';
                    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'simplecertificate', $info) . '</p>';
                    $posthtml .= '</font>';

                    @email_to_user($destination, $from, $postsubject, $posttext, $posthtml);  // If it fails, oh well, too bad.
                }
            }
        }
    }

    private function create_pdf($issuecert) {
        global $DB, $USER, $CFG;

        //Getting certificare image
        $fs = get_file_storage();

        // Prepare file record object
        $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
        // Get file
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $this->certificateimage);

        // Read contents
        if ($file) {
            $temp_manager = $this->move_temp_dir($file);
        } else {
            print_error(get_string('filenotfound', 'simplecertificate', $this->certificateimage));
        }

        $pdf = new TCPDF($this->orientation, 'mm', array($this->width, $this->height), true, 'UTF-8', true, false);
        $pdf->SetTitle($this->name);
        $pdf->SetSubject($this->name . ' - ' . $this->coursename);
        $pdf->SetKeywords(get_string('keywords', 'simplecertificate') . ',' . $this->coursename);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        //Issue #5

        $pdf->setFontSubsetting(true);
        $pdf->AddPage();

        $pdf->Image($temp_manager->absolutefilepath, 0, 0, $this->width, $this->height);

        $pdf->SetXY($this->certificatetextx, $this->certificatetexty);
        $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert), 0, 0, 0, true, 'C');
       
        @remove_dir($temp_manager->path);

        if (empty($this->disablecode)) {
            //Add certificade code using QRcode, in a new page (to print in the back)
            $pdf->AddPage();
            $style = array(
                'border' => 2,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => false, //array(255,255,255)
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
            );
            $codeurl = "$CFG->wwwroot/mod/simplecertificate/verify.php?code=$issuecert->code";
            $pdf->write2DBarcode($codeurl, 'QRCODE,H', $this->codex, $this->codey, 50, 50, $style, 'N');
            $pdf->setFontSize(10);
            $pdf->setFontStretching(75);
            $pdf->Text($this->codex - 1, $this->codey + 50, $issuecert->code);
        }

        return $pdf;
    }

    /**
     * This function returns success or failure of file save
     *
     * @param string $pdf is the string contents of the pdf
     * @param string $filename pdf filename
     * @param int $issueid the certificate issue record id
     * @return bool return true if successful, false otherwise
     */
    private function save_pdf($pdf, $filename, $issueid) {
        global $DB, $USER;

        if (empty($issueid)) {
            return false;
        }

        if (empty($pdf)) {
            return false;
        }

        $fs = get_file_storage();

        // Prepare file record object
        $fileinfo = self::get_certificate_issue_fileinfo($USER->id, $issueid, $this->context->id);
        $fileinfo['filename'] = $filename;

        // Check for file first
        if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
            $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'));
        }
        return true;
    }

    /**
     * Sends the student their issued certificate from moddata as an email
     * attachment.
     *
     * @param stdClass $course
     * @param stdClass $certificate
     * @param stdClass $certrecord
     * @param stdClass $context
     */
    private function send_certificade_email($issuecert) {
        global $USER;

        $info = new stdClass;
        $info->username = $issuecert->username;
        $info->certificate = format_string($this->name, true);
        $info->course = $issuecert->coursename;

        $subject = $info->course . ': ' . $info->certificate;
        $message = get_string('emailstudenttext', 'simplecertificate', $info) . "\n";

        // Make the HTML version more XHTML happy  (&amp;)
        $messagehtml = text_to_html($message);

        $filename = clean_filename($this->name . '.pdf');

        if (has_capability('mod/simplecertificate:manage', $this->context))
            $this->save_pdf($this->create_pdf($issuecert), $filename, $issuecert->id);

        // Get generated certificate file
        $fs = get_file_storage();
        $fileinfo = self::get_certificate_issue_fileinfo($USER->id, $issuecert->id, $this->context->id);
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $filename);


        if ($file) { //put in a tmp dir, for e-mail attachament
            $temp_manager = $this->move_temp_dir($file);
        } else {
            print_error(get_string('filenotfound', 'simplecertificate', $filename));
        }

        $attachment = $temp_manager->relativefilepath;
        $attachname = $filename;
        $ret = email_to_user($USER, format_string($this->emailfrom, true), $subject, $message, $messagehtml, $attachment, $attachname);

        @remove_dir($temp_manager->path);

        if (has_capability('mod/simplecertificate:manage', $this->context))
            $file->delete();

        return $ret;
    }

    /**
     * Get the time the user has spent in the course
     *
     * @param int $courseid
     * @return int the total time spent in seconds
     */
    public function get_course_time() {
        global $CFG, $USER;

        set_time_limit(0);

        $totaltime = 0;
        $sql = "l.course = :courseid AND l.userid = :userid";
        if ($logs = get_logs($sql, array('courseid' => $this->course, 'userid' => $USER->id), 'l.time ASC', '', '', $totalcount)) {
            foreach ($logs as $log) {
                if (!isset($login)) {
                    // For the first time $login is not set so the first log is also the first login
                    $login = $log->time;
                    $lasthit = $log->time;
                    $totaltime = 0;
                }
                $delay = $log->time - $lasthit;
                if ($delay > ($CFG->sessiontimeout * 60)) {
                    // The difference between the last log and the current log is more than
                    // the timeout Register session value so that we have found a session!
                    $login = $log->time;
                } else {
                    $totaltime += $delay;
                }
                // Now the actual log became the previous log for the next cycle
                $lasthit = $log->time;
            }
            return $totaltime;
        }
        return 0;
    }

    public function output_pdf($issuecert) {
        $pdf = $this->create_pdf($issuecert);
        $filename = clean_filename($this->name . '.pdf');

        if ($this->savecert == 1) {
            // PDF contents are now in $file_contents as a string
            $this->save_pdf($pdf, $filename, $issuecert->id);
            //        $file_contents = $pdf->Output('', 'S');
            //        certificate_save_pdf($file_contents, $certrecord->id, $filename, $context->id);
        }

        switch ($this->delivery) {
            case self::OUTPUT_OPEN_IN_BROWSER :
                $pdf->Output($filename, 'I'); // open in browser
                break;
            case self::OUTPUT_FORCE_DOWNLOAD :
                $pdf->Output($filename, 'D');
                break;
            case self::OUTPUT_SEND_EMAIL :
                $this->send_certificade_email($issuecert);
                $pdf->Output($filename, 'I'); // open in browser
                $pdf->Output('', 'S'); // send
                break;
        }
    }

    private function get_certificate_text($certissue) {
        global $USER, $DB;

        $a = new stdClass;
        $a->username = fullname($USER);
        $a->coursename = format_string($this->coursename, true);
        $a->grade = $this->get_grade();
        $a->date = $this->get_date($certissue);
        $a->outcome = $this->get_outcome();

        if (!empty($this->coursehours))
            $a->hours = format_string($this->coursehours . ' ' . get_string('hours', 'simplecertificate'), true);
        else
            $a->hours = '';

        if ($teachers = $this->get_teachers()) {
            $t = array();
            foreach ($teachers as $teacher) {
                $t[] = fullname($teacher);
            }
            $a->teachers = implode("<br>", $t);
        } else {
            $a->teachers = '';
        }

        $string = $this->certificatetext;
        $a = (array) $a;
        $search = array();
        $replace = array();
        foreach ($a as $key => $value) {
            $search[] = '{' . strtoupper($key) . '}';
            $replace[] = (string) $value;
        }
        if ($search) {
            $string = str_replace($search, $replace, $string);
        }

        return $string;
    }

    /**
     * Returns the date to display for the certificate.
     *
     * @param stdClass $certificate
     * @param stdClass $certrecord
     * @param stdClass $course
     * @param int $userid
     * @return string the date
     */
    private function get_date($certissue, $userid = null) {
        global $DB, $USER;

        if ($this->certdate <= 0) {
            return '';
        }

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Set certificate date to current time, can be overwritten later
        $date = $certissue->timecreated;

        if ($this->certdate == '2') {
            // Get the enrolment end date
            $sql = "SELECT MAX(c.timecompleted) as timecompleted
                    FROM {course_completions} c
                    WHERE c.userid = :userid
                    AND c.course = :courseid";
            if ($timecompleted = $DB->get_record_sql($sql, array('userid' => $userid, 'courseid' => $this->course))) {
                if (!empty($timecompleted->timecompleted)) {
                    $date = $timecompleted->timecompleted;
                }
            }
        } else if ($this->certdate > 2) {
            if ($modinfo = $this->get_mod_grade($this->certdate, $userid)) {
                $date = $modinfo->dategraded;
            }
        }

        switch ($this->certdatefmt) {
            case 1:
                return str_replace(' 0', ' ', strftime('%B %d, %Y', $date));
                break;
            case 2:
                return date('F jS, Y', $date);
                break;
            case 3:
                return str_replace(' 0', '', strftime('%d %B %Y', $date));
                break;
            case 4:
                return strftime('%B %Y', $date);
                break;
            case 5:
                return str_replace(' 0', '', strftime('%d ' . get_string('of', 'simplecertificate') . ' %B ' . get_string('of', 'simplecertificate') . ' %Y', $date));
                return strftime('', $date);
                break;
            case 6:
                return userdate($date, get_string('strftimedate', 'langconfig'));
                break;
        }
        return '';
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     *
     * @return array
     */
    private function get_outcomes() {
        global $COURSE, $DB;

        // get all outcomes in course
        $grade_seq = new grade_tree($COURSE->id, false, true, '', false);
        if ($grade_items = $grade_seq->items) {
            // list of item for menu
            $printoutcome = array();
            foreach ($grade_items as $grade_item) {
                if (isset($grade_item->outcomeid)) {
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
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    private function get_outcome() {
        global $USER, $DB;

        if ($this->outcome > 0) {
            if ($grade_item = new grade_item(array('id' => $this->outcome))) {
                $outcomeinfo = new stdClass;
                $outcomeinfo->name = $grade_item->get_name();
                $outcome = new grade_grade(array('itemid' => $grade_item->id, 'userid' => $USER->id));
                $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $grade_item, true, GRADE_DISPLAY_TYPE_REAL);

                return $outcomeinfo->name . ': ' . $outcomeinfo->grade;
            }
        }

        return '';
    }

    private function move_temp_dir($file) {
        global $CFG;

        $dir = $CFG->tempdir;
        $prefix = self::CERTIFICATE_COMPONENT_NAME;

        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }

        do {
            $path = $dir . $prefix . mt_rand(0, 9999999);
        } while (file_exists($path));

        check_dir_exists($path);

        $fullfilepath = $path . '/' . $file->get_filename();
        $file->copy_content_to($fullfilepath);

        $obj = new stdClass();
        $obj->path = $path;
        $obj->absolutefilepath = $fullfilepath;
        $obj->relativefilepath = str_replace($CFG->dataroot . '/', "", $fullfilepath);

        if (strpos($obj->relativefilepath, '/', 1) === 0)
            $obj->relativefilepath = substr($obj->relativefilepath, 1);

        return $obj;
    }

}

