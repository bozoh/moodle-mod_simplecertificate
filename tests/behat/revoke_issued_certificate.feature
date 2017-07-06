@mod @mod_simplecertificate @revoke_issued_certificate
Feature: Revoke an issued certificate
  In order to revoke an issued certificate
  As a teacher
  I need to select the issue certificate which will be revoked
  and click in revoke button 

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
    And I am on "Course 1" course homepage
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

  Scenario: Verify if list all certificate with valid  status
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    Then "Tumé Arandú" certificate status should be "valid"
    And "Arasy Guaraní" certificate status should be "valid"

  @javascript    
  Scenario: Verify if revoke only the seleted certificates
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Issued certificates" "link"
    # Advanced checkbox requires real browser to allow uncheck to work. MDL-58681. MDL-55386.
    And I check 'Arasy Guaraní' on list
    And I click on "Revoke Selected" "button"
    Then "Tumé Arandú" certificate status should be "valid"
    And "Tumé Arandú" certificate status should be "revoked"
  
       

  
  
    
    
    
	