<?php
namespace mod_simplecertificate;
require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
defined('MOODLE_INTERNAL') || die();
/**
 * Simple ertificate
 *
 * @package    mod_simplecertificate
 * @author     Vincent Schneider <vincent.schneider@sudile.com> 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer{
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $USER, $CFG;
        $user = $DB->get_record('user', array('id' => $event->relateduserid));
        // Check we have a course record
        if($simplecertificate_records = $DB->get_records('simplecertificate', array('course' => $event->courseid))) {
            foreach ($simplecertificate_records as $record) {
                if( $record and ($record->notifyoncoursecompletion && $record->notifyoncoursecompletion != 0) ) {
                    //ready for working
                    self::notifyoncoursecompletion($record, $event->courseid, $user);
                }
            }
        }
    }
    private static function notifyoncoursecompletion($record,$courseid,$user){
        GLOBAL $USER,$DB;
        $modid = $DB->get_field_sql("SELECT md.id from {course_modules} md join {modules} m " .
            "ON m.name=:name and m.id=md.module and instance=:id",
            array("name" => "simplecertificate", "id" => $record->id));
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_id( 'simplecertificate', $modid);
        $context = \context_module::instance ($cm->id);
        $simplecertificate = new \simplecertificate($context, $cm, $course);
        $simplecertificate->send_certificade_email($simplecertificate->get_issue($user));


        /*$stdobj = new \stdClass();
        $stdobj->name = $user->firstname . " " . $user->lastname;
        $stdobj->coursename = $course->fullname;
        $stdobj->url = new \moodle_url("/mod/simplecertificate/view.php",array("id"=>$record->id));
        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = $USER;
        $message->userto = $user;
        $message->subject = get_string('emailcoursecompletionsubject','mod_simplecertificate');
        $message->fullmessage = get_string('emailcoursecompletioncontent','mod_simplecertificate', $stdobj);
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml =  get_string('emailcoursecompletioncontent','mod_simplecertificate', $stdobj);
        $message->smallmessage = '';
        $message->notification = '0';
        $message->contexturl = new \moodle_url("/mod/simplecertificate/view.php",array("id"=>$record->id));
        $message->contexturlname = $record->name;
        $message->replyto = "no-reply@example.com";
        $message->courseid = $courseid; // This is required in recent versions, use it from 3.2 on https://tracker.moodle.org/browse/MDL-47162
        $messageid = message_send($message);*/
    }
}