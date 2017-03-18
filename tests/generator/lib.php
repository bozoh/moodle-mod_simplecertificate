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
    	global $CFG, $USER;
    	$record = (object)(array)$record;
    	$record->images = array();
    	$user_context = context_user::instance($USER->id);
    	$fileinfo = array(
    	               'contextid' => $user_context->id, 
    	               'component' => 'user', 
    	               'filearea' => 'draft', 
    	               'filepath' => '/'
    	);
 
    	$defaultsettings = array(
    	        'name'             => 'Unit Case Test Certificate',
    	        'intro'            => '<h1>Unit Case Test Certificate</h1>',
    	        'introformat'      => FORMAT_HTML,
    	        'certificatetextx' => 0,
    	        'certificatetexty' => 50,
    	        'enablesecondpage' => 1,
    	        'secondpagex'      => 0,
    	        'secondpagey'      => 50,
    	        'width'            => get_config('simplecertificate','width'),
    	        'height'           => get_config('simplecertificate','height'), 
    	        'printqrcode'      => 1,
    	        'codex'            => 30,
    	        'codey'            => 130,
    	        'certdatefmt'      => 'Rio de Janeiro, %d de %B de %Y',
    	        'qrcodefirstpage'  => 1
    	);
    	
    	foreach ($defaultsettings as $name => $value) {
    	    if (!isset($record->{$name})) {
    	        $record->{$name} = $value;
    	    }
    	}
    	
    	if (!isset($record->certificatetext['text'])) {
    		$record->certificatetext['text'] = file_get_contents("$CFG->dirroot/mod/simplecertificate/tests/fixtures/firstpage.html");
    		$record->certificatetextformat = FORMAT_HTML;
    		
    	}
    	
    	if (!isset($record->secondpagetext['text'])) {
    	   $record->secondpagetext['text'] = file_get_contents("$CFG->dirroot/mod/simplecertificate/tests/fixtures/secondpage.html");
    	   $record->secondpagetextformat = FORMAT_HTML;
    	}
    	
    	if (!isset($record->certificatetextformat)){
    		$record->certificatetextformat = FORMAT_HTML;
    	}
    	
    	if (!isset($record->certificateimage)) {
    	    $record->certificateimage = $CFG->dirroot . '/mod/simplecertificate/tests/fixtures/firstpagetestimage.jpg';
    	}
    	
    	if (!isset($record->secondimage)) {
    	    $record->secondimage = $CFG->dirroot . '/mod/simplecertificate/tests/fixtures/secondpagetestimage.jpg'; 
    	}
    	
    	$fs = get_file_storage();
    	if (!empty($record->certificateimage)) {
            // Firstpage image
            $fileinfo['itemid'] = rand(1, 10);
            $fileinfo['filename'] = basename($record->certificateimage);
            $file = $fs->create_file_from_pathname($fileinfo, $record->certificateimage);
            $record->certificateimage = $fileinfo['itemid'];
            $record->images[0] = $fileinfo['filename'];
        }
    	
    	if (!empty($record->secondimage)) {
            // Secondpage image
            $fileinfo['itemid'] = rand(11, 21);
            $fileinfo['filename'] = basename($record->secondimage);
            $file = $fs->create_file_from_pathname($fileinfo, $record->secondimage);
            $record->secondimage = $fileinfo['itemid'];
            $record->images[1] = $fileinfo['filename'];
        }
        
       return parent::create_instance($record, (array)$options);
    }
}
