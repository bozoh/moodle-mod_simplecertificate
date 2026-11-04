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

// This file keeps track of upgrades to
// the certificate module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php.
defined('MOODLE_INTERNAL') || die();

function xmldb_simplecertificate_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014051000) {
        // Define field timestartdatefmt to be added to simplecertificate.
        $table = new xmldb_table('simplecertificate');
        $field = new xmldb_field('timestartdatefmt', XMLDB_TYPE_CHAR, '255', null, null, null, '', 'secondimage');

        // Conditionally launch add field timestartdatefmt.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('simplecertificate_issues');

        $field = new xmldb_field('haschange', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timedeleted');

        // Conditionally launch add field haschange.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'haschange');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Must move files to new area and add the certificate files hashs.
        $issuedcerts = $DB->get_records('simplecertificate_issues');
        $countcerts = count($issuedcerts);

        $fs = get_file_storage();

        $pbar = new progress_bar('simplecertificatemoveissuedfiles', 500, true);
        $i = 0;
        foreach ($issuedcerts as $issued) {
            $i++;
            try {
                $courseid = $DB->get_field('simplecertificate', 'course', ['id' => $issued->certificateid], MUST_EXIST);
                $cm = get_coursemodule_from_instance('simplecertificate', $issued->certificateid, $courseid, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                $user = $DB->get_record("user", ['id' => $issued->userid]);
                if ($user) {
                    $filename = str_replace(
                        ' ',
                        '_',
                        clean_filename($issued->certificatename . ' ' . fullname($user) . ' ' . $issued->id . '.pdf')
                    );
                } else {
                    $filename = str_replace(' ', '_', clean_filename($issued->certificatename . ' ' . $issued->id . '.pdf'));
                }

                $fileinfo = [
                    'contextid' => $context->id,
                    'component' => 'mod_simplecertificate',
                    'filearea' => 'issues',
                    'itemid' => $issued->id,
                    'filepath' => '/',
                    'filename' => $filename,
                ];

                if (
                    $fs->file_exists(
                        $fileinfo['contextid'],
                        $fileinfo['component'],
                        $fileinfo['filearea'],
                        $fileinfo['itemid'],
                        $fileinfo['filepath'],
                        $fileinfo['filename']
                    )
                ) {

                    $file = $fs->get_file(
                        $fileinfo['contextid'],
                        $fileinfo['component'],
                        $fileinfo['filearea'],
                        $fileinfo['itemid'],
                        $fileinfo['filepath'],
                        $fileinfo['filename']
                    );

                    $fileinfo['filename'] = str_replace(
                        ' ',
                        '_',
                        clean_filename($issued->certificatename . ' ' . $issued->id . '.pdf')
                    );

                    $newfile = $fs->create_file_from_storedfile($fileinfo, $file);
                    if ($newfile) {
                        $file->delete();
                        $issued->pathnamehash = $newfile->get_pathnamehash();
                    }
                } else {
                    throw new moodle_exception('filenotfound', 'simplecertificate', null, null, '');
                }
            } catch (Exception $e) {
                if (empty($issued->timedeleted)) {
                    $issued->haschange = 1;
                }
                $issued->pathnamehash = '';
            }
            $pbar->update($i, $countcerts, "Moving Issued certificate files  ($i/$countcerts)");
            if (!$DB->update_record('simplecertificate_issues', $issued)) {
                throw new moodle_exception(
                    'upgradeerror',
                    'simplecertificate',
                    null,
                    "Can't update an issued certificate [id->$issued->id]"
                );
            }
        }

        $field = new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'haschange');

        // Launch change of nullability for field pathnamehash.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('coursename');

        // Conditionally launch drop field coursename.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Simplecertificate savepoint reached.
        upgrade_mod_savepoint(true, 2014051000, 'simplecertificate');
    }

    if ($oldversion < 2017013001) {
        // Define coursename in simplecertificate_issues table.
        $table = new xmldb_table('simplecertificate_issues');

        // ...<FIELD NAME="coursename" TYPE="char" LENGTH="255" NOTNULL="true" SEQUENCE="false" PREVIOUS="pathnamehash" />.
        $field = new xmldb_field('coursename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '---', 'pathnamehash');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Must add course name in new column.
            $issuedcerts = $DB->get_records('simplecertificate_issues');
            $countcerts = count($issuedcerts);
            $count = 0;
            $pbar = new progress_bar('simplecertificateupdate', 500, true);
            foreach ($issuedcerts as $issued) {
                $coursename = $DB->get_field('simplecertificate', 'coursename', ['id' => $issued->certificateid]);
                if (!$coursename) {
                    try {
                        $courseid = $DB->get_field(
                            'simplecertificate', 'course', ['id' => $issued->certificateid], MUST_EXIST
                        );
                        $coursename = $DB->get_field('course', 'fullname', ['id' => $courseid], MUST_EXIST);
                    } catch (Exception $e) {
                        if (empty($issued->timedeleted)) {
                            $issued->haschange = 1;
                        }
                        $coursename = '';
                    }
                }
                $issued->coursename = $coursename;
                if (!$DB->update_record('simplecertificate_issues', $issued)) {
                    throw new moodle_exception(
                        'upgradeerror',
                        'simplecertificate',
                        null,
                        "Can't update an issued certificate [id->$issued->id]"
                    );
                }
                $count++;
                $pbar->update($count, $countcerts, "Moving Issued certificate files  ($i/$countcerts)");
            }
        }
        // Simplecertificate savepoint reached.
        upgrade_mod_savepoint(true, 2017013001, 'simplecertificate');
    }

    if ($oldversion < 2020091500) {
        // Define index course (not unique) to be added to simplecertificate.
        $table = new xmldb_table('simplecertificate');
        $index = new xmldb_index('course', XMLDB_INDEX_NOTUNIQUE, ['course']);

        // Conditionally launch add index course.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index certificate_user (not unique) to be added to simplecertificate_issues.
        $table = new xmldb_table('simplecertificate_issues');
        $index = new xmldb_index('certificate_user', XMLDB_INDEX_NOTUNIQUE, ['certificateid', 'userid']);

        // Conditionally launch add index certificate_user.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Simplecertificate savepoint reached.
        upgrade_mod_savepoint(true, 2020091500, 'simplecertificate');
    }

    if ($oldversion < 2024051106) {
        $table = new xmldb_table('simplecertificate');

        $fields = [];
        // Define fields to be added to simplecertificate.
        $fields[] = new xmldb_field('usesignature', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $fields[] = new xmldb_field('signposx', XMLDB_TYPE_INTEGER, '4');
        $fields[] = new xmldb_field('signposy', XMLDB_TYPE_INTEGER, '4');
        $fields[] = new xmldb_field('signwidth', XMLDB_TYPE_INTEGER, '4');
        $fields[] = new xmldb_field('signheight', XMLDB_TYPE_INTEGER, '4');

        foreach ($fields as $field) {
            // Conditionally launch add field.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Simplecertificate savepoint reached.
        upgrade_mod_savepoint(true, 2024051106, 'simplecertificate');
    }

    if ($oldversion < 2026040501) {
        // Change the certlifetime site setting to 0 by default.
        unset_config('certlifetime', 'simplecertificate');
        set_config('certlifetime', 0, 'simplecertificate');
    }

    if ($oldversion < 2026040502) {
        // Define unique index code to be added to simplecertificate_issues.
        $table = new xmldb_table('simplecertificate_issues');
        $index = new xmldb_index('code', XMLDB_INDEX_UNIQUE, ['code']);

        if (!$dbman->index_exists($table, $index)) {
            $duplicates = $DB->get_records_sql(
                "SELECT code, COUNT(id) AS duplicatecount
                   FROM {simplecertificate_issues}
                  GROUP BY code
                 HAVING COUNT(id) > 1",
                null,
                0,
                1
            );

            if (empty($duplicates)) {
                $dbman->add_index($table, $index);
            }
        }

        // Simplecertificate savepoint reached.
        upgrade_mod_savepoint(true, 2026040502, 'simplecertificate');
    }

    return true;
}
