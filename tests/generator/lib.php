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
 * mod_simplecertificate data generator.
 *
 * @package    mod_simplecertificate
 * @category   test
 * @copyright  2013 Carlos Alexandre S. da Fonceca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_simplecertificate data generator class.
 *
 * @package    mod_simplecertificate
 * @category   test
 * @copyright  2013 Carlos Alexandre S. da Fonceca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplecertificate_generator extends testing_module_generator {

        /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
    }
    

    public function create_instance($record = null, array $options = null) {
    	global $CFG;
    	$record = (object)(array)$record;
    	
    	if (!isset($record->name)) {
    		$record->name = 'Unit Case Test Certificate';
    	}
    	    	
    	if (!isset($record->intro)) {
    		$record->intro = '<h1>Unit Case Test Certificate</h1>';
    	}
    	if (!isset($record->introformat)) {
    		$record->introformat = FORMAT_HTML;
    	}
    	
    	if (!isset($record->certificatetext['text'])) {
    		$record->certificatetext['text'] = file_get_contents("$CFG->dirroot/mod/simplecertificate/tests/fixtures/firstpage.html");
    		$record->certificatetextformat = FORMAT_HTML;
    		$record->certificatetextx = 50;
    		$record->certificatetexty = 0;
    		$record->enablesecondpage = 1;
    		$record->secondpagex = 50;
    		$record->secondpagey = 0;
    		$record->secondpagetext['text'] = file_get_contents("$CFG->dirroot/mod/simplecertificate/tests/fixtures/firstpage.html");
    		$record->secondpagetextformat = FORMAT_HTML;
    	}
    	
    	if (!isset($record->certificatetextformat)){
    		$record->certificatetextformat = FORMAT_HTML;
    	}
    	
    	//For test, delivery option must be 2 or 3
    	 if (!isset($record->delivery) || $record->delivery < 2) {
    		$record->delivery = 3;
    	} 
    	
    	//TODO See how i can test files upload
    	
    	//if (!isset($record->certificateimage));

    	/*Using default (in settings)
    	if (!isset($record->width));
    	if (!isset($record->height));
    	
    	if (!isset($record->certificatetextx));
    	if (!isset($record->certificatetexty));
    	if (!isset($record->coursename));
    	if (!isset($record->coursehours));
    	if (!isset($record->outcome));
    	if (!isset($record->certdate));
    	if (!isset($record->certdatefmt));
    	if (!isset($record->certgrade));
    	if (!isset($record->gradefmt));
    	if (!isset($record->emailfrom));
    	if (!isset($record->emailothers));
    	if (!isset($record->emailteachers));
    	if (!isset($record->reportcert));
    	
    	if (!isset($record->requiredtime))
    	if (!isset($record->printqrcode));
    	if (!isset($record->qrcodefirstpage));
    	if (!isset($record->codex));
    	if (!isset($record->codey));
    	if (!isset($record->enablesecondpage));
    	if (!isset($record->secondpagex));
    	if (!isset($record->secondpagey));
    	if (!isset($record->secondpagetext));
    	if (!isset($record->secondpagetextformat));
    	if (!isset($record->secondimage));*/
    	
        return parent::create_instance($record, (array)$options);
    }
    
    public function create_issue($record = null, array $options = null) {
    	global $CFG, $DB, $USER;
    	
    	$record = (object)(array)$record;
    	
    	if (!isset($record->certificate)) { 
    		throw new coding_exception("No Certificate is set");
    	}
    	
    	require_once("$CFG->dirroot/mod/simplecertificate/locallib.php");
    	$simplecerticiate = new simplecertificate($record->certificate);
    	if (isset($record->user)) {
    		return $simplecerticiate->get_issue($record->user);
    	}
    	return $simplecerticiate->get_issue();
    	
    }
}
