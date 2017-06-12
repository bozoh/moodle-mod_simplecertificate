@mod @mod_simplecertificate @remove_issued_certificate
Feature: Remove an issued certificate
  In order to remove an issued certificate
  As a teacher
  I need to select the issue certificate which will be removed
  and  seletc delete certifica option
  and click in send 

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
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Simple Certificate" to section "2" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I select "All users" from the "issuelist" singleselect
    And I click on "Send" "button"
	And I am on site homepage
    And I log out

  Scenario: Verify if list all user is listed
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    And "Arasy Guaraní" "text" should exist in the ".generaltable" "css_element"
    
  Scenario: Delete selected certificates
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
 #   And I set the field with xpath "//tr[contains(normalize-space(.), 'Arasy Guaraní')]//input[@type='checkbox']" to "1"
    #And I set the field with xpath "//tbody//tr[1]//input[@type='checkbox']" to "1"
    And I click on "//tbody//tr[1]//input[@type='checkbox']" "xpath"
    And I click on "Delete Selected" "button"
    Then "Tumé Arandú" "text" should exist
    And "Arasy Guaraní" "text" should not exist
 
 Scenario: Delete All certificates
 	Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    And I click on "Delete All" "button"
    Then "Tumé Arandú" "text" should not exist
    And "Arasy Guaraní" "text" should not exist
 
       

  
  
    
    
    
	