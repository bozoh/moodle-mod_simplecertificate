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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplecertificate/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');



class simplecertificate {

    const CERTIFICATE_IMAGE_FILE_AREA = 'image';
    const CERTIFICATE_ISSUES_FILE_AREA = 'issues';
    const CERTIFICATE_COMPONENT_NAME = 'mod_simplecertificate';
    const OUTPUT_OPEN_IN_BROWSER = 0;
    const OUTPUT_FORCE_DOWNLOAD = 1;
    const OUTPUT_SEND_EMAIL = 2;
    
    //Date Options Const
    const CERT_ISSUE_DATE = -1;
    const COURSE_COMPLETATION_DATE = -2;
    
    //Grade Option Const
    const NO_GRADE = 0;
    const COURSE_GRADE = -1;
    
    //View const
    const  DEFAULT_VIEW = 0;
    const  ISSUED_CERTIFCADES_VIEW = 1;
    const  BULK_ISSUE_CERTIFCADES_VIEW = 2;
    
    //pagination
    const SIMPLECERT_MAX_PER_PAGE = 200;

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
    public $printqrcode;
    public $qrcodefirstpage;
    public $enablesecondpage;
    public $secondpagex;
    public $secondpagey;
    public $secondpagetext;
    public $secondpagetextformat;
    public $secondimage;
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
            $this->context = context_module::instance($this->coursemodule);
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
                'itemid' => 1, // usually = ID of row in table
                'filepath' => '/'           // any path beginning and ending in /
        );
        return $fileinfo;
    }

    public static function get_certificate_secondimage_fileinfo($context) {
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }

        $fileinfo = array(
                'contextid' => $contextid, // ID of context
                'component' => self::CERTIFICATE_COMPONENT_NAME, // usually = table name
                'filearea' => self::CERTIFICATE_IMAGE_FILE_AREA, // usually = table name
                'itemid' => 2, // usually = ID of row in table
                'filepath' => '/'           // any path beginning and ending in /
        );
        return $fileinfo;
    }


    public static function get_certificate_issue_fileinfo($issuecert, $context) {
		global $DB;
    		
        if (is_object($context)) {
            $contextid = $context->id;
        } else {
            $contextid = $context;
        }
        
        if ($user=$DB->get_record("user", array('id'=>$issuecert->userid))) {
        	$filename = str_replace(' ', '_', clean_filename($issuecert->certificatename .' '. fullname($user) .' '. $issuecert->id . '.pdf'));
        } else {
        	$filename = str_replace(' ', '_', clean_filename($issuecert->certificatename . ' '. $issuecert->id . '.pdf'));
        }

        $fileinfo = array(
                'contextid' => $contextid, // ID of context
                'component' => self::CERTIFICATE_COMPONENT_NAME, // usually = table name
                'filearea' => self::CERTIFICATE_ISSUES_FILE_AREA, // usually = table name
                'itemid' => $issuecert->id, // usually = ID of row in table
                'filepath' => '/', // any path beginning and ending in /
                'mimetype' => 'application/pdf', // any filename
                'userid' => $issuecert->userid, 
        		'filename' => $filename
        );

        return $fileinfo;
    }

    /**
     * Inserts preliminary user data when a certificate is viewed.
     * Prevents form from issuing a certificate upon browser refresh.
     *
     * @param stdClass $user
     * @return stdClass the newly created certificate issue
     */
    function get_issue($user = null) {
        global $DB, $USER;
        
        if (!isset($user)) {
        	$user = $USER;
        }
        // Check if there is an issue already, should only ever be one, timedeleted must be null
        if (!$issuecert = $DB->get_record('simplecertificate_issues', array('userid' => $user->id, 'certificateid' => $this->id, 'timedeleted' => null))) {
            // Create new certificate issue record
            $issuecert = new stdClass();
            $issuecert->certificateid = $this->id;
            $issuecert->userid = $user->id;
            $formated_certificatename = str_replace('-', '_',$this->name);
            $formated_coursename = str_replace('-', '_',$this->coursename);
            $issuecert->certificatename = format_string($formated_coursename.'-'.$formated_certificatename, true);
            $issuecert->timecreated = time();
            $issuecert->code = $this->get_issue_uuid();

            if (!has_capability('mod/simplecertificate:manage', $this->context, $user)) {
                $issuecert->id = $DB->insert_record('simplecertificate_issues', $issuecert);
            } else {
                $issuecert->id = rand(0, 4);
            }

            // Email to the teachers and anyone else
            if ($this->emailteachers != 0)
                $this->send_alert_email_teachers();

            if (!empty($this->emailothers))
                $this->send_alert_email_others();
        }
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

        //If certgrade = 0 return nothing
        if (empty($this->certgrade)) //No grade
            return '';

        if (empty($userid)) {
            $userid = $USER->id;
        }

        switch ($this->certgrade) {
            case $this::COURSE_GRADE :  //Course grade
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
	 * @return stdClass bool the mod object if it exists, false otherwise
	 */
	private function get_mod_grade($moduleid, $userid) {
		global $DB;
		
		$cm = $DB->get_record ('course_modules', array('id' => $moduleid));
		$module = $DB->get_record ('modules', array('id' => $cm->module));
		
		if ($grade_item = grade_get_grades ( $this->course, 'mod', $module->name, $cm->instance, $userid )) {
			$item = new grade_item ();
			$itemproperties = reset ( $grade_item->items );
			foreach ( $itemproperties as $key => $value ) {
				$item->$key = $value;
			}
			$modinfo = new stdClass ();
			$modinfo->name = utf8_decode ( $DB->get_field ( $module->name, 'name', array (
					'id' => $cm->instance 
			) ) );
			$grade = $item->grades[$userid]->grade;
			$item->gradetype = GRADE_TYPE_VALUE;
			$item->courseid = $this->course;
			
			$modinfo->points = grade_format_gradevalue ( $grade, $item, true, GRADE_DISPLAY_TYPE_REAL, $decimals = 2 );
			$modinfo->percentage = grade_format_gradevalue ( $grade, $item, true, GRADE_DISPLAY_TYPE_PERCENTAGE, $decimals = 2 );
			$modinfo->letter = grade_format_gradevalue ( $grade, $item, true, GRADE_DISPLAY_TYPE_LETTER, $decimals = 0 );
			
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
		require_once (dirname ( __FILE__ ) . '/lib.uuid.php');
		$UUID = UUID::mint ( UUID::VERSION_1, self::CERTIFICATE_COMPONENT_NAME );
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
        } else {
        	list($coursecontactroles, $trash) = get_roles_with_cap_in_context($this->context, 'mod/simplecertificate:manage');
        }
        foreach ($coursecontactroles as $roleid) {
			$role = $DB->get_record('role', array('id' => $roleid));
			$roleid = (int) $roleid;
			if ($users = get_role_users($roleid, $this->context, true)) {
				foreach ($users as $teacher) {
					if ($teacher->id == $USER->id) {
						continue; // do not send self
					}
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
     * Alerts teachers by email of received certificates. First checks
     * whether the option to email teachers is set for this certificate.
     *
     */
    private function send_alert_email_teachers() {
        if ($teachers = $this->get_teachers()) {
            $emailteachers = array();
            foreach ($teachers as $teacher) {
                $emailteachers[] = $teacher->user->email;
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

        if (!empty($emails)) {
            $strawarded = get_string('awarded', 'simplecertificate');
            $url=new moodle_url($CFG->wwwroot . '/mod/simplecertificate/view.php',array('id'=>$this->cm->id,'tab' => self::ISSUED_CERTIFCADES_VIEW));

            foreach ($emails as $email) {
                $email = trim($email);
                if (validate_email($email)) {
                	$destination = new stdClass;
                    $destination->email = $email;
                    $destination->id = rand(-10, -1);

                    $info = new stdClass;
                    $info->student = fullname($USER);
                    $info->course = format_string($this->coursename, true);
                    $info->certificate = format_string($this->name, true);
                    $info->url = $url->out();
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

    /**
     * Create TCPDF object using parameters
     * @return TCPDF
     */
    private function create_pdf_object() {
    	$pdf = new pdf($this->orientation, 'mm', array($this->width, $this->height), true, 'UTF-8');
    	$pdf->SetTitle($this->name);
    	$pdf->SetSubject($this->name . ' - ' . $this->coursename);
    	$pdf->SetKeywords(get_string('keywords', 'simplecertificate') . ',' . $this->coursename);
    	$pdf->setPrintHeader(false);
    	$pdf->setPrintFooter(false);
    	$pdf->SetAutoPageBreak(false, 0);
    	$pdf->setFontSubsetting(true);
    	$pdf->SetMargins(0,0,0,true);
    	    	
    	return $pdf;
    }
    
    /**
     * 
     * @param stdClass $issuecert
     * @param string $pdf
     * @return mixed  TCPDF object or error 
     */
    private function create_pdf($issuecert, $pdf = null) {
        global $DB, $CFG;
        
        if (empty($pdf)) {
        	$pdf = $this->create_pdf_object();
        }
		
        $pdf->AddPage();
        
        //Getting certificare image
        $fs = get_file_storage();

        // Get first page image file
        if (!empty($this->certificateimage)) {
            // Prepare file record object
            $fileinfo = self::get_certificate_image_fileinfo($this->context->id);
            $firstpageimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $this->certificateimage);
            // Read contents
            if ($firstpageimagefile) {
                $temp_filename = $firstpageimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'first_image_');
                $pdf->Image($temp_filename, 0, 0, $this->width, $this->height);
                @unlink($temp_filename);
            } else {
                print_error(get_string('filenotfound', 'simplecertificate', $this->certificateimage));
            }
        }
        
        //Writing text
        $pdf->SetXY($this->certificatetextx, $this->certificatetexty);
        $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->certificatetext), 0, 0, 0, true, 'C');
        
        //Print QR code in first page (if enable)
        if ($this->printqrcode && $this->qrcodefirstpage) {
        	$this->print_qrcode($pdf, $issuecert->code);
        }
         
        if (!empty($this->enablesecondpage)) {

            $pdf->AddPage();

            if (!empty($this->secondimage)) {
                // Prepare file record object
                $fileinfo = self::get_certificate_secondimage_fileinfo($this->context->id);
                // Get file
                $secondimagefile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $this->secondimage);

                // Read contents
                if ($secondimagefile) {
                    $temp_filename = $secondimagefile->copy_content_to_temp(self::CERTIFICATE_COMPONENT_NAME, 'second_image_');
                    $pdf->Image($temp_filename, 0, 0, $this->width, $this->height);
                    @unlink($temp_filename);
                } else {
                    print_error(get_string('filenotfound', 'simplecertificate', $this->secondimage));
                }
            }
            if (!empty($this->secondpagetext)) {
                $pdf->SetXY($this->secondpagex, $this->secondpagey);
                $pdf->writeHTMLCell(0, 0, '', '', $this->get_certificate_text($issuecert, $this->secondpagetext), 0, 0, 0, true, 'C');
            }
        }

        if ($this->printqrcode && empty($this->qrcodefirstpage)) {
            //Add certificade code using QRcode, in a new page (to print in the back)
            if (empty($this->enablesecondpage)) {
                //If secondpage is disabled, create one
                $pdf->AddPage();
            }
            $this->print_qrcode($pdf, $issuecert->code);
            
        }

        return $pdf;
    }
    
    private function print_qrcode ($pdf, $code) {
        global $CFG;
        $style = array(
                'border' => 2,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => array(255,255,255), //false
                'module_width' => 1, // width of a single module in points
                'module_height' => 1 // height of a single module in points
        );
        
        $codeurl = "$CFG->wwwroot/mod/simplecertificate/verify.php?code=$code";
        $pdf->write2DBarcode($codeurl, 'QRCODE,H', $this->codex, $this->codey, 50, 50, $style, 'N');
        $pdf->SetXY($this->codex,  $this->codey + 48);
        $pdf->Cell(50,10,$code,0,0,'C',false,'',2);
        
    } 

    /**
     * This function returns success or failure of file save
     *
     * @param string $pdf is the string contents of the pdf
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return string with filename if successful, null otherwise
     */
    private function save_pdf($pdf, $issuecert) {

    	if (empty($pdf)) {
            return false;
        }

        $fileinfo = self::get_certificate_issue_fileinfo($issuecert, $this->context->id);
        
        // Check for file first
        if ($this->issue_file_exists($issuecert)) {
        	return $fileinfo['filename'];
        }
        
        $fs = get_file_storage();

        // Prepare file record object
        $fs->create_file_from_string($fileinfo, $pdf->Output('', 'S'));
        
        return $fileinfo['filename'];
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
        global $DB, $CFG;
        
		if (!$user = $DB->get_record('user', array('id' => $issuecert->userid))) {
			print_error('nousersfound', 'moodle');
		} 
    	
        $info = new stdClass;
        $info->username = format_string(fullname($user), true);
        $info->certificate = format_string($issuecert->certificatename, true);
        $info->course = format_string($this->coursename, true);

        $subject = get_string('emailstudentsubject', 'simplecertificate', $info);
        $message = get_string('emailstudenttext', 'simplecertificate', $info) . "\n";

        // Make the HTML version more XHTML happy  (&amp;)
        $messagehtml = text_to_html($message);
      
        // Get generated certificate file
        if ($file = $this->get_issue_file($issuecert)) { //put in a tmp dir, for e-mail attachament
        	$fullfilepath = $this->create_temp_file($file->get_filename());
        	$file->copy_content_to($fullfilepath);
        	$relativefilepath = str_replace($CFG->dataroot . '/', "", $fullfilepath);
        	
        	if (strpos($relativefilepath, '/', 1) === 0)
        		$relativefilepath = substr($relativefilepath, 1);
        	
        	if (!empty($this->emailfrom)){
        		$from = new stdClass;
        		$from->email = format_string($this->emailfrom, true);
        		$from->maildisplay = true;
        	} else {
        		$from = format_string($this->emailfrom, true);
        	}
        
        	$ret = email_to_user($user, $from, $subject, $message, $messagehtml, $relativefilepath, $file->get_filename());
        	@unlink($attachment);
        	
        	return $ret;
        } else {
        	$fileinfo = self::get_certificate_issue_fileinfo($issuecert, null);
        	print_error(get_string('filenotfound', 'simplecertificate', $fileinfo['filename']));
        }
    }

    private function get_issue_file ($issuecert) {
    	if (!$this->issue_file_exists($issuecert))
    		return false; 
    	
    	$fs = get_file_storage();
    	$fileinfo = self::get_certificate_issue_fileinfo($issuecert, $this->context);
    	return $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    }
    
    /**
     * Get the time the user has spent in the course
     *
     * @param int $userid User ID  (default= $USER->id) 
     * @return int the total time spent in seconds
     */
    public function get_course_time($user = null) {
        global $CFG, $USER;

        if (empty($user)) {
        	$user = $USER;
        }
        set_time_limit(0);

        $totaltime = 0;
        $sql = "l.course = :courseid AND l.userid = :userid";
        if ($logs = get_logs($sql, array('courseid' => $this->course, 'userid' => $user->id), 'l.time ASC', '', '', $totalcount)) {
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
        $filename = $this->save_pdf($pdf, $issuecert);
        
        if(!$file = $this->get_issue_file($issuecert)) {
        	print_error(get_string('filenotfound', 'simplecertificate', $filename));
        }
        
        switch ($this->delivery) {
            case self::OUTPUT_FORCE_DOWNLOAD:
            	send_stored_file($file, 10, 0, true, array('filename'=>$filename)); //force download
            break;
            
            case self::OUTPUT_SEND_EMAIL:
                $this->send_certificade_email($issuecert);
            break;
            
            case self::OUTPUT_OPEN_IN_BROWSER:
            	send_stored_file($file, 10, 0, false); // open in browser
            break;
        }
        
        if (has_capability('mod/simplecertificate:manage', $this->context, $issuecert->userid)) {
        	$file = $this->get_issue_file($issuecert);
        	$file->delete();
        }
        
    }

    private function get_certificate_text($issuecert, $certtext = null) {
        global $DB, $CFG;

        
        if (!$user = get_complete_user_data('id', $issuecert->userid)) {
        	print_error('nousersfound', 'moodle');
        }
        
        if(empty($certtext)) {
        	$certtext = $this->certificatetext;
        }
        
        $a = new stdClass;
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

        if (!empty($user->country)) {
            $a->country =  get_string($user->country, 'countries');
        } else {
            $a->country = '';
        }

        //Formatting URL, if needed
        $url = $user->url;
        if (strpos($url, '://') === false) {
            $url = 'http://'. $url;
        }
        $a->url = $url;

        //Getting user custom profiles fields
        $userprofilefields = $this->get_user_profile_fields($user->id);
        foreach ($userprofilefields as $key => $value) {
            $key = 'profile_'.$key;
            $a->$key=$value;
        }


        $a->coursename = format_string($this->coursename, true);
        $a->grade = $this->get_grade($user->id);
        $a->date = $this->get_date($issuecert,$user->id);
        $a->outcome = $this->get_outcome($user->id);
        $a->certificatecode=$issuecert->code;

        if (!empty($this->coursehours))
            $a->hours = format_string($this->coursehours . ' ' . get_string('hours', 'simplecertificate'), true);
        else
            $a->hours = '';
        
        try {
        	if($course = get_course($this->course)) {
        		require_once($CFG->libdir. '/coursecatlib.php');
 	        	$courseinlist = new course_in_list($course);
    	    	if ($courseinlist->has_course_contacts()) {
        			$t = array();
        			foreach ($courseinlist->get_course_contacts() as $userid => $coursecontact) {
        				$t[] = $coursecontact['rolename'] .': '.$coursecontact['username'];
        			}
        			$a->teachers = implode("<br>", $t);
        		} else {
            		$a->teachers = '';
        		}
        	} else {
        		$a->teachers = '';
        	}
        } catch (Exception $e) {
        	$a->teachers = '';
        }

        $a = (array) $a;
        $search = array();
        $replace = array();
        foreach ($a as $key => $value) {
            $search[] = '{' . strtoupper($key) . '}';
            $replace[] = (string) $value;
        }
        
        if ($search) {
            return str_replace($search, $replace, $certtext);
        }

        return $this->certificatetext;
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

        if (empty($this->certdatefmt)){
            $format = get_string('strftimedate', 'langconfig');
        } else {
            $format = $this->certdatefmt;
        }

        //Certificate Issued date
        if ($this->certdate == $this::CERT_ISSUE_DATE) {
            return userdate($certissue->timecreated, $format);
        }

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Set certificate date to current time, can be overwritten later
        $date = $certissue->timecreated;

        if ($this->certdate == $this::COURSE_COMPLETATION_DATE) {
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
        } else if ($this->certdate > 0) {
            if ($modinfo = $this->get_mod_grade($this->certdate, $userid)) {
                $date = $modinfo->dategraded;
            }
        }

        return userdate($date, $format);
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
    private function get_outcome($userid) {
        global $USER, $DB;

        if (empty($userid)) {
        	$userid = $USER->id;
        }
        if ($this->outcome > 0) {
            if ($grade_item = new grade_item(array('id' => $this->outcome))) {
                $outcomeinfo = new stdClass;
                $outcomeinfo->name = $grade_item->get_name();
                $outcome = new grade_grade(array('itemid' => $grade_item->id, 'userid' => $userid));
                $outcomeinfo->grade = grade_format_gradevalue($outcome->finalgrade, $grade_item, true, GRADE_DISPLAY_TYPE_REAL);

                return $outcomeinfo->name . ': ' . $outcomeinfo->grade;
            }
        }

        return '';
    }

    private function create_temp_file($file) {
    	global $CFG;
    	
        $path = make_temp_directory(self::CERTIFICATE_COMPONENT_NAME);
        return tempnam($path, $file);
    }

    private function get_user_profile_fields($userid) {
        global $CFG, $DB;

        $usercustomfields = new stdClass();
        if ($categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
            foreach ($categories as $category) {
                if ($fields = $DB->get_records('user_info_field', array('categoryid'=>$category->id), 'sortorder ASC')) {
                    foreach ($fields as $field) {
                        require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        if ($formfield->is_visible() and !$formfield->is_empty()) {
                            if ($field->datatype == 'checkbox'){
                                $usercustomfields->{$field->shortname} = ( $formfield->data == 1 ? get_string('yes') : get_string('no'));
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
    private function can_issue($user = null, $chkcompletation = true) {
    	global $DB, $USER, $CFG;

    	if (empty($user)) {
    		$user = $USER;
    	}
    	
		if (has_capability('mod/simplecertificate:manage', $this->context, $user)) {
    		return get_string('cantissue', 'simplecertificate');
    	}
    	
    	if ($chkcompletation) {
    		if ($this->requiredtime) {
    			if ($this->get_course_time($user) < $this->requiredtime) {
    				$a = new stdClass;
    				$a->requiredtime = $this->requiredtime;
    				return get_string('requiredtimenotmet', 'simplecertificate', $a);
    			}
    		}
    	
    		if (completion_info::is_enabled_for_site()) {
    			require_once("{$CFG->libdir}/completionlib.php");
    		
    			if (!$course = $DB->get_record('course', array('id' => $this->course))) {
    				print_error('cannotfindcourse');
    			}
    			$info = new completion_info($course);
    		
    			if ($info->is_enabled($this->cm) && !$info->is_course_complete($user->id)) {
            		return get_string('cantissue', 'simplecertificate');
            	}
        	}

        	if ($CFG->enableavailability) {
        		require_once("{$CFG->libdir}/conditionlib.php");
        		$condition_info = new condition_info($this->cm, CONDITION_MISSING_EVERYTHING);
        		if (!$condition_info->is_available($msg, false, $user->id)) {
        			return $msg;
        		}
        	}
    	}
        return null;
    }
    
    /**
     * 
     * @param unknown $issuecertid
     * @param string $user
     * @return true if exist 
     * 
     */
    private function issue_file_exists($issuecert) {

    	$fs = get_file_storage();
    	
    	// Prepare file record object
    	$fileinfo = self::get_certificate_issue_fileinfo($issuecert, $this->context->id);
    	
    	// Check for file first
    	return $fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']); 
    }

//View methods

    private function show_tabs(moodle_url $url) {
    	global $OUTPUT, $CFG;
    	
    	$tabs [] = new tabobject(self::DEFAULT_VIEW,
    			$url->out(false, array('tab' => self::DEFAULT_VIEW)),
    			get_string('standardview', 'simplecertificate'));
    	
    	$tabs [] = new tabobject(self::ISSUED_CERTIFCADES_VIEW,
    			$url->out(false, array('tab' => self::ISSUED_CERTIFCADES_VIEW)),
    			get_string ('issuedview', 'simplecertificate'));
    	
    	$tabs [] = new tabobject(self::BULK_ISSUE_CERTIFCADES_VIEW,
    			$url->out(false, array('tab' => self::BULK_ISSUE_CERTIFCADES_VIEW)),
    			get_string ('bulkview', 'simplecertificate'));
    	
    	if (!$url->get_param('tab')) {
    		$tab = self::DEFAULT_VIEW;
    	} else {
    		$tab = $url->get_param('tab');
    	}
    	
    	echo $OUTPUT->tabtree($tabs, $tab);
    	
    }
    
    //Default view
    public function view_default(moodle_url $url, $canmanage) {
    	global $OUTPUT, $USER;

    	if (!$url->get_param('action')) {
    		
    		echo $OUTPUT->header();
    		
    		if ($canmanage) {
    			$this->show_tabs($url);
    		}
    	 	
    		// Check if the user can view the certificate
    		if (!$canmanage && $msg = $this->can_issue($USER)) {
    			notice($msg, $url);
    			die;
    		}
    	
    		if (!empty($this->intro)) {
    			echo $OUTPUT->box(format_module_intro('simplecertificate', $this, $this->cm->id), 'generalbox', 'intro');
    		}
    	
    		if ($attempts = $this->get_attempts()) {
    			echo $this->print_attempts($attempts);
    		}
    		
    		if (!$canmanage) {
    			add_to_log($this->course, 'simplecertificate', 'view', $url->out_as_local_url(false), $this->id, $this->cm->id);
    		}
    	
    	
    		if ($this->delivery != 3 || $canmanage) {
    			// Create new certificate record, or return existing record
    		 
    			$certrecord = $this->get_issue($USER);
    			switch ($this->delivery) {
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
    	
    			echo html_writer::tag('p', $str, array(
    					'style' => 'text-align:center'
    			));
    			$linkname = get_string('getcertificate', 'simplecertificate');
    	
    			$link = new moodle_url('/mod/simplecertificate/view.php', array(
    					'id' => $this->cm->id,
    					'action' => 'get'
    			));
    			$button = new single_button ($link, $linkname);
    			$button->add_action(new popup_action('click', $link, 'view' . $this->cm->id, array(
    					'height' => 600,
    					'width' => 800
    			)));
    	
    			echo html_writer::tag ( 'div', $OUTPUT->render ( $button ), array (
    					'style' => 'text-align:center'
    			));
    		}
    		echo $OUTPUT->footer($this->course);
    	} else { // Output to pdf
	 		if ($this->delivery != 3 || $canmanage) {
     			$this->output_pdf($this->get_issue($USER));
 			}
    	}
	}
    
	private function get_issued_certificate_users ($sort="ci.timecreated ASC", $groupmode=0, $page = 0, $perpage = self::SIMPLECERT_MAX_PER_PAGE) {
		global $CFG, $DB;
		 
		// get all users that can manage this certificate to exclude them from the report.
		$certmanagers = get_users_by_capability($this->context, 'mod/simplecertificate:manage', 'u.id');
		$limitsql = '';
		$page = (int) $page;
		$perpage = (int) $perpage;
		 
		// Setup pagination - when both $page and $perpage = 0, get all results
		if ($page || $perpage) {
			if ($page < 0) {
				$page = 0;
			}
			 
			if ($perpage > self::SIMPLECERT_MAX_PER_PAGE) {
				$perpage = self::SIMPLECERT_MAX_PER_PAGE;
			} else {
				$perpage = get_config('simplecertificate', 'perpage');
			}
			$limitsql = " LIMIT $perpage" . " OFFSET " . $page * $perpage ;
		}
		 
		// Get all the users that have certificates issued, should only be one issue per user for a certificate
		$issedusers = $DB->get_records_sql("SELECT u.*, ci.code, ci.timecreated
				FROM {user} u
				INNER JOIN {simplecertificate_issues} ci
				ON u.id = ci.userid
				WHERE u.deleted = 0
				AND ci.certificateid = :certificateid
				AND timedeleted IS NULL
				ORDER BY {$sort} {$limitsql}", array('certificateid' => $this->id));
   
    			// now exclude all the certmanagers.
		foreach ($issedusers as $id => $user) {
			if (isset($certmanagers[$id])) { //exclude certmanagers.
				unset($issedusers[$id]);
			}
		}
		 
		// if groupmembersonly used, remove users who are not in any group
		if (!empty($issedusers) and !empty($CFG->enablegroupings) and $this->cm->groupmembersonly) {
			if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
				$issedusers = array_intersect($issedusers, array_keys($groupingusers));
			}
		}
				 
		if ($groupmode) {
			$currentgroup = groups_get_activity_group($this->cm);
			if ($currentgroup) {
				$groupusers = groups_get_members($currentgroup, 'u.*');
				if (empty($groupusers)) {
					return array();
				}
				foreach($issedusers as $id => $unused) {
					if (!isset($groupusers[$id])) {
						// remove this user as it isn't in the group!
						unset($issedusers[$id]);
					}
				}
			}
		}
		return $issedusers;
	}
	
	public static function print_issue_certificate_file($issuecert, $context = null) {
		global $CFG, $OUTPUT;
	
		$output = '';
		if (!$context) {
			try {
				if ($cm = get_coursemodule_from_instance('simplecertificate', $issuecert->certificateid)) {
					$context = context_module::instance($cm->id);
				}
			} catch (Exception $e) {
				return $output;
			}
		}
		
		$fs = get_file_storage();
		$fileinfo = simplecertificate::get_certificate_issue_fileinfo($issuecert, $context);
		if (!$fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
			return $output;
		}
				
		$link = moodle_url::make_pluginfile_url(
				$fileinfo['contextid'], 
				$fileinfo['component'], 
				$fileinfo['filearea'], 
				$fileinfo['itemid'], 
				$fileinfo['filepath'], 
				$fileinfo['filename'],
				true);
		
		$mimetype = $fileinfo['mimetype'];
		$output = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" height="16" width="16" alt="'.$mimetype.'" />&nbsp;'.
					'<a href="'.$link->out(false).'" target="_blank" >'.s($fileinfo['filename']).'</a>';
	
		$output .= '<br />';
		$output = '<div class="files">'.$output.'</div>';
	
		return $output;
	}
	
     
	//Issued certificates view
    public function view_issued_certificates(moodle_url $url) {
    	global $OUTPUT, $DB, $CFG;
    	
    	// Declare some variables
    	$strcertificates = get_string('modulenameplural', 'simplecertificate');
    	$strcertificate  = get_string('modulename', 'simplecertificate');
    	$strto = get_string('awardedto', 'simplecertificate');
    	$strdate = get_string('receiveddate', 'simplecertificate');
    	$strgrade = get_string('grade','simplecertificate');
    	$strcode = get_string('code', 'simplecertificate');
    	$strreport= get_string('report', 'simplecertificate');
    	$groupmode = groups_get_activity_groupmode($this->cm);
    	$page = $url->get_param('page');
    	$perpage = $url->get_param('perpage');
    	
    	$users = $this->get_issued_certificate_users($DB->sql_fullname(), $groupmode, $page, $perpage);
    	
    	
    	if (!$url->get_param('action')) {
    		echo $OUTPUT->header();
    		$this->show_tabs($url);
    		
    		if ($groupmode) {
    			groups_get_activity_group($this->cm, true);
    		}
    		
    		groups_print_activity_menu($this->cm, $url);

    		if (!$users) {
    			notify(get_string('nocertificatesissued', 'simplecertificate'));
    			echo $OUTPUT->footer($this->course);
    			exit();
    		}
    		
    		$usercount = count($users);
    		 
    		// Create the table for the users
    		$table = new html_table();
    		$table->width = "95%";
    		$table->tablealign = "center";
    		$table->head  = array($strto, $strdate, $strgrade, $strcode);
    		$table->align = array("left", "left", "center", "center");
    		foreach ($users as $user) {
    			$name = $OUTPUT->user_picture($user) . fullname($user);
    			$date = userdate($user->timecreated) . self::print_issue_certificate_file($this->get_issue($user), $this->context);
    			$code = $user->code;
    			$table->data[] = array ($name, $date, $this->get_grade($user->id), $code);
    		}
    		 
    		// Create table to store buttons
    		$tablebutton = new html_table();
    		$tablebutton->attributes['class'] = 'downloadreport';
    		//$btndownloadods = $OUTPUT->single_button(new moodle_url("report.php", array('id'=>$this->cm->id, 'download'=>'ods')), get_string("downloadods"));
    		$btndownloadods = $OUTPUT->single_button($url->out_as_local_url(false, array('action'=>'download', 'type'=>'ods')), get_string("downloadods"));
    		$btndownloadxls = $OUTPUT->single_button($url->out_as_local_url(false, array('action'=>'download', 'type'=>'xls')), get_string("downloadexcel"));
    		$btndownloadtxt = $OUTPUT->single_button($url->out_as_local_url(false, array('action'=>'download', 'type'=>'txt')), get_string("downloadtext"));
    		$tablebutton->data[] = array($btndownloadods, $btndownloadxls, $btndownloadtxt);
    		 
    		
    		
    		//echo $OUTPUT->heading(get_string('modulenameplural', 'simplecertificate'));
    		echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
    		echo '<br />';
    		echo html_writer::table($table);
    		echo html_writer::tag('div', html_writer::table($tablebutton), array('style' => 'margin:auto; width:50%'));
    		
    	} else if ($url->get_param('action') == 'download') {
    		$page = $perpage = 0;
    		$type=$url->get_param('type');
    		// Calculate file name
    		$filename = clean_filename($this->coursename.'-'.strip_tags(format_string($this->name,true)).'.'.strip_tags(format_string($type, true)));
    		
    		switch ($type) {
    		    case 'ods':
    		    	require_once("$CFG->libdir/odslib.class.php");

    		    	// Creating a workbook
    		    	$workbook = new MoodleODSWorkbook("-");
    		    	// Send HTTP headers
    		    	$workbook->send($filename);
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
    		    			if ($usergrps = groups_get_all_groups($this->course, $user->id)) {
    		    				foreach ($usergrps as $ug) {
    		    					$ug2 = $ug2. $ug->name;
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
    		    	require_once("$CFG->libdir/excellib.class.php");
    		    	 
    		    	// Creating a workbook
    		    	$workbook = new MoodleExcelWorkbook("-");
    		    	// Send HTTP headers
    		    	$workbook->send($filename);
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
    		    			if ($usergrps = groups_get_all_groups($this->course, $user->id)) {
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
    		    	header("Content-Disposition: attachment; filename=\"$filename\"");
    		    	header("Expires: 0");
    		    	header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    		    	header("Pragma: public");
    		    	 
    		    	// Print names of all the fields
    		    	echo get_string("fullname"). "\t" . get_string("idnumber") . "\t";
    		    	echo get_string("group"). "\t";
    		    	echo $strdate. "\t";
    		    	echo $strgrade. "\t";
    		    	echo $strcode. "\n";
    		    	 
    		    	// Generate the data for the body of the spreadsheet
    		    	$i=0;
    		    	$row=1;
    		    	if ($users) foreach ($users as $user) {
    		    		echo fullname($user);
    		    		$studentid = " ";
    		    		if (!empty($user->idnumber)) {
    		    			$studentid = $user->idnumber;
    		    		}
    		    		echo "\t" . $studentid . "\t";
    		    		$ug2 = '';
    		    		if ($usergrps = groups_get_all_groups($this->course, $user->id)) {
    		    			foreach ($usergrps as $ug) {
    		    				$ug2 = $ug2. $ug->name;
    		    			}
    		    		}
    		    		echo $ug2 . "\t";
    		    		echo userdate($user->timecreated) . "\t";
    		    		echo $this->get_grade($user->id). "\t";
    		    		echo $user->code . "\n";
    		    		$row++;
    		    	}
    		    break;
    		}
    		exit;
    	}
    	echo $OUTPUT->footer($this->course);
    }
    
    public function view_bulk_certificates(moodle_url $url, array $selectedusers = null){
    	global $OUTPUT, $CFG, $DB;

    	$course_context = context_course::instance($this->course);
    	
    	$page = $url->get_param('page');
    	$perpage = $url->get_param('perpage');
    	$issuelist = $url->get_param('issuelist');
    	$action = $url->get_param('action');
    	$groupid = 0;
    	$groupmode = groups_get_activity_groupmode($this->cm);
    	if ($groupmode) {
    		$groupid = groups_get_activity_group($this->cm, true);
    	}

    	if (!$selectedusers) {
    		$users = get_enrolled_users($course_context, '', $groupid);
    	} else {
    		list($sqluserids, $params) = $DB->get_in_or_equal($selectedusers);
    		$sql = "SELECT * FROM {user} WHERE id $sqluserids";
    		$users = $DB->get_records_sql($sql, $params);
    	}
    	
    	if (!$action) {
    		$usercount = count($users);
    		echo $OUTPUT->header();
    		$this->show_tabs($url);
    		
    		groups_print_activity_menu($this->cm, $url);
    		
    		$select = new single_select($url, 'issuelist', array('completed' => get_string('completedusers','simplecertificate'), 'allusers' => get_string('allusers','simplecertificate')), $issuelist);
    		$select->label = get_string('showusers','simplecertificate');
    		echo $OUTPUT->render($select);
    		echo '<br>';
    		echo '<form id="bulkissue" name="bulkissue" method="post" action="view.php">';
    		
    		echo html_writer::label(get_string('bulkaction','simplecertificate'), 'menutype', true);
    		echo '&nbsp;';
    		echo html_writer::select(array('pdf' => get_string('onepdf','simplecertificate'), 'zip'=> get_string('multipdf','simplecertificate'), 'email'=>get_string('sendtoemail','simplecertificate')),'type','pdf');
    		$table = new html_table();
    		$table->width = "95%";
    		$table->tablealign = "center";
    		//strgrade
    		
    		$table->head  = array(' ', get_string('fullname'), get_string('grade'));
    		$table->align = array("left", "left", "center");
    		$table->size = array ('1%','89%','10%');
    		foreach ($users as $user) {
    			$canissue = $this->can_issue($user, $issuelist != 'allusers');
    			if (empty($canissue)) {
    				$chkbox = html_writer::checkbox('selectedusers[]', $user->id, false); 
    				$name = $OUTPUT->user_picture($user) . fullname($user);
    				$table->data[] = array ($chkbox ,$name, $this->get_grade($user->id));
    			}
    		}


    		$downloadbutton = $OUTPUT->single_button($url->out_as_local_url(false, array('action'=>'download')), get_string('bulkbuttonlabel','simplecertificate'));

    		echo $OUTPUT->paging_bar($usercount, $page, $perpage, $url);
    		echo '<br />';
    		echo html_writer::table($table);
    		echo html_writer::tag('div', $downloadbutton, array('style' => 'text-align: center'));
    		echo '</form>';
    		    		
    	} else if ($action == 'download') {
    		$type = $url->get_param('type');
    		
    		// Calculate file name
    		$filename = str_replace(' ', '_', clean_filename($this->coursename.' '.get_string('modulenameplural','simplecertificate').' '.strip_tags(format_string($this->name,true)).'.'.strip_tags(format_string($type, true))));

    		switch ($type) {
    			//One pdf with all certificates
    		    case 'pdf':
    		    	$pdf = $this->create_pdf_object();
    		    	
    		    	foreach ($users as $user) {
    		    		$canissue = $this->can_issue($user, $issuelist != 'allusers');
    					if (empty($canissue)) {
    		    			$this->create_pdf($this->get_issue($user), $pdf);
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
    		    			if(!$this->issue_file_exists($issuecert)) {
    		    				$this->save_pdf($this->create_pdf($issuecert), $issuecert);
    		    			}
    		    			$fs = get_file_storage();
    		    			 
    		    			$fileinfo = self::get_certificate_issue_fileinfo($issuecert, $this->context);
    		    			$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    		    			$fileforzipname = $file->get_filename();
    		    			$filesforzipping[$fileforzipname] = $file;
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
    		    			if(!$this->issue_file_exists($issuecert)) {
    		    				$this->save_pdf($this->create_pdf($issuecert), $issuecert);
    		    			}
    		    			$this->send_certificade_email($issuecert);
    		    		}
    		    	}
    		    	$url->remove_params('action','type');
    		    	redirect($url,get_string('emailsent','simplecertificate'),5);
    		    break;
    		}
    		exit;
    	}
    	echo $OUTPUT->footer($this->course);
    }
}


