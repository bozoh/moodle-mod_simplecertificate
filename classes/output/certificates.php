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
 * Certificates page renderer
 *
 * @package    mod_simplecertificate
 * @copyright  2020 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplecertificate\output;

use renderable;
use templatable;
use renderer_base;

/**
 * My certificates page renderer
 *
 * @package    mod_simplecertificate
 * @copyright  2020 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates implements renderable, templatable {
    /**
     * @var int $courseid The course id.
     */
    protected $courseid;

    /**
     * Certificates constructor.
     *
     * @param int $courseid
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     *
     * @return array
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $certificates = new \mod_simplecertificate\certificates($USER, $this->courseid);

        $issuedcertificates = $certificates->get_all_certificates();

        $title = get_string('certificatestitle', 'mod_simplecertificate');

        return [
            'hascertificates' => (count($issuedcertificates)) ? true : false,
            'coursescertificates' => $issuedcertificates,
            'title' => $title,
        ];
    }
}