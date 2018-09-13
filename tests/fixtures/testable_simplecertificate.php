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
 * The testable assign class.
 *
 * @package   mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

/**
 * Test subclass that makes all the protected methods we want to test public.
 */
/**
 * Test subclass that makes all the protected methods we want to test public.
 */
class testable_simplecertificate extends simplecertificate {

    const PLUGIN_VERSION = '2.2.2';

    /**
     * Overwrites parents to format $formdata
     * @see simplecertificate::update_instance()
     */
    public function update_instance(stdClass $instance) {
        global $CFG, $USER;

        $usercontext = context_user::instance($USER->id);

        // Draft fileinfo.
        $fileinfo = array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'filepath' => '/'
        );

        $formdata = clone $instance;
        unset($formdata->certificatetext);
        unset($formdata->certificatetextformat);

        $formdata->certificatetext['text'] = $instance->certificatetext;
        $formdata->certificatetext['format'] = $instance->certificatetextformat;

        if (!empty($instance->secondpagetext)) {
            unset($formdata->secondpagetext);
            unset($formdata->secondpagetextformat);
            $formdata->secondpagetext['text'] = $instance->secondpagetext;
            $formdata->secondpagetext['format'] = $instance->secondpagetextformat;
        }

        $fs = get_file_storage();
        if (!empty($instance->certificateimage)) {
            $imagefileinfo = self::get_certificate_image_fileinfo($this->get_context()->id);
            $imagefile = $fs->get_file($imagefileinfo['contextid'], $imagefileinfo['component'], $imagefileinfo['filearea'],
                                    $imagefileinfo['itemid'], $imagefileinfo['filepath'], $instance->certificateimage);

            $fileinfo['itemid'] = rand(1, 10);
            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
            $fs->create_file_from_storedfile($fileinfo, $imagefile);

            $formdata->certificateimage = $fileinfo['itemid'];
            $imagefileinfo = null;
        }

        if (!empty($instance->secondimage)) {
            $imagefileinfo = self::get_certificate_secondimage_fileinfo($this->get_context()->id);
            $imagefile = $fs->get_file($imagefileinfo['contextid'], $imagefileinfo['component'], $imagefileinfo['filearea'],
                                    $imagefileinfo['itemid'], $imagefileinfo['filepath'], $instance->secondimage);

            $fileinfo['itemid'] = rand(11, 21);
            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
            $fs->create_file_from_storedfile($fileinfo, $imagefile);

            $formdata->secondimage = $fileinfo['itemid'];
            $imagefileinfo = null;
        }

        parent::update_instance($formdata);
    }

    // For mocking.
    public function check_user_can_access_certificate_instance($userid) {
         return $this->testable_check_user_can_access_certificate_instance($userid);
    }

    public function testable_check_user_can_access_certificate_instance($userid) {
        return  parent::check_user_can_access_certificate_instance($userid);
    }


    /**
     * Prepare to print an activity grade.
     *
     * @param int $moduleid
     * @param int $userid
     * @return stdClass bool the mod object if it exists, false otherwise
     */
    public function testable_get_mod_grade($moduleid, $userid) {
        return parent::get_mod_grade($moduleid, $userid);
    }

    /**
     * Returns a list of teachers by group
     * for sending email alerts to teachers
     *
     * @return array the teacher array
     */
    public function testable_get_teachers() {
        return parent::get_teachers();
    }

    public function testable_create_pdf($issuecert, $pdf = null, $isbulk = false) {
        return parent::create_pdf($issuecert, $pdf, $isbulk);
    }

    /**
     * This function returns success or failure of file save
     *
     * @param string $pdf is the string contents of the pdf
     * @param stdClass $issuecert the certificate issue record
     * @return mixed return string with filename if successful, null otherwise
     */
    public function testable_save_pdf($issuecert) {
        return parent::save_pdf($issuecert);
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
    public function testable_send_certificade_email($issuecert) {
        return parent::send_certificade_email($issuecert);
    }

    public function testable_get_issue_file ($issuecert) {
        return parent::get_issue_file($issuecert);
    }

    public function testable_get_certificate_text($issuecert, $certtext = null) {
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
    public function testable_get_date($certissue, $userid = null) {
        return parent::get_date($certissue, $userid);
    }

    /**
     * Get the course outcomes for for mod_form print outcome.
     *
     * @return array
     */
    public function testable_get_outcomes() {
        return parent::get_outcomes();
    }

    /**
     * Returns the outcome to display on the certificate
     *
     * @return string the outcome
     */
    public function testable_get_outcome($userid) {
        return parent::get_outcome($userid);
    }


    /**
     * Verify if user meet issue conditions
     *
     * @param int $userid User id
     * @return string null if user meet issued conditions, or an text with erro
     */
    public function testable_can_issue($user = null, $chkcompletation = true) {
        return parent::can_issue($user, $chkcompletation);
    }

    /**
     *
     * @param unknown $issuecertid
     * @param string $user
     * @return true if exist
     *
     */
    public function testable_issue_file_exists($issuecert) {
        if (!parent::issue_file_exists($issuecert)) {
            debugging('Issued certificate pathnamehash='.$issuecert->pathnamehash);
            return false;
        }
        return true;
    }

    public function testable_get_issued_certificate_users ($sort="ci.timecreated ASC", $groupmode=0, $page = 0,
                    $perpage = self::SIMPLECERT_MAX_PER_PAGE) {

        return parent::get_issued_certificate_users($sort, $groupmode, $page, $perpage);
    }

    public function testable_get_textmark_plugin($type) {
        return parent::get_textmark_plugin($type);
    }

}