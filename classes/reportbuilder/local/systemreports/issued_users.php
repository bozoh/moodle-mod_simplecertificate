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

namespace mod_simplecertificate\reportbuilder\local\systemreports;

use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use core_user\fields;
use lang_string;
use mod_simplecertificate\reportbuilder\local\entities\issued_certificate;

defined('MOODLE_INTERNAL') || die();

/**
 * Issued users system report for showing users who have been issued a certificate.
 *
 * @package    mod_simplecertificate
 * @copyright  2024 onwards SimpleCertificate contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issued_users extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->set_main_table('user', $entityuseralias);
        $this->add_entity($entityuser);

        // Any columns required by actions should be defined here to ensure they're always available.
        $fullnamefields = array_map(fn($field) => "{$entityuseralias}.{$field}", fields::get_name_fields());
        $this->add_base_fields("{$entityuseralias}.id, " . implode(', ', $fullnamefields));

        if ($this->get_parameter('withcheckboxes', false, PARAM_BOOL)) {
            $this->set_checkbox_toggleall(static function(\stdClass $row): array {
                return [$row->id, fullname($row)];
            });
        }

        // Exclude deleted users.
        $this->add_base_condition_sql("{$entityuseralias}.deleted <> 1");

        // Join issued certificates entity, restricted to the current certificate instance.
        $entityissue = new issued_certificate();
        $issuealias = $entityissue->get_table_alias('simplecertificate_issues');
        $certificateid = (int) $this->get_parameter('certificateid', 0, PARAM_INT);

        $this->add_entity($entityissue->add_join(
            "INNER JOIN {simplecertificate_issues} {$issuealias}
                ON {$issuealias}.userid = {$entityuseralias}.id
                AND {$issuealias}.certificateid = {$certificateid}
                AND {$issuealias}.timedeleted IS NULL"
        ));

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Set if report can be downloaded.
        $this->set_downloadable(true);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        $cmid = $this->get_parameter('cmid', 0, PARAM_INT);
        $context = \context_module::instance($cmid);
        return has_capability('mod/simplecertificate:manage', $context);
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $entityuser = $this->get_entity('user');

        $this->add_column($entityuser->get_column('fullnamewithpicturelink'));

        // Include identity field columns.
        $identitycolumns = $entityuser->get_identity_columns($this->get_context());
        foreach ($identitycolumns as $identitycolumn) {
            $this->add_column($identitycolumn);
        }

        $this->add_columns_from_entities([
            'issued_certificate:timecreated',
            'issued_certificate:code',
        ]);

        $this->set_initial_sort_column('issued_certificate:timecreated', SORT_DESC);
        $this->set_default_no_results_notice(new lang_string('nocertificatesissued', 'simplecertificate'));
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'user:fullname',
            'user:email',
            'issued_certificate:timecreated',
            'issued_certificate:code',
        ];
        $this->add_filters_from_entities($filters);
    }
}
