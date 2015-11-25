<?php
///*** it's not working well
/// try to chage to behat


define('CLI_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

// Transaction.
$transaction = $DB->start_delegated_transaction();

// Create new course.
$srcfolder             = $CFG->dirroot.'/mod/simplecertificate/tests/other/backup-test-v2.1-with-issued-certs';
$folder = $CFG->dataroot. '/temp/backup/7af1bcb7cf5c268130c23e5947efbcc3';
$categoryid         = 1; // e.g. 1 == Miscellaneous
$userdoingrestore   = 2; // e.g. 2 == admin
$courseid           = restore_dbops::create_new_course('restore teste', 'rt', $categoryid);

// Restore backup into course.



if (!is_file($folder.'/'.basename($srcfolder))) {
     xcopy($srcfolder, $folder);
} 

$controller = new restore_controller($folder, $courseid,
backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userdoingrestore,
backup::TARGET_NEW_COURSE);
$controller->execute_precheck();
$controller->execute_plan();

// Commit.
$transaction->allow_commit();

// rmdir($folder);

function xcopy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                xcopy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 
