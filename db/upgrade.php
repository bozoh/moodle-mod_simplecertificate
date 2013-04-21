<?php

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
// using the functions defined in lib/ddllib.php

function xmldb_simplecertificate_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2013042001) {
        
        $table = new xmldb_table('simplecertificate');
        $field = new xmldb_field('disablecode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'requiredtime');

        // Conditionally launch add field disablecode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         $field = new xmldb_field('codex', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '10', 'disablecode');

        // Conditionally launch add field codex
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('codey', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '10', 'codex');

        // Conditionally launch add field codey
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         $field = new xmldb_field('enablesecondpage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'codey');

        // Conditionally launch add field enablesecondpage
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         $field = new xmldb_field('secondpagex', XMLDB_TYPE_INTEGER, '4', null, null, null, '10', 'enablesecondpage');

        // Conditionally launch add field secondpagex
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('secondpagey', XMLDB_TYPE_INTEGER, '4', null, null, null, '50', 'secondpagex');

        // Conditionally launch add field secondpagey
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         $field = new xmldb_field('secondpagetext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagey');

        // Conditionally launch add field secondpagetext
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
         $field = new xmldb_field('secondpagetextformat', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagetext');

        // Conditionally launch add field secondpagetextformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // simplecertificate savepoint reached
        upgrade_mod_savepoint(true, 2013042001, 'simplecertificate');

    }
    return true;
}
