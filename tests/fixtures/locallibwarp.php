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
 * Simple Certificate module class warper
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');


class simplecertificateWarperClass extends simplecertificate {

    public $orientation = '';
    public $cm;
    const PLUGIN_VERSION = '2.2.0-alpha';

  
	/**
	 * Prepare to print an activity grade.
	 *
	 * @param int $moduleid        	
	 * @param int $userid        	
	 * @return stdClass bool the mod object if it exists, false otherwise
	 */
	public function get_mod_grade($moduleid, $userid) {
		return parent::get_mod_grade($moduleid, $userid);
	}
	
	
	 /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    public function get_teachers() {
        return parent::get_teachers();
    }
    
    public function create_pdf($issuecert, $pdf = null, $isbulk = false) {
    	return parent::create_pdf($issuecert, $pdf, $isbulk);
    }

    /**
     * This function returns success or failure of file save
     *
     * @param string $pdf is the string contents of the pdf
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return string with filename if successful, null otherwise
     */
    public function save_pdf($pdf, $issuecert) {
		return parent::save_pdf($pdf, $issuecert);
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
    public function send_certificade_email($issuecert) {
    	return parent::send_certificade_email($issuecert);
    }

    public function get_issue_file ($issuecert) {
    	return parent::get_issue_file($issuecert);
    }
    
    public function get_certificate_text($issuecert, $certtext = null) {
        return parent::get_certificate_text($issuecert, $certtext);
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
    public function get_date($certissue, $userid = null) {
        return parent::get_date($certissue, $userid);
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     *
     * @return array
     */
    public function get_outcomes() {
    	return parent::get_outcomes();
    }

    /**
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    public function get_outcome($userid) {
    	return parent::get_outcome($userid);
    }

        
    /**
     * Verify if user meet issue conditions
     * 
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    public function can_issue($user = null, $chkcompletation = true) {
    	return parent::can_issue($user = null, $chkcompletation = true);
    }
    
    /**
     * 
     * @param unknown $issuecertid
     * @param string $user
     * @return true if exist 
     * 
     */
    public function issue_file_exists($issuecert) {
    	return parent::issue_file_exists($issuecert);
    }

   	public function get_issued_certificate_users ($sort="ci.timecreated ASC", $groupmode=0, $page = 0, $perpage = self::SIMPLECERT_MAX_PER_PAGE) {
   		return parent::get_issued_certificate_users($sort, $groupmode, $page, $perpage);
	}
}


