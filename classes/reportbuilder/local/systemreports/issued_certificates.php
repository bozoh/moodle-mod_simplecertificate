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
use core_reportbuilder\system_report;
use lang_string;
use mod_simplecertificate\reportbuilder\local\entities\issued_certificate;

defined('MOODLE_INTERNAL') || die();

/**
 * Site-level issued certificates system report.
 *
 * Lists all certificates issued across the site for administrators.
 *
 * @package    mod_simplecertificate
 * @copyright  2026 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issued_certificates extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entityissue = new issued_certificate();
        $issuealias = $entityissue->get_table_alias('simplecertificate_issues');

        $this->set_main_table('simplecertificate_issues', $issuealias);
        $this->add_entity($entityissue);

        // Only show non-deleted issued certificates.
        $this->add_base_condition_sql("{$issuealias}.timedeleted IS NULL");

        // Join user entity.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->add_entity($entityuser->add_join(
            "INNER JOIN {user} {$entityuseralias}
                ON {$entityuseralias}.id = {$issuealias}.userid
                AND {$entityuseralias}.deleted <> 1"
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
        return has_capability('moodle/site:configview', \context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $enableidentity = get_config('simplecertificate', 'enableidentity');
        $columns = [
            'user:fullnamewithpicturelink',
            'issued_certificate:certificatelink',
            'issued_certificate:coursename',
            'issued_certificate:timecreated',
            'issued_certificate:code',
        ];

        if ($enableidentity) {
            array_unshift($columns, 'user:username');
        }

        $this->add_columns_from_entities($columns);

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
        $enableidentity = get_config('simplecertificate', 'enableidentity');
        $filters = [
            'user:fullname',
            'user:email',
            'issued_certificate:certificatename',
            'issued_certificate:coursename',
            'issued_certificate:timecreated',
            'issued_certificate:code',
        ];

        if ($enableidentity) {
            array_unshift($filters, 'user:username');
        }

        $this->add_filters_from_entities($filters);
    }
}
