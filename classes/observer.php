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

namespace mod_simplecertificate;

/**
 * Event observers
 *
 * @package   mod_simplecertificate
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Triggered when user completes a course.
     *
     * @param \core\event\course_completed $event
     */
    public static function sendemails(\core\event\course_completed $event) {
        global $DB, $CFG;
        require_once ($CFG->dirroot . '/mod/simplecertificate/locallib.php');
        $records = $DB->get_records('simplecertificate', ['delivery' => 4, 'course' => $event->courseid]);
        if (count($records) > 0) {
            foreach ($records as $rec) {
                $cm = get_coursemodule_from_instance( 'simplecertificate', $rec->id, $event->courseid );
                $context = \context_module::instance($cm->id);
                $course = $DB->get_record('course', ['id' => $cm->course]);
                $user = $DB->get_record('user', ['id' => $event->relateduserid]);
                $simplecertificate = new \simplecertificate($context, $cm, $course);
                $issuecert = $simplecertificate->get_issue($user);
                if ($simplecertificate->get_issue_file($issuecert)) {
                    $simplecertificate->send_certificade_email($issuecert);
                }
            }
        }
    }

}
