<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/lib/formslib.php');


class verify_form extends moodleform {

    // Define the form
    function definition () {
        global $CFG, $COURSE, $USER;

        $mform =& $this->_form;
               
        
        
        $mform->addElement('text', 'code', get_string('code', 'simplecertificate'), array('size'=>'36'));
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        
        $this->add_action_buttons(false, get_string('getcertificate','simplecertificate'));
    }

    function definition_after_data() {
       
    }

    function validation($usernew, $files) {
    }
}