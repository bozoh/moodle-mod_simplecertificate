@mod @mod_simplecertificate @issued_tab_simplecertificate
Feature: List issued Certificates
  In order to list issued certificates
  As a teacher
  I need to create a certificate and students issue then

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tupã      | Xingú    | teacher1@example.com |
      | student1 | Tumé      | Arandú   | student1@example.com |
      | student2 | Arasy     | Guaraní  | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Simple Certificate" to section "1" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
	And I log out

  Scenario: Verify if issued certificates are displyed
    Given I log in as "student1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I press "Get Certificate"
    And I am on site homepage
    And I log out
    Then I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    And I should see "Tumé Arandú"
    But "Arasy Guaraní" "text" should not exist in the ".generaltable" "css_element"
    And I am on site homepage
    And I log out
    And I log in as "student2"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I press "Get Certificate"
    And I am on site homepage
    And I log out
    And I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    Then I should see "Tumé Arandú"
    And I should see "Arasy Guaraní"
    But "Tupã Xingú" "text" should not exist in the ".generaltable" "css_element" 
    
 # Test if teacher certificate is save
 #Test export as 
 # test no certificate is issued
	