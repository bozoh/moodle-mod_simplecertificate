<?php

/**
 * Code fragment to define the version of the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Fonseca <carlos.alexandre@outlook.com>, Mark Nelson <mark@moodle.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

$module->version  = 2013052800;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2010112400;  // Requires this Moodle version
$module->cron     = 4 * 3600;    // Period for cron to check this module (secs)
$module->release  = '1.0.3';       // Human-friendly version name
$module->maturity = MATURITY_STABLE;