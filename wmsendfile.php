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
 * Add Watermark and send files
 *
 * @package mod
 * @subpackage simplecertificate
 * @copyright 2014 © Carlos Alexandre Soares da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// ... $id = required_param('id', PARAM_INTEGER); // Issed Code.
// ... $sk = required_param('sk', PARAM_RAW); // sesskey.
$code = required_param('code', PARAM_TEXT); // Issued Code.


if (!$issuedcert = $DB->get_record("simplecertificate_issues", array('code' => $code))) {
    print_error(get_string('issuedcertificatenotfound', 'simplecertificate'));
}
watermark_and_sent($issuedcert);

function watermark_and_sent(stdClass $issuedcert) {
    global $CFG, $USER, $COURSE, $DB, $PAGE;

    if ($issuedcert->haschange) {
        // This issue have a haschange flag, try to reissue.
        if (empty($issuedcert->timedeleted)) {
            require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');
            try {
                // Try to get cm.
                $cm = get_coursemodule_from_instance('simplecertificate', $issuedcert->certificateid, 0, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                // Must set a page context to issue .
                $PAGE->set_context($context);
                $simplecertificate = new simplecertificate($context, null, null);
                $file = $simplecertificate->get_issue_file($issuedcert);

            } catch (moodle_exception $e) {
                // Only debug, no errors.
                debugging($e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
            }
        } else {
            // Have haschange and timedeleted, somehting wrong, it will be impossible to reissue
            // add wraning.
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
        require_once($CFG->libdir . '/pdflib.php');
        require_once($CFG->dirroot . '/mod/simplecertificate/lib/fpdi/fpdi.php');

        // Copy to a tmp file.
        $tmpfile = $file->copy_content_to_temp();

        // TCPF doesn't import files yet, so i must use FPDI.
        $pdf = new FPDI();
        $pagecount = $pdf->setSourceFile($tmpfile);

        for ($pgnum = 1; $pgnum <= $pagecount; $pgnum++) {
            // Import a page.
            $templateid = $pdf->importPage($pgnum);
            // Get the size of the imported page.
            $size = $pdf->getTemplateSize($templateid);

            // Create a page (landscape or portrait depending on the imported page size).
            if ($size['w'] > $size['h']) {
                $pdf->AddPage('L', array($size['w'], $size['h']));
                // Font size 1/3 Height if it landscape.
                $fontsize = $size['h'] / 3;
            } else {
                $pdf->AddPage('P', array($size['w'], $size['h']));
                // Font size 1/3 Width if it portrait.
                $fontsize = $size['w'] / 3;
            }

            // Use the imported page.
            $pdf->useTemplate($templateid);

            // Calculating the rotation angle.
            $rotangle = (atan($size['h'] / $size['w']) * 180) / pi();
            // Find the middle of the page to use as a pivot at rotation.
            $mdlx = ($size['w'] / 2);
            $mdly = ($size['h'] / 2);

            // Set the transparency of the text to really light.
            $pdf->SetAlpha(0.25);

            $pdf->StartTransform();
            $pdf->Rotate($rotangle, $$mdlx, $$mdly);
            $pdf->SetFont("freesans", "B", $fontsize);

            $pdf->SetXY(0, $$mdly);
            $bodersytle = array('LTRB' => array('width' => 2, 'dash' => $fontsize / 5,
                                        'cap' => 'round',
                                        'join' => 'round',
                                        'phase' => $fontsize / $$mdlx)
            );

            $pdf->Cell($size['w'], $fontsize, get_string('certificatecopy', 'simplecertificate'), $bbodersytle, 0, 'C', false, '',
                    4, true, 'C', 'C');
            $pdf->StopTransform();

            // Reset the transparency to default.
            $pdf->SetAlpha(1);

        }
        // Set protection seems not work, so comment.
        // $pdf->SetProtection(array('print', 'modify', 'print-high'),null, random_string(), '1',null);.

        // For DEGUG
        // $pdf->Output($file->get_filename(), 'I');.

        // Save and send tmpfiles.
        $pdf->Output($tmpfile, 'F');
        send_temp_file($tmpfile, $file->get_filename());
    }
}