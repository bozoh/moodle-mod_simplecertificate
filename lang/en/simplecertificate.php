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
 * Language strings for the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Simple Certificate';
$string['modulenameplural'] = 'Simple Certificates';
$string['pluginname'] = 'Simple Certificate';
$string['viewcertificateviews'] = 'View {$a} issued certificates';
$string['summaryofattempts'] = 'Summary of Previously Received Certificates';
$string['issued'] = 'Issued';
$string['coursegrade'] = 'Course Grade';
$string['getcertificate'] = 'Get your certificate';
$string['awardedto'] = 'Awarded To';
$string['receiveddate'] = 'Issued Date';
$string['grade'] = 'Grade';
$string['code'] = 'Code';
$string['report'] = 'Report';
$string['opendownload'] = 'Click the button below to save your certificate to your computer.';
$string['openemail'] = 'Click the button below and your certificate will be sent to you as an email attachment.';
$string['openwindow'] = 'Click the button below to open your certificate in a new browser window.';
$string['hours'] = 'hours';
$string['keywords'] = 'certificate, course, pdf, moodle';
$string['pluginadministration'] = 'Certificate administration';
$string['deletissuedcertificates'] = 'Delete issued certificates';
$string['nocertificatesissued'] = 'There are no certificates that have been issued';

// Form.
$string['certificatename'] = 'Certificate Name';
$string['certificateimage'] = 'Certificate Image File';
$string['certificatetext'] = 'Certificate Text';
$string['certificatetextx'] = 'Certificate Text Horizontal Position';
$string['certificatetexty'] = 'Certificate Text Vertical Position';
$string['height'] = 'Certificate Height';
$string['width'] = 'Certificate Width';
$string['coursename'] = 'Alternative Course Name';
$string['intro'] = 'Introduction';
$string['printoutcome'] = 'Print Outcome';
$string['printdate'] = 'Print Date';

// Second Page.
$string['secondpageoptions'] = 'Certificate Back page';
$string['enablesecondpage'] = 'Enable Certificate Back page';
$string['enablesecondpage_help'] = 'Enable Certificate Back page edition, if is disabled, only certificate QR code will be printed in back page (if the QR code is enabled)';
$string['secondimage'] = 'Certificate Back Image file';
$string['secondimage_help'] = 'This is the picture that will be used in the back of certificate';
$string['secondpagetext'] = 'Certificate Back Text';

$string['secondpagex'] = 'Certificate Back Text Horizontal Position';
$string['secondpagey'] = 'Certificate Back Text Vertical Position';
$string['secondtextposition'] = 'Certificate Back Text Position';
$string['secondtextposition_help'] = 'These are the XY coordinates (in millimeters) of the certificate back page text';

// QR Code.
$string['printqrcode'] = 'Print Certificate QR Code';
$string['printqrcode_help'] = 'Print (or not) certificate QR Code';
$string['codex'] = 'Certificate QR Code Horizontal Position';
$string['codey'] = 'Certificate QR Code Vertical Position';
$string['qrcodeposition'] = 'Certificate QR Code Position';
$string['qrcodeposition_help'] = 'These are the XY coordinates (in millimeters) of the certificate QR Code';
$string['defaultcodex'] = 'Default Horizontal QR code Position';
$string['defaultcodey'] = 'Default Vertical QR code Position';

// Date options.
$string['issueddate'] = 'Date Issued';
$string['completiondate'] = 'Course Completion';
$string['coursestartdate'] = 'Course Start Date';
$string['datefmt'] = 'Date Format';

// Date format options.
$string['userdateformat'] = 'User\'s Language Date Format';

