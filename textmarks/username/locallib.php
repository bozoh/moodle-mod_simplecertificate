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
 * This file contains the definition for the library class for username texmark plugin
 *
 *
 * @package simplecertificatetextmark_username
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');

/**
 *
 * Library class for username textmark plugin
 *
 * @package   simplecertificatetextmark_username
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificate_textmark_username extends simplecertificate_textmark_plugin {
    private $user;


    public function get_type() {
        return 'username';
    }

    public function get_name() {
        return get_string('name', 'simplecertificatetextmark_username');
    }

    protected function is_valid_textmark($name, $attribute = null, $formatter = null) {
        try {
            parent::is_valid_textmark($name, $attribute, $formatter);
        } catch (Exception $e) {
            return null;
        }

        switch($name) {
            case 'FULLNAME':
            case 'FIRSTNAME':
            case 'LASTNAME':
                if (!empty($attribute)) {
                    return null;
                }

        }
        return $this->get_textmark_text($name, $attribute, $formatter);
    }

    public function get_names() {
        return array(
            'USERNAME',
            'FULLNAME',
            'FIRSTNAME',
            'LASTNAME'
        );
    }

    protected function get_attributes() {
        return array(
            'fullname',
            'firstname',
            'lastname'
        );
    }

    protected function get_formatters() {
        return array(
            'ucase',
            'lcase',
            'ucasefirst'
        );
    }

    public function is_enabled() {
        //TODO get from settings
        return true;
    }

    public function get_replace_text($textmark) {
        if (empty($this->user)) {
            $issuecert = $this->smplcert->get_issue();
            $this->user = get_complete_user_data('id', $issuecert->userid);
            if (!$this->user) {
                print_error('nousersfound', 'moodle');
                return;
            }
        }

        $firstname = strip_tags($this->user->firstname);
        $lastname = strip_tags($this->user->lastname);
        $fullname = strip_tags(fullname($this->user));

        switch($textmark) {
            // All fullname Textmark.
            case '{USERNAME}':
            case '{USERNAME:fullname}':
            case '{FULLNAME}':
                return $fullname;
            break;
            case '{USERNAME:ucase}':
            case '{USERNAME:fullname:ucase}':
            case '{FULLNAME:ucase}':
                return strtoupper($fullname);
            break;
            case '{USERNAME:lcase}':
            case '{USERNAME:fullname:lcase}':
            case '{FULLNAME:lcase}':
                return strtolower($fullname);
            break;
            case '{USERNAME:ucasefirst}':
            case '{USERNAME:fullname:ucasefirst}':
            case '{FULLNAME:ucasefirst}':
                return ucwords($fullname);
            break;
            // All firstname Textmark.
            case '{USERNAME:firstname}':
            case '{FIRSTNAME}':
                return $firstname;
            break;
            case '{USERNAME:firstname:ucase}':
            case '{FIRSTNAME:ucase}':
                return strtoupper($firstname);
            break;
            case '{USERNAME:firstname:lcase}':
            case '{FIRSTNAME:lcase}':
                return strtolower($firstname);
            break;
            case '{USERNAME:firstname:ucasefirst}':
            case '{FIRSTNAME:ucasefirst}':
                return ucwords($firstname);
            break;
            // All lastname Textmark.
            case '{USERNAME:lastname}':
            case '{lastname}':
                return $lastname;
            break;
            case '{USERNAME:lastname:ucase}':
            case '{lastname:ucase}':
                return strtoupper($lastname);
            break;
            case '{USERNAME:lastname:lcase}':
            case '{lastname:lcase}':
                return strtolower($lastname);
            break;
            case '{USERNAME:lastname:ucasefirst}':
            case '{lastname:ucasefirst}':
                return ucwords($lastname);
            break;
        }
    }
}
