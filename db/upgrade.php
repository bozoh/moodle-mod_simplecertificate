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
    if ($oldversion < 2013053102) {

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

        $field = new xmldb_field('secondimage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'secondpagetextformat');

        // Conditionally launch add field secondimage
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing type of field certdatefmt on table simplecertificate to char
        $field = new xmldb_field('certdatefmt', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'certdate');

        // Launch change of type for field certdatefmt
        $dbman->change_field_type($table, $field);



        // simplecertificate savepoint reached
        upgrade_mod_savepoint(true, 2013053102, 'simplecertificate');

    }

    //--- Unir tudo em uma versão só
    if ($oldversion < 2013092000) {

        // Changing nullability of field certificateimage on table simplecertificate to null.
        $table = new xmldb_table('simplecertificate');
        $field = new xmldb_field('certificateimage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'height');

        // Launch change of type for field certificateimage.
        $dbman->change_field_type($table, $field);

        // Launch rename field disablecode->printqrcode.
        
        $field = new xmldb_field('disablecode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'requiredtime');

        if ($dbman->field_exists($table, $field)) {
        	$objs = $DB->get_records('simplecertificate',array("disablecode"=>0),'','id');
        	$ids='';
        	
        	foreach ($objs as $obj) {
        		$ids= $ids . $obj->id .',';
        	}
        	if (!empty($ids)) {
        		$ids=chop($ids,',');
        	
				$sql = 'UPDATE {simplecertificate} SET disablecode = 1 WHERE id in (' . $ids . ')';
				$DB->execute ( $sql );
				
				$sql = 'UPDATE {simplecertificate} SET disablecode = 0 WHERE id not in (' . $ids . ')';
				$DB->execute ( $sql );
        	}

	        // 	Launch change of default for field.
        	$dbman->change_field_default($table, $field);
	        // Launch rename field printqrcode.
    	    $dbman->rename_field($table, $field, 'printqrcode');
        } else {
        	$field = new xmldb_field('printqrcode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'requiredtime');
        	if (!$dbman->field_exists($table, $field)) {
        		$dbman->add_field($table, $field);
        	}
        }

        $field = new xmldb_field('qrcodefirstpage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'printqrcode');

        // Conditionally launch add field qrcodefirstpage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        
        $field = new xmldb_field('savecert');

        // Conditionally launch drop field savecert.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

    	$table = new xmldb_table('simplecertificate_issues');
    	$field = new xmldb_field('certificatename', XMLDB_TYPE_TEXT, null, null, null, null, null, 'userid');
    	
    	// Conditionally launch add field certificatename.
    	if (!$dbman->field_exists($table, $field)) {
    		$dbman->add_field($table, $field);
    	}
    	
    	$field = new xmldb_field('username');
    	
    	// Conditionally launch drop field username.
    	if ($dbman->field_exists($table, $field)) {
    		$dbman->drop_field($table, $field);
    	}
    	
    	$field = new xmldb_field('coursename');
    	
    	// Conditionally launch drop field coursename.
    	if ($dbman->field_exists($table, $field)) {
    		$dbman->drop_field($table, $field);
    	}
    	
    	//Populating certificatename
    	$certs = $DB->get_records('simplecertificate');
    	foreach ($certs as $cert) {
    		$DB->execute('UPDATE {simplecertificate_issues} SET certificatename = ? WHERE certificateid = ?', array($cert->name, $cert->id));
    	}
    	
    	// Simplecertificate savepoint reached.
    	upgrade_mod_savepoint(true, 2013092000, 'simplecertificate');
    }
    
    if ($oldversion < 2013111900) {
    	
    	//Certdate update
    	$objs = $DB->get_records('simplecertificate',array("certdate"=>1),'','id');
    	$objs = $objs + $DB->get_records('simplecertificate',array("certdate"=>2),'','id');
        $ids='';
		        	
        foreach ($objs as $obj) {
        	$ids= $ids . $obj->id .',';
        }
        if (!empty($ids)) {
        	$ids = chop($ids,',');
        	$sql = 'UPDATE {simplecertificate} SET certdate = -1 * certdate where id in (' . $ids . ')';
    		$DB->execute ($sql);
    	
        }
        
        //Certgrade update
        $objs = $DB->get_records('simplecertificate',array("certgrade"=>1),'','id');
        $ids='';
         
        foreach ($objs as $obj) {
        	$ids= $ids . $obj->id .',';
        }
        if (!empty($ids)) {
        	$ids = chop($ids,',');
        	$sql = 'UPDATE {simplecertificate} SET certdate = -1 * certgrade where id in (' . $ids . ')';
        	$DB->execute ($sql);
        }
        
    	// Simplecertificate savepoint reached.
    	upgrade_mod_savepoint(true, 2013111900, 'simplecertificate');
    }
    
    return true;
}