$string['printgrade'] = 'Print Grade';
$string['gradefmt'] = 'Grade Format';
// Grade format options.
$string['gradeletter'] = 'Letter Grade';
$string['gradepercent'] = 'Percentage Grade';
$string['gradepoints'] = 'Points Grade';
$string['coursetimereq'] = 'Required minutes in course';
$string['emailteachers'] = 'Email Teachers';
$string['emailothers'] = 'Email Others';
$string['emailfrom'] = 'Email From name';
$string['delivery'] = 'Delivery';
// Delivery options.
$string['openbrowser'] = 'Open in new window';
$string['download'] = 'Force download';
$string['emailcertificate'] = 'Email';
$string['nodelivering'] = 'No delivering, user will receive this certificate using others ways';
$string['emailoncompletion'] = 'Email on course completion';
// Form options help text.
$string['certificatename_help'] = 'Certificate Name';
$string['certificatetext_help'] = 'This is the text that will be used in the certificate back, some special words will be replaced with variables such as course name, student\'s name, grade ...
These are:
<ul>
<li>{USERNAME} -> Full user name</li>
<li>{COURSENAME} -> Full course name (or a defined alternate course name)</li>
<li>{GRADE} -> Formatted Grade</li>
<li>{DATE} -> Formatted Date</li>
<li>{OUTCOME} -> Outcomes</li>
<li>{TEACHERS} -> Teachers List</li>
<li>{IDNUMBER} -> User id number</li>
<li>{FIRSTNAME} -> User first name</li>
<li>{LASTNAME} -> User last name</li>
<li>{EMAIL} -> User e-mail</li>
<li>{ICQ} -> User ICQ</li>
<li>{SKYPE} -> User Skype</li>
<li>{YAHOO} -> User yahoo messenger</li>
<li>{AIM} -> User AIM</li>
<li>{MSN} -> User MSN</li>
<li>{PHONE1} -> User 1° Phone Number</li>
<li>{PHONE2} -> User 2° Phone Number</li>
<li>{INSTITUTION} -> User institution</li>
<li>{DEPARTMENT} -> User department</li>
<li>{ADDRESS} -> User address</li>
<li>{CITY} -> User city</li>
<li>{COUNTRY} -> User country</li>
<li>{URL} -> User Home-page</li>
<li>{CERTIFICATECODE} -> Unique certificate code text</li>
<li>{USERROLENAME} -> User role name in course</li>
<li>{TIMESTART} -> User Enrollment start date in course</li>
<li>{USERIMAGE} -> User profile image</li>
<li>{USERRESULTS} -> User results (grade) in others course activities</li>
<li>{PROFILE_xxxx} -> User custom profile fields</li>
</ul>
In order to use custom profiles fields you must use "PORFILE_" prefix, for example: you has created a custom profile with shortname of "birthday," so the text mark used on certificate must be {PROFILE_BIRTHDAY}.
The text can use basic html, basic fonts, tables,  but avoid any position definition.';

$string['textposition'] = 'Certificate Text Position';
$string['textposition_help'] = 'These are the XY coordinates (in millimetres) of the certificate text';
$string['size'] = 'Certificate Size';
$string['size_help'] = 'These are the Width and Height size (in millimetres) of the certificate, Default size is A4 Landscape';
$string['coursename_help'] = 'Alternative Course Name';
$string['certificateimage_help'] = 'This is the picture that will be used in the certificate';

$string['printoutcome_help'] = 'You can choose any course outcome to print the name of the outcome and the user\'s received outcome on the certificate.  An example might be: Assignment Outcome: Proficient.';
$string['printdate_help'] = 'This is the date that will be printed, if a print date is selected. If the course completion date is selected but the student has not completed the course, the date received will be printed. You can also choose to print the date based on when an activity was graded. If a certificate is issued before that activity is graded, the date received will be printed.';
$string['datefmt_help'] = 'Enter a valid PHP date format pattern (<a href="http://www.php.net/manual/en/function.strftime.php"> Date Formats</a>). Or, leave it empty to use the format of the user\'s chosen language.';
$string['printgrade_help'] = 'You can choose any available course grade items from the gradebook to print the user\'s grade received for that item on the certificate.  The grade items are listed in the order in which they appear in the gradebook. Choose the format of the grade below.';
$string['gradefmt_help'] = 'There are three available formats if you choose to print a grade on the certificate:
<ul>
<li>Percentage Grade: Prints the grade as a percentage.</li>
<li>Points Grade: Prints the point value of the grade.</li>
<li>Letter Grade: Prints the percentage grade as a letter.</li>
</ul>';

$string['coursetimereq_help'] = 'Enter here the minimum amount of time, in minutes, that a student must be logged into the course before they will be able to receive the certificate.';
$string['emailteachers_help'] = 'If enabled, then teachers are alerted with an email whenever students receive a certificate.';
$string['emailothers_help'] = 'Enter the email addresses here, separated by a comma, of those who should be alerted with an email whenever students receive a certificate.';
$string['emailfrom_help'] = 'Alternate email form name';
$string['delivery_help'] = 'Choose here how you would like your students to get their certificate.
<ul>
<li>Open in Browser: Opens the certificate in a new browser window.</li>
<li>Force Download: Opens the browser file download window.</li>
<li>Email Certificate: Choosing this option sends the certificate to the student as an email attachment.</li>
<li>After a user receives their certificate, if they click on the certificate link from the course homepage, they will see the date they received their certificate and will be able to review their received certificate.</li>
</ul>';

// Form Sections.
$string['issueoptions'] = 'Issue Options';
$string['designoptions'] = 'Design Options';

