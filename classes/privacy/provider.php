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

namespace mod_simplecertificate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Class provider
 *
 * @package    mod_simplecertificate
 * @copyright  2024 Karl Michael Reyes <michaelreyes@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns metadata about this plugin's data.
     *
     * @param collection $collection The initialised collection to add metadata to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'simplecertificate_issues',
            [
                'userid' => 'privacy:metadata:simplecertificate_issues:userid',
                'certificateid' => 'privacy:metadata:simplecertificate_issues:certificateid',
                'certificatename' => 'privacy:metadata:simplecertificate_issues:certificatename',
                'code' => 'privacy:metadata:simplecertificate_issues:code',
                'timecreated' => 'privacy:metadata:simplecertificate_issues:timecreated',
                'timedeleted' => 'privacy:metadata:simplecertificate_issues:timedeleted',
                'haschange' => 'privacy:metadata:simplecertificate_issues:haschange',
                'pathnamehash' => 'privacy:metadata:simplecertificate_issues:pathnamehash',
                'coursename' => 'privacy:metadata:simplecertificate_issues:coursename',
            ],
            'privacy:metadata:simplecertificate_issues'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user ID to search.
     * @return contextlist The contextlist containing the user's information.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "
            SELECT c.id
              FROM {simplecertificate_issues} si
              JOIN {context} c ON c.instanceid = si.certificateid
             WHERE si.userid = :userid
        ";
        $params = ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $contextid = $context->id;

            $sql = "
                SELECT si.*
                  FROM {simplecertificate_issues} si
                 WHERE si.userid = :userid
                   AND si.certificateid = :certificateid
            ";
            $params = ['userid' => $userid, 'certificateid' => $context->instanceid];
            $issues = \core_privacy\local\request\helper::get_records_sql($sql, $params);

            if (!empty($issues)) {
                foreach ($issues as $issue) {
                    // Export user data for this context.
                    writer::with_context($context)->export_data(
                        [
                            get_string('simplecertificateissue', 'mod_simplecertificate'),
                        ],
                        (object) $issue
                    );
                }
            }
        }
    }

    public static function export_user_preferences(int $userid) {
        // Fetch user preference for the simplecertificate plugin.
        $certificateemailnotification = get_user_preferences('certificateemailnotification', null, $userid);

        // Check if the preference is set and handle the description accordingly.
        if (null !== $certificateemailnotification) {
            switch ($certificateemailnotification) {
                case 0:
                    $certificateemailnotificationdescription = get_string('certificateemailnotificationno', 'mod_simplecertificate');
                    break;
                case 1:
                default:
                    $certificateemailnotificationdescription = get_string('certificateemailnotificationyes', 'mod_simplecertificate');
                    break;
            }

            // Export the user preference.
            writer::export_user_preference(
                'mod_simplecertificate',
                'certificateemailnotification',
                $certificateemailnotification,
                $certificateemailnotificationdescription
            );
        }
    }

    /**
     * Delete all user data for the specified contexts.
     *
     * @param \context $context The context to delete information for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Delete user data from the issues table related to the given context.
        global $DB;
        $DB->delete_records('simplecertificate_issues', ['certificateid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified approved context.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records('simplecertificate_issues', [
                'certificateid' => $context->instanceid,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Deletes multiple users' data from a specified context.
     *
     * @param approved_userlist $userlist The approved userlist to delete data for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (!empty($userids)) {
            list($in_sql, $in_params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params = array_merge($in_params, ['certificateid' => $context->instanceid]);
            $DB->delete_records_select('simplecertificate_issues', "certificateid = :certificateid AND userid $in_sql", $params);
        }
    }
}
