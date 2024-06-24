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
 * Class to get the user certificates
 *
 * @package    mod_simplecertificate
 * @copyright  2020 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplecertificate;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to get the user certificates.
 *
 * @package    mod_simplecertificate
 * @copyright  2020 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificates {

    /**
     * @var \stdClass $user The target user.
     */
    protected $user;

    /**
     * @var int $courseid The course id.
     */
    protected $courseid;

    /**
     * Certificates constructor.
     *
     * @param \stdClass $user
     * @param int $courseid
     */
    public function __construct($user, $courseid = 0) {
        $this->user = $user;
        $this->courseid = $courseid;
    }

    /**
     * Returns all issued certificates from all certificates modules.
     *
     * @return array
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_all_certificates() {
        global $USER;

        // Only admins can view certificates from other users.
        if ($this->user->id != $USER->id && !is_siteadmin()) {
            return [];
        }

        $simplecertificate = $this->get_from_simplecertificate();
        $customcert = $this->get_from_customcert();

        $allcerts = array_merge($simplecertificate, $customcert);

        if (!empty($allcerts)) {
            return array_values($this->group_certificates_by_course($allcerts));
        }

        return [];
    }

    /**
     * Get all issued certificates from simplecertificate module.
     *
     * @return array
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_from_simplecertificate() {
        global $DB;

        $simplecertificate = \core_plugin_manager::instance()->get_plugin_info('mod_simplecertificate');

        if (is_null($simplecertificate)) {
            return [];
        }

        $params = ['userid' => $this->user->id];
        if ($this->courseid) {
            $sql = "SELECT
                    sci.code,
                    sci.pathnamehash,
                    sci.certificatename,
                    c.id as courseid,
                    c.fullname,
                    c.shortname,
                    'simplecertificate' as module
                FROM {simplecertificate_issues} sci
                INNER JOIN {simplecertificate} sc ON sc.id = sci.certificateid
                INNER JOIN {course} c ON sc.course = c.id
                WHERE sci.userid = :userid AND c.id = :courseid
                ORDER BY c.fullname, sci.timecreated";

            $params['courseid'] = $this->courseid;
        } else {
            $sql = "SELECT
                    sci.code,
                    sci.pathnamehash,
                    sci.certificatename,
                    0 courseid,
                    sci.coursename fullname,
                    sci.coursename shortname,
                    'simplecertificate' module
                FROM {simplecertificate_issues} sci
                WHERE sci.userid = :userid
                ORDER BY sci.coursename, sci.timecreated";
        }

        $certificates = $DB->get_records_sql($sql, $params);

        if (empty($certificates)) {
            return [];
        }

        $fs = get_file_storage();

        $returndata = [];
        foreach ($certificates as $certificate) {
            if (!$fs->file_exists_by_hash($certificate->pathnamehash)) {
                continue;
            }

            $url = new \moodle_url('/mod/simplecertificate/wmsendfile.php', [
                'code' => $certificate->code
            ]);

            $certificate->downloadurl = $url->out(false);

            if (strpos($certificate->certificatename, $certificate->fullname) === 0) {
                $certificate->certificatename = substr($certificate->certificatename, strlen($certificate->fullname) + 1);
            }

            $returndata[] = $certificate;
        }

        return $returndata;
    }

    /**
     * Get issued certificates from customcert module.
     *
     * @return array
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_from_customcert() {
        global $DB;

        $customcert = \core_plugin_manager::instance()->get_plugin_info('mod_customcert');

        if (is_null($customcert)) {
            return [];
        }

        $sql = "SELECT
                  ci.customcertid,
                  cc.name certificatename,
                  c.id as courseid,
                  c.fullname,
                  c.shortname,
                  'customcert' as module
                FROM {customcert_issues} ci
                INNER JOIN {customcert} cc ON cc.id = ci.customcertid
                INNER JOIN {course} c ON c.id = cc.course
                WHERE ci.userid = :userid";

        $params = ['userid' => $this->user->id];

        if ($this->courseid) {
            $sql .= ' AND c.id = :courseid';
            $params['courseid'] = $this->courseid;
        }

        $sql .= ' ORDER BY c.fullname, ci.timecreated';

        $certificates = $DB->get_records_sql($sql, $params);

        if (empty($certificates)) {
            return [];
        }

        foreach ($certificates as $key => $certificate) {
            $url = new \moodle_url('/mod/customcert/my_certificates.php', [
                'downloadcert' => true,
                'userid' => $this->user->id,
                'certificateid' => $certificate->customcertid
            ]);

            $certificates[$key]->downloadurl = $url->out(false);
        }

        return $certificates;
    }

    /**
     * Group certificates by course.
     *
     * @param array $certificates
     *
     * @return array
     */
    public static function group_certificates_by_course($certificates) {
        $returndata = [];

        foreach ($certificates as $certificate) {
            $certs = [$certificate];

            if (isset($returndata[$certificate->shortname])) {
                $certs = array_merge($certs, $returndata[$certificate->shortname]['certificates']);

                $returndata[$certificate->shortname]['certificates'] = $certs;

                continue;
            }

            $returndata[$certificate->shortname] = [
                'courseid' => $certificate->courseid,
                'shortname' => $certificate->shortname,
                'fullname' => $certificate->fullname,
                'certificates' => $certs
            ];
        }

        return $returndata;
    }
}
