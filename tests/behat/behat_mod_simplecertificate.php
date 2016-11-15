<?php

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ElementException;
use Behat\Mink\Exception\Exception;
use Behat\Mink\Exception\ExpectationException;

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Basic simplecertificate steps definitions.
 * 
 * @package mod_simplecertificate
 * @category test
 * @copyright 2016 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once (__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Log in log out steps definitions.
 * 
 * @package mod_simplecertificate
 * @category test
 * @copyright 2016 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_simplecertificate extends behat_base {
    // Just a memo note
    // $this->execute('behat_forms::i_set_the_field_to', array('Username', $this->escape($username)));
    // $this->execute('behat_forms::i_set_the_field_to', array('Password', $this->escape($username)));
    
    /**
     * Verify if an new windows is created
     * @Then /^I should see a pop-up window$/
     * 
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $button
     */
    public function should_see_popup() {
        $windowNames = $this->getSession()->getWindowNames();
        if (count($windowNames) < 2) {
            throw new ElementNotFoundException();
        }
    }

    /**
     * For some reason I set don't work when i try to enable access restringtion
     * using xpath to slove the problem
     * @Given /^I select "(?P<option_string>(?:[^"]|\\")*)" option from the "(?P<select_string>(?:[^"]|\\")*)"$/
     * 
     * @param unknown $option
     * @param unknown $select
     */
    public function select_option_from($option, $select) {
        // //select[@name="id"]/option
        // $xpath = '//select[@name="'.$this->escape($select).'"]/option';
        $xpath = '//select[@name="' . $this->escape($select) . '"]';
        $selectElement = $this->find('xpath', $xpath);
        try {
            $selectElement->selectOption($this->escape($option));
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException($e->getMessage() . "\nField Options = " . $selectElement->getText(), 
                                        $this->getSession()->getDriver(), $e);
        }
    
    }

    /**
     * Add grade restriction to an activity
     * @Given /^I put a grade restrinction to "(?P<activity_string>(?:[^"]|\\")*)" with "(?P<grade_activity_string>(?:[^"]|\\")*)" min grade "(?P<mingrade_string>(?:[^"]|\\")*)"$/
     * 
     * @param unknown $activity
     * @param unknown $mingrade
     */
    public function add_grade_restriction($activity, $grade_activity, $mingrade) {
        //I follow
        $this->execute('behat_general::click_link', array($this->escape($activity)));
        $this->execute('behat_navigation::i_navigate_to_node_in', array("Edit settings", "Certificate administration"));
        $this->execute('behat_forms::i_expand_all_fieldsets');
        $this->execute('behat_general::i_click_on', array("Add restriction...", "button"));
        $this->execute('behat_general::i_click_on_in_the', array("Grade", "button", "Add restriction...", "dialogue"));
        $this->execute('behat_general::i_click_on', array("min", "checkbox"));
        $this->select_option_from($this->escape($grade_activity), 'id');
        $this->execute('behat_forms::i_set_the_field_to', array('minval', $this->escape($mingrade)));
        $this->execute('behat_forms::press_button', array("Save and return to course"));
    }

}
