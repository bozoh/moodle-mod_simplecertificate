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
 * External forum API
 *
 * @package mod_simplecertificate
 * @copyright 2014 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class mod_simplecertificate_external extends external_api {

    /**
     * To validade input parameters
     * @return external_function_parameters
     */
    public static function verify_code_parameters() {
        return new external_function_parameters(
              array(
                  'code' => new external_value(PARAM_TEXT, 'Certificate Code', VALUE_REQUIRED)
               )
        );
    }


    public static function verify_code($code) {
        global $DB;
        // Parameter validation.
        // REQUIRED.
        $params = self::validate_parameters(self::verify_code_parameters(), array('code' => $code));

        $code = trim($params['code']);

        if (empty($code)) {
            throw new invalid_parameter_exception('Empty code');
        }
        if (!$issuecert = $DB->get_record('simplecertificate_issues', array('code' => $code))) {
            throw new invalid_parameter_exception('Invalid code');
        }

        if (!$user = get_complete_user_data('id', $issuecert->userid)) {
            throw new moodle_exception('cannotfinduser', 'error', null, '');
        }

        return fullname($user);
    }

    /**
     * Validate the return value
     * @return external_value
     */
    public static function verify_code_returns() {
        return new external_value(PARAM_TEXT, 'certificate owner username');
    }

}