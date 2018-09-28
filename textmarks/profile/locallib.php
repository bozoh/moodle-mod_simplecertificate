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
 * This file contains the definition for the library class for profile texmark plugin
 *
 *
 * @package simplecertificatetextmark_profile
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/simplecertificate/textmarks/textmark_plugin.php');

/**
 *
 * Library class for profile textmark plugin
 *
 * @package   simplecertificatetextmark_profile
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplecertificate_textmark_profile extends simplecertificate_textmark_plugin {
    private $user;

    public function get_type() {
        return 'profile';
    }

    public function get_name() {
        return get_string('name', 'simplecertificatetextmark_profile');
    }

    protected function is_valid_textmark($name, $attribute = null, $formatter = null) {
        try {
            parent::is_valid_textmark($name, $attribute, $formatter);
        } catch (Exception $e) {
            return null;
        }

        // Excluding some textmarks by name.
        switch ($name) {
            // With all attributes and formatter
            case 'PROFILE':
                if (empty($attribute)) {
                    return null;
                }
            break;

            // No attribtes or formaters.
            case 'ICQ':
            case 'SKYPE':
            case 'YAHOO':
            case 'AIM':
            case 'MSN':
            case 'PHONE1':
            case 'PHONE2':
            case 'USERIMAGE':
                if (!empty($attribute)) {
                    return null;
                }
                if (!empty($formatter)) {
                    return null;
                }
            break;

            // No attribtes and all default formatters.
            case 'INSTITUTION':
            case 'DEPARTMENT':
            case 'ADDRESS':
            case 'CITY':
            case 'COUNTRY':
                if (!empty($attribute)) {
                    return null;
                }
            break;

            // No attribtes and no ucasefirst formatter.
            case 'URL':
            case 'EMAIL':
                if (!empty($attribute)) {
                    return null;
                }
                if (!empty($formatter)) {
                    if ($formatter == 'ucasefirst') {
                        return null;
                    }
                }
            break;
        }

        // Excluding some textmarks for PROFILE textmark.
        if ($name == 'PROFILE') {
            switch ($attribute) {
                // No formatters.
                case 'icq':
                case 'skype':
                case 'yahoo':
                case 'aim':
                case 'msn':
                case 'phone1':
                case 'phone2':
                case 'userimage':
                    if (!empty($formatter)) {
                        return null;
                    }
                break;

                // No ucasefirst formatter.
                case 'url':
                case 'email':
                    if (!empty($formatter)) {
                        if ($formatter == 'ucasefirst') {
                            return null;
                        }
                    }
                break;
            }
        }

        return $this->get_textmark_formated_text($name, $attribute, $formatter);
    }



    public function get_names() {
        return array(
            'PROFILE',
            'EMAIL',
            'ICQ',
            'SKYPE',
            'YAHOO',
            'AIM',
            'MSN',
            'PHONE1',
            'PHONE2',
            'INSTITUTION',
            'DEPARTMENT',
            'ADDRESS',
            'CITY',
            'COUNTRY',
            'URL',
            'USERIMAGE'
        );
    }

    protected function get_attributes() {
        return array(
            'email',
            'icq',
            'skype',
            'yahoo',
            'aim',
            'msn',
            'phone1',
            'phone2',
            'institution',
            'department',
            'address',
            'city',
            'country',
            'url',
            'userimage'
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

    protected function get_replace_text($name, $attribute = null, $formatter = null) {
        if (empty($this->user)) {
            $issuecert = $this->smplcert->get_issue();
            $this->user = get_complete_user_data('id', $issuecert->userid);
            if (!$this->user) {
                print_error('nousersfound', 'moodle');
                return;
            }
        }

        $user = $this->user;
        $value = null;

        // Process attributes exceptions, which don't have direct value from user obj (db),
        // like userimage, and full country name
        $isexception = false;
        $exception = $this->get_textmark_formated_text($name, $attribute);
        switch ($exception) {
            case '{COUNTRY}':
            case '{PROFILE:country}':
                $isexception = true;
                if (!empty($user->country)) {
                    $value = strip_tags(get_string($user->country, 'countries'));
                }
            break;

            case '{USERIMAGE}':
            case '{PROFILE:userimage}':
                $isexception = true;
                if ($user->picture > 0) {
                    $value = $this->get_user_image($user);
                }
            break;
        }

        if (!$isexception) {
            if (!empty($attribute)) {
                $value = $user->$attribute;
            } else {
                $name = strtolower($name);
                $value = $user->$name;
            }

            $value = strip_tags($value);
        }

        if (empty($formatter)) {
            return $value;
        }

        switch ($formatter) {
            case self::LOWER_CASE_FORMATTER:
                return strtolower($value);
            break;

            case self::UPPER_CASE_FORMATTER:
                return strtoupper($value);
            break;

            case self::UPPER_CASE_FIRST_FORMATTER:
                return ucwords($value);
            break;
        }
    }

    private function get_user_image($user) {
        //TODO
        return '';
    }
}
