@mod @mod_simplecertificate @verify_simplecertificate_code
Feature: Verify certificate  authenticity by code, in verification page
  In order to verify the authenticity 
  As a any user (no need to be logged)
  I need to issue a certificate, get the code, and verify it at 
  verification page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Tumé      | Arandú   | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course Verify | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course Verify"
    And I turn editing mode on
    And I add a "Simple Certificate" to section "1" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
	And I issue a "Test Simple Certificate" to "student1"
	And I log out

  Scenario: Verify certificate authenticity by code
    Given I am on "certificate verification page"
    And I set "student1" certificate "Test Simple Certificate" code 
    And I press "Verify Certificate"
    Then I should see "Tumé Arandú"
    And I should see the "student1" certificate "Test Simple Certificate" code
    And I should see "Course Verify"
    #But "Tupã Xingú" "text" should not exist in the ".generaltable" "css_element" 
	