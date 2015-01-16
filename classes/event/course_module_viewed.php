<?php

namespace mod_simplecertificate\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_simplecertificate course module viewed event class.
 * 
 * @package    mod
 * @subpackage simplecertificate
 * @author	   Carlos Alexandre S. da Fonseca
 * @copyright  2015 - Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     * 
     * @return void
     */
    protected function init() {
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['crud'] = 'r';
        $this->data['objecttable'] = 'simplecertificate';
    }

    /**
     * Get URL related to the action
     * 
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/simplecertificate/view.php', array('id' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     * 
     * @return array null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'simplecertificate', 'view', 'view.php?id=' . $this->objectid, $this->objectid, 
                $this->contextinstanceid);
    }

}