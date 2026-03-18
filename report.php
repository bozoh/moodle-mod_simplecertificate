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
 * Site-level report of all issued certificates.
 *
 * @package    mod_simplecertificate
 * @copyright  2026 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use mod_simplecertificate\reportbuilder\local\systemreports\issued_certificates;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$systemcontext = context_system::instance();
require_login();
require_capability('moodle/site:viewreports', $systemcontext);

$PAGE->set_url('/mod/simplecertificate/report.php');
$PAGE->set_context($systemcontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('issuedview', 'simplecertificate'));

$report = system_report_factory::create(issued_certificates::class, context_system::instance());
echo $report->output();

echo $OUTPUT->footer();
