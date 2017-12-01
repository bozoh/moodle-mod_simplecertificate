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

namespace mod_simplecertificate\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_simplecertificate course module viewed event class.
 *
 * @package    mod
 * @subpackage simplecertificate
 * @author       Carlos Alexandre S. da Fonseca
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