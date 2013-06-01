<?php

/**
 * Language strings for the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//-----
$string['modulename'] = 'Simple Certificate';
$string['modulenameplural'] = 'Simple Certificates';
$string['pluginname'] = 'Simple Certificate';
$string['viewcertificateviews'] = 'View {$a} issued certificates';
$string['summaryofattempts'] = 'Summary of Previously Received Certificates';
$string['issued'] = 'Issued';
$string['coursegrade'] = 'Course Grade';
$string['getcertificate'] = 'Get your certificate';
$string['awardedto'] = 'Awarded To';
$string['receiveddate'] = 'Date Received';
$string['grade'] = 'Grade';
$string['code'] = 'Code';
$string['report'] = 'Report';
$string['opendownload'] = 'Click the button below to save your certificate to your computer.';
$string['openemail'] = 'Click the button below and your certificate will be sent to you as an email attachment.';
$string['openwindow'] = 'Click the button below to open your certificate in a new browser window.';
$string['hours'] = 'hours';
$string['keywords'] = 'cetificate, course, pdf, moodle';
$string['pluginadministration'] = 'Certificate administration';
$string['awarded'] = 'Awarded';
$string['deletissuedcertificates'] = 'Delete issued certificates';
$string['nocertificatesissued'] = 'There are no certificates that have been issued';

//Form
$string['certificatename'] = 'Certificate Name';
$string['certificateimage'] = 'Certificate Image File';
$string['certificatetext'] = 'Certificate Text';
$string['certificatetextx'] = 'Certificate Text Horizontal Position';
$string['certificatetexty'] = 'Certificate Text Vertical Position';
$string['height'] = 'Certificate Height';
$string['width'] = 'Certificate Width';
$string['coursehours'] = 'Hours in course';
$string['coursename'] = 'Alternative Course Name';
$string['intro'] = 'Introduction';
$string['printoutcome'] = 'Print Outcome';
$string['printdate'] = 'Print Date';

//Second Page
$string['secondpageoptions'] = 'Certificate Back page';
$string['enablesecondpage'] = 'Enable Certificate Back page';
$string['enablesecondpage_help'] = 'Enable Certificate Back page edition, if is disabled, only certificate QR code will be printed in back page (if the QR code is enabled)';
$string['secondimage'] = 'Certificate Back Image file';
$string['secondimage_help'] = 'This is the picture that will be used in the back of certificate';
$string['secondpagetext'] = 'Certificate Back Text';
$string['secondpagetext_help'] = 'This is the text that will be used in the certificate back, some special words will be replaced with variables such as course name, student\'s name, grade ...
These are:

{USERNAME} -> Full user name

{COURSENAME} -> Full course name (or a Defined alternate course name)

{GRADE} -> Formated Grade

{DATE} -> Formated Date

{OUTCOME} -> Outcomes

{HOURS} -> Defined hours in course

{TEACHERS} -> Teachers List


The text can use basic html, basic fonts, tables,  but avoid any position definition';
$string['secondpagex'] = 'Certificate Back Text Horizontal Position';
$string['secondpagey'] = 'Certificate Back Text Vertical Position';
$string['secondtextposition'] = 'Certificate Back Text Position';
$string['secondtextposition_help'] = 'These are the XY coordinates (in millimeters) of the certificate back page text';

//QR Code
$string['disablecode'] = 'Disable Certificate QR Code';
$string['disablecode_help'] = 'Disable/enable certificate QR Code generation';
$string['codex'] = 'Certificate QR Code Horizontal Position';
$string['codey'] = 'Certificate QR Code Vertical Position';
$string['qrcodeposition'] = 'Certificate QR Code Position';
$string['qrcodeposition_help'] = 'These are the XY coordinates (in millimeters) of the certificate QR Code';
$string['defaultcodex'] = 'Default Hotizontal QR code Position';
$string['defaultcodey'] = 'Default Vertical QR code Position';

////Date options
$string['issueddate'] = 'Date Issued';
$string['completiondate'] = 'Course Completion';
$string['datefmt'] = 'Date Format';

////Date format options
$string['userdateformat'] = 'User\'s Language Date Format';

$string['printgrade'] = 'Print Grade';
$string['gradefmt'] = 'Grade Format';
////Grade format options
$string['gradeletter'] = 'Letter Grade';
$string['gradepercent'] = 'Percentage Grade';
$string['gradepoints'] = 'Points Grade';
$string['coursetimereq'] = 'Required minutes in course';
$string['emailteachers'] = 'Email Teachers';
$string['emailothers'] = 'Email Others';
$string['emailfrom'] = 'Email From name';
$string['savecert'] = 'Save Certificates';
$string['delivery'] = 'Delivery';
//Delivery options
$string['openbrowser'] = 'Open in new window';
$string['download'] = 'Force download';
$string['emailcertificate'] = 'Email (Must also choose save!)';



////Form options help text

$string['certificatename_help'] = 'Certificate Name';
$string['certificatetext_help'] = 'This is the text that will be used in the certificate, some special words will be replaced with variables such as course name, student\'s name, grade ...
These are:

{USERNAME} -> Full user name

{COURSENAME} -> Full course name (or a Defined alternate course name)

{GRADE} -> Formated Grade

{DATE} -> Formated Date

{OUTCOME} -> Outcomes

{HOURS} -> Defined hours in course

{TEACHERS} -> Teachers List


The text can use basic html, basic fonts, tables,  but avoid any position definition';

$string['textposition'] = 'Certificate Text Position';
$string['textposition_help'] = 'These are the XY coordinates (in millimeters) of the certificate text';
$string['size'] = 'Certificate Size';
$string['size_help'] = 'These are the Width and Height size (in millimeters) of the certificate, Default size is A4 Landscape';
$string['coursehours_help'] = 'Hours in course';
$string['coursename_help'] = 'Alternative Course Name';
$string['certificateimage_help'] = 'This is the picture that will be used in the certificate';

$string['printoutcome_help'] = 'You can choose any course outcome to print the name of the outcome and the user\'s received outcome on the certificate.  An example might be: Assignment Outcome: Proficient.';
$string['printdate_help'] = 'This is the date that will be printed, if a print date is selected. If the course completion date is selected but the student has not completed the course, the date received will be printed. You can also choose to print the date based on when an activity was graded. If a certificate is issued before that activity is graded, the date received will be printed.';
$string['datefmt_help'] = 'Enter a valid PHP date format pattern (http://www.php.net/manual/en/function.strftime.php). Or, leave it empty to use the format of the user\'s chosen language.';
$string['printgrade_help'] = 'You can choose any available course grade items from the gradebook to print the user\'s grade received for that item on the certificate.  The grade items are listed in the order in which they appear in the gradebook. Choose the format of the grade below.';
$string['gradefmt_help'] = 'There are three available formats if you choose to print a grade on the certificate:

Percentage Grade: Prints the grade as a percentage.
Points Grade: Prints the point value of the grade.
Letter Grade: Prints the percentage grade as a letter.';

$string['coursetimereq_help'] = 'Enter here the minimum amount of time, in minutes, that a student must be logged into the course before they will be able to receive the certificate.';
$string['emailteachers_help'] = 'If enabled, then teachers are alerted with an email whenever students receive a certificate.';
$string['emailothers_help'] = 'Enter the email addresses here, separated by a comma, of those who should be alerted with an email whenever students receive a certificate.';
$string['emailfrom_help'] = 'Alternate email form name';
$string['savecert_help'] = 'If you choose this option, then a copy of each user\'s certificate pdf file is saved in the course files moddata folder for that certificate. A link to each user\'s saved certificate will be displayed in the certificate report.';
$string['delivery_help'] = 'Choose here how you would like your students to get their certificate.
Open in Browser: Opens the certificate in a new browser window.
Force Download: Opens the browser file download window.
Email Certificate: Choosing this option sends the certificate to the student as an email attachment.
After a user receives their certificate, if they click on the certificate link from the course homepage, they will see the date they received their certificate and will be able to review their received certificate.';

////Form Sections
$string['issueoptions'] = 'Issue Options';
$string['designoptions'] = 'Design Options';

//Emails text
$string['emailstudenttext'] = 'Attached is your certificate for {$a->course}.';
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



//Admin settings page
$string['defaultwidth'] = 'Default Width';
$string['defaultheight'] = 'Default Height';
$string['defaultcertificatetextx'] = 'Default Hotizontal Text Position';
$string['defaultcertificatetexty'] = 'Default Vertical Text Position';


//Erros
$string['filenotfound'] = 'File not Found: {$a}';
$string['invalidcode'] = 'Invalid certificate code';
$string['cantdeleteissue'] = 'Error removing issued certificates';


//Verify certificate page
$string['certificateverification'] = 'Certificate Verification';

//Settings
$string['certlifetime'] = 'Keep issued certificates for: (in Months)';
$string['certlifetime_help'] = 'This specifies the length of time you want to keep issued certificates. Issed certificates that are older than this age are automatically deleted.';
$string['neverdeleteoption'] = 'Never delete';

$string['variablesoptions'] = 'Others Options';
$string['getcertificate'] = 'Verify Certificate';
