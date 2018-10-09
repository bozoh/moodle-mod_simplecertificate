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
 * This file contains the definition for the library class for coursename texmark plugin
 *
 *
 * @package simplecertificatetextmark_coursename
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');

/**
 *
 * Library class for coursename textmark plugin
 *
 * @package   simplecertificatetextmark_coursename
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificate_textmark_coursename extends simplecertificate_textmark_plugin {
    public function get_type() {
        return 'coursename';
    }

    public function get_name() {
        return get_string('name', 'simplecertificatetextmark_coursename');
    }

    protected function is_valid_textmark($name, $attribute = null, $formatter = null) {
        try {
            parent::is_valid_textmark($name, $attribute, $formatter);
        } catch (Exception $e) {
            return null;
        }

        if ($name != 'COURSENAME') {
            return null;
        }
        return $this->get_textmark_formated_text($name, $attribute, $formatter);
    }

    public function get_names() {
        return array('COURSENAME');

    }

    protected function get_attributes() {
        return array();
    }

    public function is_enabled() {
        //TODO get from settings
        return true;
    }

    protected function get_replace_text($name, $attribute = null, $formatter = null) {
        $coursename = strip_tags($this->smplcert->get_coursename());

        $textmark = $this->get_textmark_formated_text($name, $attribute, $formatter);
        switch($textmark) {
            case '{COURSENAME}':
                return $coursename;
            break;
            case '{COURSENAME:ucase}':
                return strtoupper($coursename);
            break;
            case '{COURSENAME:lcase}':
                return strtolower($coursename);
            break;
            case '{COURSENAME:ucasefirst}':
                return ucwords($coursename);
            break;
        }
    }
}
