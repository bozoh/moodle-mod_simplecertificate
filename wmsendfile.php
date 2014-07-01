<?php
/**
 * Watermark and send files
 * 
 * @package mod
 * @subpackage simplecertificate
 * @copyright 2014 © Carlos Alexandre Soares da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

$id = required_param('id', PARAM_INTEGER); // Issed Code
$sk = required_param('sk', PARAM_RAW); // sesskey

if (confirm_sesskey($sk)) {
    if (!$issuedcert = $DB->get_record("simplecertificate_issues", array('id' => $id))) {
        print_error(get_string('issuedcertificatenotfound', 'simplecertificate'));
    }
    watermark_and_sent($issuedcert);
} else {
    print_error('invalidsesskey');
}

function watermark_and_sent(stdClass $issuedcert) {
    global $CFG, $USER, $COURSE, $DB, $PAGE;
    
    if ($issuedcert->haschange) {
        //This issue have a haschange flag, try to reissue
        if (empty($issuedcert->timedeleted)) {
            require_once ($CFG->dirroot . '/mod/simplecertificate/locallib.php');
            try {
                // Try to get cm
                $cm = get_coursemodule_from_instance('simplecertificate', $issuedcert->certificateid, 0, false, MUST_EXIST);
                $context = context_module::instance($cm->id);
                
                //Must set a page context to issue .... 
                $PAGE->set_context($context);
                $simplecertificate = new simplecertificate($context, null, null);
                $file = $simplecertificate->get_issue_file($issuedcert);
            
            } catch (moodle_exception $e) {
                // Only debug, no errors
                debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
            }
        } else {
            //Have haschange and timedeleted, somehting wrong, it will be impossible to reissue
            //add wraning
            debugging("issued certificate [$issuedcert->id], have haschange and timedeleted");
        }
        $issuedcert->haschange = 0;
        $DB->update_record('simplecertificate_issues', $issuedcert);
    }
    
    if (empty($file)) {
        $fs = get_file_storage();
        if (!$fs->file_exists_by_hash($issuedcert->pathnamehash)) {
            print_error(get_string('filenotfound', 'simplecertificate', ''));
        }
        
        $file = $fs->get_file_by_hash($issuedcert->pathnamehash);
    }
    
    $canmanage = false;
    if (!empty($COURSE)) {
        $canmanage = has_capability('mod/simplecertificate:manage', context_course::instance($COURSE->id));
    }
    
    if ($canmanage || (!empty($USER) && $USER->id == $issuedcert->userid)) {
        send_stored_file($file, 0, 0, true);
    } else {
        require_once ($CFG->libdir . '/pdflib.php');
        require_once ($CFG->dirroot . '/mod/simplecertificate/lib/fpdi/fpdi.php');
        
        // copy to a tmp file
        $tmpfile = $file->copy_content_to_temp();
        
        // TCPF doesn't import files yet, so i must use FPDI
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
                // Font size 1/3 Height if it landscape
                $fontsize = $size['h'] / 3;
            } else {
                $pdf->AddPage('P', array($size['w'], $size['h']));
                // Font size 1/3 Width if it portrait
                $fontsize = $size['w'] / 3;
            }
            
            // use the imported page
            $pdf->useTemplate($templateId);
            
            // Calculating the rotation angle
            $rotAngle = (atan($size['h'] / $size['w']) * 180) / pi();
            // Find the middle of the page to use as a pivot at rotation.
            $mX = ($size['w'] / 2);
            $mY = ($size['h'] / 2);
            
            // Set the transparency of the text to really light
            $pdf->SetAlpha(0.25);
            
            $pdf->StartTransform();
            $pdf->Rotate($rotAngle, $mX, $mY);
            $pdf->SetFont("freesans", "B", $fontsize);
            
            $pdf->SetXY(0, $mY);
            $boder_style = array(
                    'LTRB' => array('width' => 2, 'dash' => $fontsize / 5, 'cap' => 'round', 'join' => 'round', 
                            'phase' => $fontsize / $mX));
            
            $pdf->Cell($size['w'], $fontsize, get_string('certificatecopy', 'simplecertificate'), $boder_style, 0, 'C', false, '', 
                    4, true, 'C', 'C');
            $pdf->StopTransform();
            
            // Reset the transparency to default
            $pdf->SetAlpha(1);
        
        }
        // Set protection seems not work, so comment
        // $pdf->SetProtection(array('print', 'modify', 'print-high'),null, random_string(), '1',null);
        
        // For DEGUG
        // $pdf->Output($file->get_filename(), 'I');
        
        // Save and send tmpfiles
        $pdf->Output($tmpfile, 'F');
        send_temp_file($tmpfile, $file->get_filename());
    }
}