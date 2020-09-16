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
 * Privacy Subsystem implementation.
 *
 * @package mod_simplecertificate
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplecertificate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {simplecertificate} sc ON sc.id = cm.instance
                  JOIN {simplecertificate_issues} sci ON sci.certificateid = sc.id
                 WHERE sci.userid = :userid";

        $params = [
            'modname' => 'simplecertificate',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];

        $contextlist = new contextlist();
        $contextlist->set_component('simplecertificate');
        return $contextlist->add_from_sql($sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $contexts = $contextlist->get_contexts();
        $userid = $contextlist->get_user()->id;

        foreach ($contexts as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $sql1 = "SELECT sci.*
                           FROM {simplecertificate_issues} sci
                           JOIN {course_modules} cm ON cm.instance = sci.certificateid
                           JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                          WHERE sci.userid = :userid
                            AND cm.id = :cmid";

                $params = [
                    'modname' => 'simplecertificate',
                    'cmid' => $context->instanceid,
                    'userid' => $userid
                ];

                $issues = $DB->get_records_sql($sql1, $params);

                $data = (object) [
                    'issues' => $issues,
                ];

                writer::with_context($context)->export_data([], $data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT sc.id
                  FROM {simplecertificate} sc
                  JOIN {course_modules} cm ON cm.instance = sc.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'simplecertificate',
            'cmid' => $context->instanceid,
        ];

        $certificateid = $DB->get_field_sql($sql, $params);

        $DB->delete_records('simplecertificate_issues', ['certificateid' => $certificateid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $contexts = $contextlist->get_contexts();
        $userid = $contextlist->get_user()->id;
        $cmids = [];
        foreach ($contexts as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cmids[] = $context->instanceid;
            }
        }
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);

        $sql = "SELECT sci.id
                  FROM {simplecertificate_issues} sci
                  JOIN {course_modules} cm ON cm.instance = sci.certificateid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE sci.userid = :userid
                  AND cm.id $insql";

        $params = [
            'modname' => 'simplecertificate',
            'userid' => $userid
        ];
        $params = array_merge($params, $inparams);

        $issueids = $DB->get_fieldset_sql($sql, $params);

        $DB->delete_records_list('simplecertificate_issues', 'id', array_values($issueids));
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT sci.userid
                  FROM {simplecertificate_issues} sci
                  JOIN {course_modules} cm ON cm.instance = sci.certificateid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'simplecertificate',
            'cmid' => $context->instanceid
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT sci.id
                  FROM {simplecertificate_issues} sci
                  JOIN {course_modules} cm ON cm.instance = sci.certificateid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE sci.userid $insql
                   AND cm.id = :cmid";

        $params = [
            'modname' => 'simplecertificate',
            'cmid' => $context->instanceid
        ];

        $params = array_merge($params, $inparams);

        $issueids = $DB->get_fieldset_sql($sql, $params);

        $DB->delete_records_list('simplecertificate_issues', 'id', array_values($issueids));
    }

    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('simplecertificate_issues', [
            'userid' => 'privacy:metadata:userid',
            'certificatename' => 'privacy:metadata:certificatename',
            'code' => 'privacy:metadata:code',
        ], 'privacy:metadata:simplecertificate_issues');

        return $collection;
    }
}