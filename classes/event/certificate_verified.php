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
 * The mod_simplecertificate issued certificate verified event class.
 *
 * @package    mod
 * @subpackage simplecertificate
 * @author       Carlos Alexandre S. da Fonseca
 * @copyright  2015 - Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class certificate_verified extends \core\event\base  {

    protected function init() {
        $this->data['crud'] = 'r'; // ... c(reate), r(ead), u(pdate), d(elete).
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
    }

    public function get_url() {
        return new \moodle_url('/mod/simplecertificate/verify.php', array('code' => $this->other['issuedcertcode']));
    }

    public function get_legacy_logdata() {
        return array($this->contextinstanceid, 'simplecertificate',
            'verify',
            $this->get_url()->out_as_local_url(false),
            $this->objectid
        );

    }
}