<?php

/**
 * Provides some custom settings for the certificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->dirroot/mod/simplecertificate/lib.php");

    //--- general settings -----------------------------------------------------------------------------------

    $settings->add(new admin_setting_configtext('simplecertificate/width', get_string('defaultwidth', 'simplecertificate'),
        get_string('size_help', 'simplecertificate'), 297, PARAM_INT));
    $settings->add(new admin_setting_configtext('simplecertificate/height', get_string('defaultheight', 'simplecertificate'),
        get_string('size_help', 'simplecertificate'), 210, PARAM_INT));

    $settings->add(new admin_setting_configtext('simplecertificate/certificatetextx', get_string('defaultcertificatetextx', 'simplecertificate'),
        get_string('textposition_help', 'simplecertificate'), 50, PARAM_INT));
    $settings->add(new admin_setting_configtext('simplecertificate/certificatetexty', get_string('defaultcertificatetexty', 'simplecertificate'),
        get_string('textposition_help', 'simplecertificate'), 50, PARAM_INT));

    $settings->add(new admin_setting_configselect('simplecertificate/certdate', get_string('printdate', 'simplecertificate'),
        get_string('printdate_help', 'simplecertificate'), -2, simplecertificate_get_date_options()));

    // Setting to include teachers in certificate completion emails
    $settings->add(new admin_setting_configcheckbox('simplecertificate/cert2teacher', get_string('cert2teacher', 'simplecertificate'),
		get_string('cert2teacher_help', 'simplecertificate'), 1));

    $settings->add(new admin_setting_configtext('simplecertificate/certlifetime', get_string('certlifetime', 'simplecertificate'),
        get_string('certlifetime_help', 'simplecertificate'), 60, PARAM_INT));
    
    //QR CODE
    $settings->add(new admin_setting_configcheckbox('simplecertificate/printqrcode',
        get_string('printqrcode', 'simplecertificate'), get_string('printqrcode_help', 'simplecertificate'), 1));
    $settings->add(new admin_setting_configtext('simplecertificate/codex', get_string('defaultcodex', 'simplecertificate'),
        get_string('qrcodeposition_help', 'simplecertificate'), 10, PARAM_INT));
    $settings->add(new admin_setting_configtext('simplecertificate/codey', get_string('defaultcodey', 'simplecertificate'),
        get_string('qrcodeposition_help', 'simplecertificate'), 10, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('simplecertificate/qrcodefirstpage',
            get_string('qrcodefirstpage', 'simplecertificate'), get_string('qrcodefirstpage_help', 'simplecertificate'), 0));

	//Certificate back page
    $settings->add(new admin_setting_configcheckbox('simplecertificate/enablesecondpage',
    		get_string('enablesecondpage', 'simplecertificate'), get_string('enablesecondpage_help', 'simplecertificate'), 0));
    
    //Pagination
    $settings->add(new admin_setting_configtext('simplecertificate/perpage', get_string('defaultperpage', 'simplecertificate'),
    		get_string('defaultperpage_help', 'simplecertificate'), 30, PARAM_INT));
    
       
}

?>
