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
     * @var int keep track of how many chapters have been created.
     */
    protected $chaptercount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->chaptercount = 0;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        if (!isset($record->certificatetext)) {
            $record->certificatetext['text'] = '
<h1>FRIST PAGE</h1>
<p> </p>
<table border="5" cellpadding="1" align="center">
<tbody>
<tr>
<td>Username: {USERNAME}</td>
<td>Coursename: {COURSENAME}</td>
<td>Grade:{GRADE}</td>
<td>Date: {DATE}</td>
<td>Outcome: {OUTCOME}</td>
</tr>
<tr>
<td>Hours: {HOURS}</td>
<td>Teachers: {TEACHERS}</td>
<td>Idnumber: {IDNUMBER}</td>
<td>
<p> </p>
First Name: {FIRSTNAME}</td>
<td>Last Name: {LASTNAME}</td>
</tr>
<tr>
<td>Email: {EMAIL}</td>
<td>ICQ: {ICQ}</td>
<td>Skype: {SKYPE}</td>
<td>Yahoo: {YAHOO}</td>
<td>AIM: {AIM}</td>
</tr>
<tr>
<td><br />Msn: {MSN}</td>
<td>Phone 1: {PHONE1}</td>
<td>Phone 2: {PHONE2}</td>
<td>Institution: {INSTITUTION}</td>
<td>Department: {DEPARTMENT}</td>
</tr>
<tr>
<td>Address: {ADDRESS}</td>
<td>City: {CITY}</td>
<td>Country: {COUNTRY}</td>
<td>Url: {URL}</td>
<td>Certificate Code: {CERTIFICATECODE}</td>
</tr>
</tbody>
</table>';
			$record->certificatetextformat = FORMAT_HTML;
			$record->certificatetextx = 50;
			$record->certificatetexty = 0;
        }
        
        if (!isset($record->enablesecondpage)) {
        	$record->enablesecondpage = 1;
        }
        
        if (!isset($record->secondpagetext)) {
            $record->secondpagetext['text'] = '<h1>SECOND PAGE</h1>
<p> </p>
<table border="5" cellpadding="1" align="center">
<tbody>
<tr>
<td>Username: {USERNAME}</td>
<td>Coursename: {COURSENAME}</td>
<td>Grade:{GRADE}</td>
<td>Date: {DATE}</td>
<td>Outcome: {OUTCOME}</td>
</tr>
<tr>
<td>Hours: {HOURS}</td>
<td>Teachers: {TEACHERS}</td>
<td>Idnumber: {IDNUMBER}</td>
<td>
<p> </p>
First Name: {FIRSTNAME}</td>
<td>Last Name: {LASTNAME}</td>
</tr>
<tr>
<td>Email: {EMAIL}</td>
<td>ICQ: {ICQ}</td>
<td>Skype: {SKYPE}</td>
<td>Yahoo: {YAHOO}</td>
<td>AIM: {AIM}</td>
</tr>
<tr>
<td><br />Msn: {MSN}</td>
<td>Phone 1: {PHONE1}</td>
<td>Phone 2: {PHONE2}</td>
<td>Institution: {INSTITUTION}</td>
<td>Department: {DEPARTMENT}</td>
</tr>
<tr>
<td>Address: {ADDRESS}</td>
<td>City: {CITY}</td>
<td>Country: {COUNTRY}</td>
<td>Url: {URL}</td>
<td>Certificate Code: {CERTIFICATECODE}</td>
</tr>
<tr>
<td style="text-align: center;" colspan="5">Birthday: {PROFILE_BIRTHDAY}</td>
</tr>
</tbody>
</table>';
            $record->secondpagetextformat = FORMAT_HTML;
            $record->secondpagex = 50;
            $record->secondpagey = 0;
        }

        if (!isset($record->certdatefmt)) {
        	$record->certdatefmt = 'Rio de Janeiro, %d de %B de %Y ';
        }
        
        if (!isset($record->printqrcode)) {
        	$record->printqrcode = 1;
        } 
        $record->qrcodefirstpage = 1;
        $record->codex = 30;
        $record->codey = 130;
        
        $record->requiredtime = 3;
        $record->coursehours = 4;
        $record->emailothers = 'bozohhot@hotmail.com';
        if (!isset($record->name)){ 
        	$record->name= 'Tests unit certificate';
        }
        $record->intro['text'] = 'Tests unit certificate';
        $record->introformat = FORMAT_HTML;
        
        
        
        return parent::create_instance($record, (array)$options);
    }
}
