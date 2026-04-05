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

declare(strict_types=1);

namespace mod_simplecertificate\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Issued certificate entity
 *
 * @package     mod_simplecertificate
 * @copyright   2026 David Herney - BambuCo
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issued_certificate extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'simplecertificate_issues',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('issuedview', 'simplecertificate');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('simplecertificate_issues');

        // Date issued column.
        $columns[] = (new column(
            'timecreated',
            new lang_string('issueddate', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'userdate']);

        // Certificate name column.
        $columns[] = (new column(
            'certificatename',
            new lang_string('certificatename', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.certificatename")
            ->set_is_sortable(true);

        // Certificate name with link to the module column.
        $columns[] = (new column(
            'certificatelink',
            new lang_string('certificatename', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.certificatename, {$tablealias}.certificateid")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $certificatename, \stdClass $row): string {
                if (empty($certificatename)) {
                    return '';
                }
                if (empty($row->certificateid)) {
                    return $certificatename;
                }
                $url = new \moodle_url('/mod/simplecertificate/view.php', ['a' => $row->certificateid, 'tab' => 1]);
                return \html_writer::link($url, $certificatename, ['target' => '_blank']);
            });

        // Course name column.
        $columns[] = (new column(
            'coursename',
            new lang_string('coursename', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.coursename")
            ->set_is_sortable(true);

        // Code column with certificate file (download link) column.
        $columns[] = (new column(
            'code',
            new lang_string('code', 'simplecertificate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.code, {$tablealias}.pathnamehash")
            ->set_is_sortable(false)
            ->add_callback(static function(?string $code, \stdClass $row): string {
                if (empty($row->pathnamehash) || empty($code)) {
                    return '';
                }
                $url = new \moodle_url('/mod/simplecertificate/wmsendfile.php', ['code' => $code]);
                return \html_writer::link($url, $code, ['target' => '_blank']);
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('simplecertificate_issues');

        // Date issued filter.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('issueddate', 'simplecertificate'),
            $this->get_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        // Certificate name filter.
        $filters[] = (new filter(
            text::class,
            'certificatename',
            new lang_string('certificatename', 'simplecertificate'),
            $this->get_entity_name(),
            "{$tablealias}.certificatename"
        ))
            ->add_joins($this->get_joins());

        // Course name filter.
        $filters[] = (new filter(
            text::class,
            'coursename',
            new lang_string('coursename', 'simplecertificate'),
            $this->get_entity_name(),
            "{$tablealias}.coursename"
        ))
            ->add_joins($this->get_joins());

        // Code filter.
        $filters[] = (new filter(
            text::class,
            'code',
            new lang_string('code', 'simplecertificate'),
            $this->get_entity_name(),
            "{$tablealias}.code"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
