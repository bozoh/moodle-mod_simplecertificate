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
    And the following "activities" exist:
      | activity           | name                    | course | idnumber    | section |
      | simplecertificate  | Test Simple Certificate | C1     | cert1       | 1       |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course Verify"
    And I turn editing mode on
	And I issue a "Test Simple Certificate" to "student1"
	And I log out

  Scenario: Verify certificate authenticity by code
    Given I am on "certificate verification page"
    And I set "student1" certificate "Test Simple Certificate" code
    And I press "Verify Certificate"
    Then I should see "Tumé Arandú"
    And I should see the "student1" certificate "Test Simple Certificate" code
    And I should see "Course Verify"
