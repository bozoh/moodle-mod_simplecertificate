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
 *
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/simplecertificate/backup/moodle2/restore_simplecertificate_stepslib.php');

/**
 * certificate restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_simplecertificate_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Certificate only has one structure step.
        $this->add_step(
                        new restore_simplecertificate_activity_structure_step(
                                        'simplecertificate_structure', 'simplecertificate.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('simplecertificate', array('intro'), 'simplecertificate');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('SIMPLECERTIFICATEVIEWBYID', '/mod/simplecertificate/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('SIMPLECERTIFICATEINDEX', '/mod/simplecertificate/index.php?id=$1', 'course');
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * certificate logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('simplecertificate', 'add', 'view.php?id={course_module}', '{simplecertificate}');
        $rules[] = new restore_log_rule('simplecertificate', 'update', 'view.php?id={course_module}', '{simplecertificate}');
        $rules[] = new restore_log_rule('simplecertificate', 'view', 'view.php?id={course_module}', '{simplecertificate}');
        $rules[] = new restore_log_rule('simplecertificate', 'received', 'report.php?a={simplecertificate}', '{simplecertificate}');
        $rules[] = new restore_log_rule('simplecertificate', 'view report', 'report.php?id={simplecertificate}',
                                        '{simplecertificate}');
        $rules[] = new restore_log_rule('simplecertificate', 'verifyt', 'verify.php?code={simplecertificate_issues}',
                                        '{simplecertificate}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs.
     * It must return one array
     * of {@link restore_log_rule} objects
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();
        // Fix old wrong uses (missing extension).
        $rules[] = new restore_log_rule('simplecertificate', 'view all', 'index.php?id={course}', null);
        return $rules;
    }

    /*
     * This function is called after all the activities in the backup have been restored. This allows us to get the new course
     * module ids, as they may have been restored after the
     * certificate module, meaning no id was available at the time.
     */
    public function after_restore() {
        global $DB;

        if ($certificate = $DB->get_record('simplecertificate', array('id' => $this->get_activityid()))) {
            if ($certificate->certdate <= -1000) { // If less or equal -1000, is mark as not sucefully retored in stepslib.
                $certificate->certdate = $certificate->certdate / -1000;

                if ($mapping = restore_dbops::get_backup_ids_record(
                                $this->get_restoreid(), 'course_module', $certificate->certdate)) {
                    // If certdate == certgrade the function get_backup_ids_record for certgrade returns null, could be a bug.
                    if ($certificate->certdate == $certificate->certgrade / -1000) {
                        $certificate->certgrade = $mapping->newitemid;
                    }
                    $certificate->certdate = $mapping->newitemid;
                } else {
                    $this->get_logger()->process(
                           "Failed to restore dependency in simplecertificate 'certdate'. " .
                           "Backup and restore will not work correctly unless you include the dependent module.",
                           backup::LOG_ERROR);
                }
            }

            if ($certificate->certgrade <= -1000) { // If greater than 0, then it is a grade item value.
                $certificate->certgrade = $certificate->certgrade / -1000;

                if ($mapping = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module',
                                                                    $certificate->certgrade)) {
                    $certificate->certgrade = $mapping->newitemid;
                } else {
                    $this->get_logger()->process(
                           "Failed to restore dependency in simplecertificate 'certgrade'. " .
                           "Backup and restore will not work correctly unless you include the dependent module.",
                           backup::LOG_ERROR);
                }
            }

            if (!$DB->update_record('simplecertificate', $certificate)) {
                throw new restore_task_exception('cannotrestore');
            }

            // Process issued files.
            if ($issues = $DB->get_records('simplecertificate_issues', array('certificateid' => $certificate->id))) {

                $fs = get_file_storage();
                foreach ($issues as $issued) {
                    try {
                        $context = context_module::instance($this->get_moduleid());

                        if ($this->get_old_moduleversion() < 2014051000 &&
                             ($user = $DB->get_record("user", array('id' => $issued->userid)))) {
                            $filename = str_replace(' ', '_',
                                                    clean_filename(
                                                                $issued->certificatename . ' ' . fullname($user) . ' ' .
                                                                 $issued->pathnamehash . '.pdf'));
                        } else {
                            $filename = str_replace(' ', '_',
                                                  clean_filename($issued->certificatename . ' ' . $issued->pathnamehash . '.pdf'));
                        }

                        $fileinfo = array('contextid' => $context->id, 'component' => 'mod_simplecertificate',
                            'filearea' => 'issues', 'itemid' => $issued->pathnamehash, 'filepath' => '/', 'filename' => $filename);

                        if ($fs->file_exists($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {

                            $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

                            $context = context_user::instance($issued->userid);
                            $newfileinfo = $fileinfo;
                            $newfileinfo['itemid'] = $issued->userid;
                            $newfileinfo['filename'] = str_replace(' ', '_',
                                                                clean_filename(
                                                                            $issued->certificatename . ' ' . $issued->id . '.pdf'));

                            if ($fs->file_exists($newfileinfo['contextid'], $newfileinfo['component'], $newfileinfo['filearea'],
                                                $newfileinfo['itemid'], $newfileinfo['filepath'], $newfileinfo['filename'])) {
                                $newfile = $fs->get_file($newfileinfo['contextid'], $newfileinfo['component'],
                                                        $newfileinfo['filearea'], $newfileinfo['itemid'], $newfileinfo['filepath'],
                                                        $newfileinfo['filename']);
                            } else {
                                $newfile = $fs->create_file_from_storedfile($newfileinfo, $file);
                            }

                            $issued->pathnamehash = $newfile->get_pathnamehash();
                            $fs->delete_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                                $fileinfo['itemid']);
                        } else {
                            throw new moodle_exception('filenotfound', 'simplecertificate', null, null, '');
                        }
                    } catch (Exception $e) {
                        $this->log(" Can't restore file $filename. " . $e->getMessage(), backup::LOG_WARNING);
                        $issued->haschange = 1;
                    }

                    if (!$DB->update_record('simplecertificate_issues', $issued)) {
                        throw new restore_task_exception('cannotrestore');
                    }
                }
            }
        }
    }
}