// Emails text.
$string['emailstudentsubject'] = 'Your certificate for {$a->course}';
$string['emailstudenttext'] = '
Hello {$a->username},

		Attached is your certificate for {$a->course}.


THIS IS AN AUTOMATED MESSAGE - PLEASE DO NOT REPLY';

$string['emailteachermail'] = '
{$a->student} has received their certificate: \'{$a->certificate}\'
for {$a->course}.

You can review it here:

    {$a->url}';

$string['emailteachermailhtml'] = '
{$a->student} has received their certificate: \'<i>{$a->certificate}</i>\'
for {$a->course}.

You can review it here:

    <a href="{$a->url}">Certificate Report</a>.';

// Admin settings page.
$string['defaultwidth'] = 'Default Width';
$string['defaultheight'] = 'Default Height';
$string['defaultcertificatetextx'] = 'Default Horizontal Text Position';
$string['defaultcertificatetexty'] = 'Default Vertical Text Position';

// Erros.
$string['filenotfound'] = 'File not Found';
$string['invalidcode'] = 'Invalid certificate code';
$string['cantdeleteissue'] = 'Error removing issued certificates';
$string['requiredtimenotmet'] = 'You must have at least {$a->requiredtime} minutes in this course to issue this certificate';

// Verify certificate page.
$string['certificateverification'] = 'Certificate Verification';

// Settings.
$string['certlifetime'] = 'Keep issued certificates for: (in Months)';
$string['certlifetime_help'] = 'This specifies the length of time you want to keep issued certificates. Issued certificates that are older than this age are automatically deleted.';
$string['neverdeleteoption'] = 'Never delete';

$string['variablesoptions'] = 'Others Options';
$string['getcertificate'] = 'Get Certificate';
$string['verifycertificate'] = 'Verify Certificate';

$string['qrcodefirstpage'] = 'Print QR Code in the first page';
$string['qrcodefirstpage_help'] = 'Print QR Code in the first page';

// Tabs String.
$string['standardview'] = 'Issue a test certificate';
$string['issuedview'] = 'Issued certificates';
$string['bulkview'] = 'Bulk operations';

$string['cantissue'] = 'The certificate can\'t be issued, because the user hasn\'t met activity conditions';

// Bulk texts.
$string['onepdf'] = 'Download certificates in a one pdf file';
$string['multipdf'] = 'Download certificates in a zip file';
$string['sendtoemail'] = 'Send to user\'s email';
$string['showusers'] = 'Show';
$string['completedusers'] = 'Users that met the activity conditions';
$string['allusers'] = 'All users';
$string['bulkaction'] = 'Choose a Bulk Operation';
$string['bulkbuttonlabel'] = 'Send';
$string['emailsent'] = 'The emails have been sent';

$string['issueddownload'] = 'Issued certificate [id: {$a}] downloaded';

$string['defaultperpage'] = 'Per page';
$string['defaultperpage_help'] = 'Number of certificate to show per page (Max. 200)';

// For Capabilities.
$string['simplecertificate:addinstance'] = "Add Simple Certificate Activity";
$string['simplecertificate:manage'] = "Manage Simple Certificate Activity";
$string['simplecertificate:view'] = "View Simple Certificate Activity";

$string['usercontextnotfound'] = 'User context not found';
$string['usernotfound'] = 'User not found';
$string['coursenotfound'] = 'Course not found';
$string['issuedcertificatenotfound'] = 'Issued certificate not found';
$string['awardedsubject'] = 'Awarded certificate notification: {$a->certificate} issued to {$a->student}';
$string['certificatenot'] = 'Simple certificate instance not found';
$string['modulename_help'] = 'The simple certificate activity module enables the teacher to create a custom certificate that can be issued to participants who have completed the teacher’s specified requirements.';
$string['timestartdatefmt'] = 'Enrollment start date format';
$string['timestartdatefmt_help'] = 'Enter a valid PHP date format pattern (<a href="http://www.php.net/manual/en/function.strftime.php"> Date Formats</a>). Or, leave it empty to use the format of the user\'s chosen language.';
$string['certificatecopy'] = 'COPY';
$string['upgradeerror'] = 'Error while upgrading $a';
$string['notreceived'] = 'No issued certificate';

// Verify envent.
$string['eventcertificate_verified'] = 'Certificate verified';
$string['eventcertificate_verified_description'] = 'The user with id {$a->userid} verified the certificate with id {$a->certificateid}, issued to user with id {$a->certiticate_userid}.';
$string['deleteall'] = "Delete All";
$string['deleteselected'] = "Delete Selected";




