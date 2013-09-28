<?php

/**
 * Code fragment to define the version of the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>, Mark Nelson <mark@moodle.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

$module->version  = 2013092700; // The current module version (Date: YYYYMMDDXX)
$module->requires = 2011120500;  // Requires this Moodle version
$module->cron     = 4 * 3600;    // Period for cron to check this module (secs)
$module->release  = '2.1.1';       // Human-friendly version name
//MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE
$module->maturity = MATURITY_STABLE;