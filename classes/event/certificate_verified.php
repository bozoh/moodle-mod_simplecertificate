<?php

namespace mod_simplecertificate\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_simplecertificate issued certificate verified event class.
 *
 * @package    mod
 * @subpackage simplecertificate
 * @author	   Carlos Alexandre S. da Fonseca
 * @copyright  2015 - Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
*/
class certificate_verified extends \core\event\base  {
    
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'simplecertificate_issues';
    }
    
    public static function get_name() {
        return get_string('eventcertificate_verified', 'simplecertificate');
    }
    
    public function get_description() {
        return get_string('eventcertificate_verified_description', 'simplecertificate', array(
            'userid' => $this->userid,
            'certificateid' => $this->objectid,
            'certiticate_userid' => $this->relateduserid 
        ));
         "The user with id {$a->userid} verified the certificate with id {$a->certificateid} issued to user with id {$a->certiticate_userid}.";
    }
    
    public function get_url() {
        return new \moodle_url('/mod/simplecertificate/verify.php', array('code' => $this->other['issuedcertcode']));
    }
    
    public function get_legacy_logdata() {
        return array($this->contextinstanceid, 'simplecertificate', 'verify', $this->get_url()->out_as_local_url(false), $this->objectid);

    }
    
//         public static function get_legacy_eventname() {
//         // Override ONLY if you are migrating events_trigger() call.
//             return 'MYPLUGIN_OLD_EVENT_NAME';
//         }
    
//     protected function get_legacy_eventdata() {
//         // Override if you migrating events_trigger() call.
//                 $data = new \stdClass();
//                 $data->id = $this->objectid;
//                 $data->userid = $this->relateduserid;
//                 return $data;
//             }


}