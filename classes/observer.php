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

use completion_info;

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

    /**
     * Callback function triggered when an activity is completed.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function activity_completed(\core\event\course_module_completion_updated $event) {
        global $DB;
        
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        

        // Check if all activities are completed.
        if (self::are_all_activities_completed($courseid, $userid)) {
            // Generate the certificate.
            self::generate_certificate($courseid, $userid);

            // Optionally mark the course as complete.
            self::mark_course_complete($courseid, $userid);
        }
    }

    /**
     * Check if all activities in the course are completed.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user.
     * @return bool True if all activities are completed, false otherwise.
     */
    private static function are_all_activities_completed($courseid, $userid) {
        global $DB;
        $completion = new completion_info(get_course($courseid));
        
        $activities = $completion->get_activities();
        
        foreach ($activities as $activity) {
            // Get the module name (e.g., 'quiz', 'forum', 'simplecertificate')
            $modname = $DB->get_field('modules', 'name', ['id' => $activity->module]);

            // Skip the Simple Certificate module
            if ($modname === 'simplecertificate') {
                continue;
            }

            // Check completion status of the activity
            $completiondata = $completion->get_data($activity, true, $userid);
            
            // Check if the activity is not complete (0 or 3) 
            // or if completionstate is not 1 or 2
            if (!in_array($completiondata->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate the certificate for the user.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user.
     */
    private static function generate_certificate($courseid, $userid) {
        global $DB, $CFG;
        require_once ($CFG->dirroot . '/mod/simplecertificate/locallib.php');

        $instanceid = $DB->get_field('simplecertificate', 'id', ['course' => $courseid]);
        // Get the course module for the Simple Certificate.
        $cm = get_coursemodule_from_instance('simplecertificate', $instanceid, $courseid);
         if (!$cm) {
             return;
         }

         $context = \context_module::instance($cm->id);

        // Check if the certificate already exists for the user.
        if (!$DB->record_exists('simplecertificate_issues', ['userid' => $userid, 'certificateid' => $cm->instance])) {

            $course = $DB->get_record('course', ['id' => $cm->course]);
            $user = $DB->get_record('user', ['id' => $userid]);
            $simplecertificate = new \simplecertificate($context, $cm, $course);
            $issuecert = $simplecertificate->get_issue($user);
            // Optionally notify the user or perform other actions.
        }
    }

    /**
     * Mark the course as complete for the user.
     *
     * @param int $courseid The ID of the course.
     * @param int $userid The ID of the user.
     */
    private static function mark_course_complete($courseid, $userid) {
        global $DB;
    
        $completion = new \completion_info(get_course($courseid));
    
        // Ensure the course has completion enabled
        if (!$completion->is_enabled()) {
            return;
        }
    
        // Check if the course is already marked as complete
        $coursecompletion = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid,
        ]);
    
        if ($coursecompletion && $coursecompletion->timecompleted) {
            return; // Course already completed, no need to proceed.
        }
    
        // Check if all criteria are complete
        $criteria = $completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
        $allcompleted = true;
    
        foreach ($criteria as $criterion) {
            $completiondata = $criterion->get_completion_state($userid);
            if ($completiondata != COMPLETION_COMPLETE && $completiondata != COMPLETION_COMPLETE_PASS) {
                $allcompleted = false;
                break;
            }
        }
    
        // If all criteria are completed, mark the course as complete
        if ($allcompleted) {
            if (!$coursecompletion) {
                // Create a new course completion record if it doesn't exist
                $coursecompletion = (object)[
                    'course' => $courseid,
                    'userid' => $userid,
                    'timecompleted' => time(),
                    'status' => COMPLETION_COMPLETE,
                ];
                $DB->insert_record('course_completions', $coursecompletion);
            } else {
                // Update the existing course completion record
                $coursecompletion->timecompleted = time();
                $coursecompletion->status = COMPLETION_COMPLETE;
                $DB->update_record('course_completions', $coursecompletion);
            }
    
            // Trigger course completion event, ensure 'relateduserid' is passed in 'other'
            $event = \core\event\course_completed::create([
                'objectid' => $courseid,
                'context' => \context_course::instance($courseid),
                'relateduserid' => $userid, // This is crucial to set
                'other' => [
                    'relateduserid' => $userid // Add 'relateduserid' to 'other'
                ]
            ]);
            $event->trigger();
        }
    }

}
