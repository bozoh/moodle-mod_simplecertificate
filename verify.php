<?php

/**
 * Verify an issued certificate by code
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('verify_form.php');
require_once('lib.php');

//optional_param('id', $USER->id, PARAM_INT);
$code = optional_param('code', null,PARAM_ALPHANUMEXT); // Issed Code
$sk = optional_param('sk', null, PARAM_RAW);  // sesskey


if (!empty($sk) && confirm_sesskey($sk)) {
	$issuedcert = get_issued_cert($code);
	require_once ($CFG->libdir . '/filelib.php');
	
	//Getting file
	$fs = get_file_storage();
	if (!$fs->file_exists_by_hash($issuedcert->pathnamehash)) {
		
	}
	$file = $fs->get_file_by_hash($issuedcert->pathnamehash);
	
	//verifing if need to add watermark
	//Verify if user is the same (or has permission do get the certificate)
	if (!empty($USER)) {
		if ($USER->id == $issuedcert->userid) {
			send_stored_file($file, 0, 0, true);
			return;
		}
	}
	watermark_and_sent($file);

} else {

	$context = context_system::instance();
	$PAGE->set_url('/mod/simplecertificate/verify.php', array('code' => $code));
	$PAGE->set_context($context);
	$PAGE->set_title(get_string('certificateverification', 'simplecertificate'));
	$PAGE->set_heading(get_string('certificateverification', 'simplecertificate'));
	$PAGE->set_pagelayout('base');
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('certificateverification', 'simplecertificate'));

	$verifyform = new verify_form();

	if (!$verifyform->get_data()) {
    	if ($code)
        	$verifyform->set_data(array('code'=>$code));
    
    	$verifyform->display();

	} else {
		$issuedcert = get_issued_cert($code);

    	if ($user = $DB->get_record('user', array('id'=>$issuedcert->userid))) {
        	$username = fullname($user);
    	} else {
        	$username = get_string('notavailable');
    	}
    
    	$strto = get_string('awardedto', 'simplecertificate');
    	$strdate = get_string('issueddate', 'simplecertificate');
    	$strcode = get_string('code', 'simplecertificate');
    
	    //Add to log
    	add_to_log($context->instanceid, 'simplecertificate', 'verify', "verify.php?code=$code", '$issuedcert->id');

    	$table = new html_table();
    	$table->width = "95%";
    	$table->tablealign = "center";
  		$table->head  = array(get_string('course'), $strto, $strdate, $strcode);
   		$table->align = array("left", "left", "center", "center");
   		$table->data[] = array ($issuedcert->coursename, $username, userdate($issuedcert->timecreated).
   	        	simplecertificate_print_issue_certificate_file($issuedcert), $issuedcert->code);
    	echo html_writer::table($table);
	}

	echo $OUTPUT->footer();
}

function get_issued_cert($code=null) {
	global $DB;
	
	if (!$issuedcert = $DB->get_record("simplecertificate_issues", array('code' => $code))) {
		print_error(get_string('invalidcode','simplecertificate'));
	}
	return 	$issuedcert;
}

function watermark_and_sent($file) {
	global $CFG;
	
	require_once($CFG->libdir.'/pdflib.php');
	//require_once($CFG->libdir . '/filelib.php');
	require_once($CFG->dirroot.'/mod/simplecertificate/lib/fpdi/fpdi.php');
	
	//copy to a tmp file 
	$tmpfile = $file->copy_content_to_temp();
	
	//TCPF doesn't import files yet, so i must use FPDI
	$pdf = new FPDI();
	$pageCount = $pdf->setSourceFile($tmpfile);
	
	for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
		// import a page
		$templateId = $pdf->importPage($pageNo);
		// get the size of the imported page
		$size = $pdf->getTemplateSize($templateId);
	
		// create a page (landscape or portrait depending on the imported page size)
		if ($size['w'] > $size['h']) {
			$pdf->AddPage('L', array($size['w'], $size['h']));
			//Font size 1/3 Height if it landscape
			$fontsize = $size['h'] / 3 ;
		} else {
			$pdf->AddPage('P', array($size['w'], $size['h']));
			//Font size 1/3 Width if it portrait
			$fontsize = $size['w'] / 3 ;
		}
	
		// use the imported page
		$pdf->useTemplate($templateId);
			
		//Calculating the rotation angle
		$rotAngle = (atan($size['h'] / $size['w'] ) * 180) / pi();
		// Find the middle of the page to use as a pivot at rotation.
		$mX = ($size['w']  / 2);
		$mY = ($size['h'] / 2);
	
		// Set the transparency of the text to really light
		$pdf->SetAlpha(0.25);
	
		$pdf->StartTransform();
		$pdf->Rotate($rotAngle, $mX, $mY);
		$pdf->SetFont("freesans", "B", $fontsize);
	
		$pdf->SetXY(0, $mY);
		$boder_style = array (
				'LTRB' => array (
						'width' => 2,
						'dash' => $fontsize / 5 ,
						'cap' => 'round',
						'join' => 'round',
						'phase' =>  $fontsize / $mX
				)
		);
	
		$pdf->Cell($size['w'], $fontsize, get_string('certificatecopy','simplecertificate'),$boder_style, 0, 'C', false, '', 4, true, 'C', 'C');
		$pdf->StopTransform();
	
		// Reset the transparency to default
		$pdf->SetAlpha(1);
	
	}
	//Set protection seems not work, so comment
	//$pdf->SetProtection(array('print', 'modify', 'print-high'),null, random_string(), '1',null);
	
	//For DEGUG
	//$pdf->Output($file->get_filename(), 'I');
	
	//Save and send tmpfiles
	$pdf->Output($tmpfile, 'F');
 	send_temp_file($tmpfile, $file->get_filename());
}
	
