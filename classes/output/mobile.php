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
 * Simple Certificate activity module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <bozohhot@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplecertificate\output;

defined('MOODLE_INTERNAL') || die();

use context_module;

global $CFG;
require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");

/**
  * Simple Certificate activity module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <bozohhot@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns page showing the emebeded PDF certificate
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function download_certificate($args) {
        global $OUTPUT, $USER, $DB;

        $id = $args['cmid']; // Course Module ID.

        $cm = get_coursemodule_from_id( 'simplecertificate', $id);
        if (!$cm) {
            print_error('Course Module ID was incorrect');
        }

        $course = $DB->get_record('course', array('id' => $cm->course));
        if (!$course) {
            print_error('course is misconfigured');
        }

        $certificate = $DB->get_record('simplecertificate', array('id' => $cm->instance));
        if (!$certificate) {
            print_error('course module is incorrect');
        }

        $context = context_module::instance ($cm->id);

        require_login( $course->id, false, $cm);
        require_capability('mod/simplecertificate:view', $context);

        $simplecertificate = new \simplecertificate($context, $cm, $course);
        $simplecertificate->set_instance($certificate);

        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        $issuecert = $simplecertificate->get_issue($USER);
        $file = $simplecertificate->get_issue_file($issuecert);

        $data = array(
            'file_base64' => base64_encode($file->get_content())
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_simplecertificate/download', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => '',
        );
    }
}
