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

namespace mod_simplecertificate\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_value;

require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

/**
 * Implementation of web service mod_simplecertificate_create_issue
 *
 * @package    mod_simplecertificate
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_issue extends external_api {

    /**
     * Describes the parameters for mod_simplecertificate_create_issue
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID to issue the certificate for'),
            'certificateid' => new external_value(PARAM_INT, 'Simple Certificate activity ID', VALUE_DEFAULT, 0),
            'cmid' => new external_value(PARAM_INT, 'Course Module ID', VALUE_DEFAULT, 0),
            'archive' => new external_value(PARAM_BOOL, 'Whether to archive the issue or not', VALUE_DEFAULT, false),
            'variables' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TAG, 'Variable name'),
                    'value' => new external_value(PARAM_TEXT, 'Value of the variable'),
                ]),
                'List of variable to overwrite in the certificate. Only available to archived certificates.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Create a new certificate issue for a user
     *
     * @param int $userid User ID to issue the certificate for
     * @param int $certificateid Simple Certificate activity ID
     * @param bool $archive Whether to archive the issue or not
     * @param array|null $variables List of variable to overwrite in the certificate
     * @return string Issue code
     * @throws \moodle_exception
     */
    public static function execute(int $userid,
                                    int $certificateid,
                                    int $cmid,
                                    bool $archive = false,
                                    ?array $variables = []
                                ): string {
        global $DB;

        // Parameter validation.
        [
            'userid' => $userid,
            'certificateid' => $certificateid,
            'cmid' => $cmid,
            'archive' => $archive,
            'variables' => $variables,
        ] = self::validate_parameters(
                self::execute_parameters(),
                    [
                        'userid' => $userid,
                        'certificateid' => $certificateid,
                        'cmid' => $cmid,
                        'archive' => $archive,
                        'variables' => $variables,
                    ]
        );

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        // Capability check.
        if (!has_capability('mod/simplecertificate:issue', $syscontext)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'issue a certificate');
        }

        if (empty($certificateid) && empty($cmid)) {
            throw new \moodle_exception('invaliddata', 'error', '', '', 'Either certificateid or cmid must be provided');
        }

        if (!empty($cmid)) {
            $cm = get_coursemodule_from_id('simplecertificate', $cmid, 0, false, MUST_EXIST);
            $certificateid = $cm->instance;
        } else {
            $cm = get_coursemodule_from_instance('simplecertificate', $certificateid, 0, false, MUST_EXIST);
        }

        // From web services we don't call require_login(), but rather validate_context.
        $context = \context_module::instance($cm->id);

        $instance = $DB->get_record('simplecertificate', ['id' => $certificateid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Not archived certificates can only be issued to users enrolled in the course.
        if (!$archive) {
            // Check if the user already has enrolled in course.
            $enrolled = is_enrolled($context, $user);
            if (!$enrolled) {
                throw new \moodle_exception('usernotincourse', 'error', '', $user->id);
            }
        }

        $simplecertificate = new \simplecertificate($context, $cm, $course);

        // Issue the certificate.
        $issuecert = $simplecertificate->get_issue($user);

        if ($archive) {
            $simplecertificate->set_customvariables($variables);
            $simplecertificate->get_issue_file($issuecert);
            $simplecertificate->archive_issue($issuecert);
        }

        return $issuecert->code;
    }

    /**
     * Describe the return structure for mod_simplecertificate_create_issue
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_TEXT, 'Issue code');
    }
}
