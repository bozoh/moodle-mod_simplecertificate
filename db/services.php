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
 * Forum external functions and service definitions.
 *
 * @package    mod_simplecertificate
 * @copyright  2014 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_simplecertificate_verify_code' => array(
        'classname' => 'mod_simplecertificate_external',
        'methodname' => 'verify_code',
        'classpath' => 'mod/simplecertificate/externallib.php',
        'description' => 'Returns certificate owner username if code is valid',
        'type' => 'read',
        'capabilities' => ''
    )
);

$services = array(
        'Simplecertificate plugin webservices' => array(
                'functions' => array ('mod_simplecertificate_verify_code'),
                'restrictedusers' => 0, // ...if 1, the administrator must manually select which user can use this service.
                // ... (Administration > Plugins > Web services > Manage services > Authorised users).
                'enabled' => 0, // ... if 0, then token linked to this service won't work.
                'shortname' => 'mod_simplecertificate_ws'
        )
);