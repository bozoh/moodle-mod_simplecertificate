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
    // Default formatters.
    public const LOWER_CASE_FORMATTER = 'lcase'; // It's uses strtolower.
    public const UPPER_CASE_FORMATTER = 'ucase'; // It's uses strtoupper.
    public const UPPER_CASE_FIRST_FORMATTER = 'ucasefirst'; // It's uses ucwords.


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
     * Should return all textmarks of the plugin.
     *
     * @return array - array of textmarks
     */
    // public function get_textmarks() {
    //     $names = $this->get_names();
    //     $attrs = (array)$this->get_attributes();
    //     $attrs[] = '';
    //     $fmts = (array)$this->get_formatters();
    //     $fmts[] = '';

    //     $textmarks = array();

    //     foreach ($names as $name) {
    //         foreach ($attrs as $attr) {
    //             foreach ($fmts as $fmt) {
    //                 $tm = $this->is_valid_textmark($name, $attr, $fmt);
    //                 if ($tm !== null) {
    //                     $textmarks[] = $tm;
    //                 }
    //             }
    //         }
    //     }
    //     return $textmarks;
    // }

    protected function get_textmark_formated_text($name, $attribute = null, $formatter = null) {
        if (empty($name)) {
            //TODO improve errors msg
            print_error('invalid_textmark_name');
        }
        $tmstr = '{' . strtoupper($name);

        if (!empty($attribute)) {
            $tmstr .= ':' . strtolower($attribute);
        }

        if (!empty($formatter)) {
            $tmstr .= ':' . strtolower($formatter);
        }
        return $tmstr . '}';
    }


    /**
     * Should return all textmarks names.
     *
     * @return array - array of textmarks names
     */
    public abstract function get_names();

    /**
     * Should return all attributes for this plugin.
     *
     * @return array - array of allowed attributes
     */
    protected abstract function get_attributes();

    /**
     * Should return all allowed formatters for this plugin.
     *
     * @return array - array of allowed formatters
     */
    protected function get_formatters() {
        return array(
            self::LOWER_CASE_FORMATTER,
            self::UPPER_CASE_FORMATTER,
            self::UPPER_CASE_FIRST_FORMATTER
        );
    }

    /**
     * Check if a textmark is valid for this plugin
     *
     * @return string - The textmark if is valid, or null if is not
     * @throws simplecertificate_textmark_plugin_exception - if textmark is not from this plugin
     */
    protected function is_valid_textmark($name, $attribute = null, $formatter = null) {
        if (empty($name)) {
            //TODO improve errors msg
            print_error('invalid_textmark_name');
        }

        if (!in_array($name, $this->get_names()) &&
            (!empty($attribute) && !in_array($attribute, $this->get_attributes())) &&
            (!empty($formatter) && !in_array($formatter, $this->get_formatters()))
        ) {
            //TODO improve errors msg
            throw new simplecertificate_textmark_plugin_exception('TODO');
        }
    }

    /**
     * Should return the parsed text.
     *
     * @param string $text Text with textmarks, to be parsed.
     * @return string The parsed text.
     */
    public function get_text($text = null) {
        $names = $this->get_names();
        $attrs = (array)$this->get_attributes();
        $attrs[] = '';
        $fmts = (array)$this->get_formatters();
        $fmts[] = '';

        $search = array();
        $replace = array();

        foreach ($names as $name) {
            foreach ($attrs as $attr) {
                foreach ($fmts as $fmt) {
                    $tm = $this->is_valid_textmark($name, $attr, $fmt);
                    if (empty($tm)) {
                        continue;
                    }
                    $search[] = $tm;
                    $replace[] = (string)$this->get_replace_text($name, $attr, $fmt);
                }
            }
        }
        return str_replace($search, $replace, $text);
    }

    /**
     * Get the textmark value, to be parsed
     *
     * @param string $textmark A textmark
     * @return string Textmark value, to be used in text
     */
    protected abstract function get_replace_text($name, $attribute = null, $formatter = null);

    /**
     * Should return if this plugin is enable or not.
     *
     * @return boolean - true if enabled
     */
    // TODO tirar o abstract, usar um código padrão
    public abstract function is_enabled();

    /**
     * Get the settings for textmark plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
    }
}

class simplecertificate_textmark_plugin_exception extends moodle_exception {

}
