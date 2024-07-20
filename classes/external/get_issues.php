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
 * This class contains the get_issues webservice functions.
 *
 * @package     mod_simplecertificate
 * @copyright   2024 David Herney - cirano
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_simplecertificate\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Get the current user certificate issues.
 *
 * @copyright   2024 David Herney - cirano
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_issues extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() : external_function_parameters {
        return new \external_function_parameters([
            'value' => new \external_value(PARAM_TEXT, 'To search', VALUE_REQUIRED),
            'field' => new \external_value(PARAM_TEXT, 'Field in which to search (id, username, idnumber)', VALUE_DEFAULT, 'id'),
        ]);
    }

    /**
     * Get issues.
     *
     * @param string $value Value to search
     * @param string $field Field in which to search
     *
     * @return array
     */
    public static function execute(string $value, string $field = 'id'): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['value' => $value, 'field' => $field]);

        $syscontext = \context_system::instance();
        require_capability('mod/simplecertificate:view', $syscontext);

        $availablefields = ['id', 'username', 'idnumber'];
        if (!in_array($params['field'], $availablefields)) {
            throw new \moodle_exception('invalidfilterfield', 'mod_simplecertificate');
        }

        $user = $DB->get_record('user', [$params['field'] => $params['value']], '*', MUST_EXIST);

        $returnfields = 'id, certificateid, certificatename, coursename, code, timecreated';
        $issues = $DB->get_records('simplecertificate_issues', ['userid' => $user->id], 'timecreated DESC', $returnfields);

        return $issues;
    }

    /**
     * Return user certificates.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {

        return new external_multiple_structure(
                new external_single_structure(
                    [
                    'id' => new \external_value(PARAM_INT, 'Issue id'),
                    'certificateid' => new \external_value(PARAM_INT, 'Certificate id'),
                    'certificatename' => new \external_value(PARAM_TEXT, 'Certificate name'),
                    'coursename' => new \external_value(PARAM_TEXT, 'Course name'),
                    'code' => new \external_value(PARAM_TEXT, 'Certificate code'),
                    'timecreated' => new \external_value(PARAM_INT, 'Time created'),
                ],
                'Certificate issue'
            ),
            'User certificates', VALUE_DEFAULT, null
        );
    }
}
