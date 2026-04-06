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

namespace mod_simplecertificate\task;

/**
 * Scheduled task to remove old issued certificates.
 *
 * @package   mod_simplecertificate
 * @copyright 2026 David Herney - BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcleanup', 'mod_simplecertificate');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $lifetime = get_config('simplecertificate', 'certlifetime');

        if ($lifetime <= 0) {
            return;
        }

        $month = 2629744;
        $timedeleted = time() - ($lifetime * $month);
        $issues = $DB->get_records_select('simplecertificate_issues', 'timedeleted <= ?', [$timedeleted]);
        $count = count($issues);
        mtrace("Removing {$count} old issued certificates...");
        if ($count > 0) {
            $fs = get_file_storage();
            foreach ($issues as $issue) {
                if (!empty($issue->pathnamehash)) {
                    $file = $fs->get_file_by_hash($issue->pathnamehash);
                    if ($file) {
                        $file->delete();
                    }
                }
            }
            $DB->delete_records_select('simplecertificate_issues', 'timedeleted <= ?', [$timedeleted]);
        }
        mtrace('done');
    }
}
