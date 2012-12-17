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

    $settings->add(new admin_setting_configcheckbox('simplecertificate/savecert',
        get_string('savecert', 'simplecertificate'), get_string('savecert_help', 'simplecertificate'), 1));

    $settings->add(new admin_setting_configselect('simplecertificate/certdate', get_string('printdate', 'simplecertificate'),
        get_string('printdate_help', 'simplecertificate'), 2, simplecertificate_get_date_options()));

    $settings->add(new admin_setting_configselect('simplecertificate/certdatefmt', get_string('datefmt', 'simplecertificate'),
        get_string('datefmt_help', 'simplecertificate'), 5, simplecertificate_get_date_format_options()));

    $settings->add(new admin_setting_configtext('simplecertificate/certlifetime', get_string('certlifetime', 'simplecertificate'),
        get_string('certlifetime_help', 'simplecertificate'), 60, PARAM_INT));


}

?>