<?php
namespace mod_simplecertificate\task;
include_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
use core_availability\info_module;
class checkforcompletion extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('checkforcompletion', 'simplecertificate');
    }


    public function execute() {
        GLOBAL $DB;
        /*
         * This function will get run only if the current user have not watched the certifcate at the time the task runs
         */
        $certificates = $DB->get_records("simplecertificate");
        foreach ($certificates as $certificate){
            //check if the module have restrictions option enabled
            if(!empty($certificate->notifyonrestriction) && $certificate->notifyonrestriction=="1"){
                $modid = $DB->get_field_sql("SELECT md.id from {course_modules} md join {modules} m " .
                    "ON m.name=:name and m.id=md.module and instance=:id",
                    array("name" => "simplecertificate", "id" => $certificate->id));
                $cm = get_coursemodule_from_id('simplecertificate', $modid, 0, false, MUST_EXIST);
                $course = $DB->get_record('course', array('id' => $cm->course));
                $coursecontext = \context_course::instance($cm->course);
                $context = \context_module::instance ($cm->id);
                $users = \get_enrolled_users($coursecontext);
                //get enrolled users and check if they can view the course / exclude the manager and admins because they always can
                foreach ($users as $user){
                    if(!has_capability('mod/simplecertificate:manage', $context, $user)){
                        if($this->isNotified($certificate,$user)){
                            echo $user->firstname . " " . $user->lastname . "</br>";
                            if(info_module::is_user_visible($cm->id, $user->id, false)){
                                $simplecertificate = new \simplecertificate($context, $cm, $course);
                                $data = $simplecertificate->get_issue($user);
                                $simplecertificate->set_instance($certificate);
                                $simplecertificate->create_pdf($data);
                                $simplecertificate->save_pdf($data);
                                $simplecertificate->send_certificade_email($data);
                                //create issue and submit it over email.
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Prechecks the module if its allready emailed, if not then work it through
     */
    private function isNotified($certificate,$user){
        GlOBAL $DB;
        if($issuedcerts = $DB->get_records("simplecertificate_issues" , array("certificateid"=>$certificate->id,"userid"=>$user->id))){
            return false;
        }else{
            return true;
        }
    }
}