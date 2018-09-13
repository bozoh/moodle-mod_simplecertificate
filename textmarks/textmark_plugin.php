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
 * This file contains the definition for the abstract class for submission_plugin
 *
 * This class provides all the functionality for textmark plugins.
 *
 * @package   mod_simplecertificate
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

/**
 * Abstract base class for textmark plugin types.
 *
 * @package   mod_simplecertificate
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class simplecertificate_textmark_plugin {
    protected $smplcert;

    /**
     * Constructor for the abstract plugin type class
     *
     * @param simplecertificate $smplcert
     *
     */
    public final function __construct(simplecertificate $smplcert) {
        $this->smplcert = $smplcert;
    }


    /**
     * Should return the name of this plugin.
     *
     * @return string - the name
     */
    public abstract function get_name();

    /**
     * Should return the type of this plugin.
     *
     * @return string - the type
     */
    public abstract function get_type();


    /**
     * Should return if this plugin is enable or not.
     *
     * @return boolean - true if enabled
     */
    // TODO tirar o abstract, usar um código padrão
    public abstract function is_enabled();


    /**
     * Should return the parsed certificate text
     *
     * @return string parsed certificar text
     */
    public abstract function get_text();

    /**
     * Get the settings for textmark plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
    }
}
