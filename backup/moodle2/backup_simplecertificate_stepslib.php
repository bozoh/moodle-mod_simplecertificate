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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_certificate_activity_task
 */

/**
 * Define the complete certificate structure for backup, with file and id annotations
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");

class backup_simplecertificate_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $CFG;

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $certificate = new backup_nested_element('simplecertificate', array('id'), array(
                'name', 'intro', 'introformat', 'timemodified', 'width', 'height', 'certificateimage',
                'certificatetext', 'certificatetextformat', 'certificatetextx', 'certificatetexty',
                'coursename', 'coursehours', 'outcome', 'certdate', 'certdatefmt', 'certgrade',
                'gradefmt', 'emailfrom', 'emailothers', 'emailteachers', 'reportcert', 'delivery',
                'requiredtime', 'printqrcode', 'qrcodefirstpage', 'codex', 'codey', 'enablesecondpage',
                'secondpagex', 'secondpagey', 'secondpagetext', 'secondpagetextformat', 'secondimage', 'timestartdatefmt'));

        $issues = new backup_nested_element('issues');

        $issue = new backup_nested_element('issue', array('id'),
                        array('userid', 'certificatename', 'timecreated', 'code', 'timedeleted'));

        // Build the tree.
        $certificate->add_child($issues);
        $issues->add_child($issue);

        // Define sources.
        $certificate->set_source_table('simplecertificate', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $issue->set_source_table('simplecertificate_issues', array('certificateid' => backup::VAR_PARENTID));
        }

        // Annotate the user id's where required.
        $certificate->annotate_ids('outcome', 'outcome');
        $certificate->annotate_ids('certdate', 'certdate');
        $certificate->annotate_ids('certgrade', 'certgrade');
        $issue->annotate_ids('user', 'userid');

        // Define file annotations.
        $certificate->annotate_files(simplecertificate::CERTIFICATE_COMPONENT_NAME,
                        simplecertificate::CERTIFICATE_IMAGE_FILE_AREA, null);
        $issue->annotate_files(simplecertificate::CERTIFICATE_COMPONENT_NAME,
                        simplecertificate::CERTIFICATE_ISSUES_FILE_AREA, 'id');

        // Return the root element (certificate), wrapped into standard activity structure.
        return $this->prepare_activity_structure($certificate);
    }
}
