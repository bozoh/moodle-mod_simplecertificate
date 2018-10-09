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
 * Tests tools for textmarks tests
 *
 * @package   simplecertificatetextmark_profile
 * @copyright 2018 Carlos Alexandre S. da Fonseca
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for mod/simplecertificate/textmarks/profile/locallib.php
 *
 * @copyright  2018 Carlos Alexandre S. da Fonseca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait simplecertificatetextmark_test_tools {

    // ================= DATA PROVIDERS ================

    /**
     * Dataprovider returns all valid textmarks
     * testcase
     *
     * @return array of testcases
     */
    public function get_valid_plugins_textmarks($filename) {
        $textmarks = $this->get_all_plugins_textmarks($filename);
        $testcases = array();
        foreach ($textmarks as $tm => [$name, $attr, $fmt, $valid]) {
            if ($valid) {
                $testcases[$tm] = [$name, $attr, $fmt, $valid];
            }
        }
        return $testcases;
    }

    /**
     * Dataprovider returns all textmarks
     * testcase
     *
     * @return array of testcases
     */
    public function get_all_plugins_textmarks($filename) {
        $dataset = $this->createCsvDataSet(array(
            'textmarks' => $filename
        ));

        $testcases = array ();
        $table = $dataset->getTable('textmarks');
        for ($r = 0; $r < $table->getRowCount(); $r++) {
            $record = (object)$table->getRow($r);
            // textmark,name,attribute,formatter,expected
            $testcases[$record->textmark] = [
                $record->name,
                $record->attribute,
                $record->formatter,
                $record->expected == 'true'
            ];
        }

        return $testcases;
    }
}