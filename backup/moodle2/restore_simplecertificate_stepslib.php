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
 * Define all the restore steps that will be used by the restore_simplecertificate_activity_task
 */

/**
 * Structure step to restore one simplecertificate activity
 */
class restore_simplecertificate_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('simplecertificate', '/activity/simplecertificate');

        if ($userinfo) {
            $paths[] = new restore_path_element('simplecertificate_issue', '/activity/simplecertificate/issues/issue');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_simplecertificate($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the simplecertificate record
        $newitemid = $DB->insert_record('simplecertificate', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_simplecertificate_issue($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->certificateid = $this->get_new_parentid('simplecertificate');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('simplecertificate_issues', $data);
        $this->set_mapping('simplecertificate_issue', $oldid, $newitemid);
    }

    protected function after_execute() {
        global $CFG;
        require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");

        // Add simplecertificate related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_simplecertificate', simplecertificate::CERTIFICATE_IMAGE_FILE_AREA, 0);
        $this->add_related_files('mod_simplecertificate', simplecertificate::CERTIFICATE_ISSUES_FILE_AREA, 'simplecertificate_issue');
    }
}
