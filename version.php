<?php

/**
 * Code fragment to define the version of the simplecertificate module
 * 
 * @package mod
 * @subpackage simplecertificate
 * @author Carlos Alexandre S. da Fonseca
 * @copyright ® Carlos Alexandre S. da Fonseca - 2014
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

$module->version = 2014031000; // The current module version (Date: YYYYMMDDXX)
$module->requires = 2013101800; // Requires this Moodle version (moodle 2.5.x)
$module->cron = 4 * 3600; // Period for cron to check this module (secs)
$module->component = 'mod_simplecertificate';
$module->release = '2.1.3'; // Human-friendly version name
// MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE
$module->maturity = MATURITY_STABLE;