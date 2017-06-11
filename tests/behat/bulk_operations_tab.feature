@mod @mod_simplecertificate @bulk_tab_simplecertificate
Feature: Verify bulk operations
  In order to use bulk operations
  As a teacher
  I need to create a certificate 

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
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Grade assignment |
      | Description | Grade this assignment to revoke restriction on restricted assignment |
      | assignsubmission_onlinetext_enabled | 0 |
      | assignsubmission_file_enabled | 0 |
    And I add a "Simple Certificate" to section "2" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
	And I log out

  Scenario: Verify if list all user without any grading restrictions
    Given I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "All users"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    And "Arasy Guaraní" "text" should exist in the ".generaltable" "css_element"
    
  
  @javascript  
  Scenario: Verify if list all user with grading restrictions
    Given I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I put a grade restrinction to "Test Simple Certificate" with "Grade assignment" min grade "70"
    And I follow "Grade assignment"
      #	Old version 3.1 or less
#    And I follow "View all submissions"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Tumé Arandú" "table_row"
    And I set the following fields to these values:
      | Grade | 70 |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Edit settings"
    And I press "Cancel"
    And I follow "Grade assignment"
    #	Old version 3.1 or less
#    And I follow "View all submissions"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Arasy Guaraní" "table_row"
    And I set the following fields to these values:
      | Grade | 69 |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Edit settings"
    And I press "Cancel"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "All users"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    And "Arasy Guaraní" "text" should exist in the ".generaltable" "css_element"
       
  @javascript  
  Scenario: Verify if list only user whose match grading restrictions
  	Given I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I put a grade restrinction to "Test Simple Certificate" with "Grade assignment" min grade "70"
    And I follow "Grade assignment"
  #	Old version 3.1 or less
#    And I follow "View all submissions"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Tumé Arandú" "table_row"
    And I set the following fields to these values:
      | Grade | 70 |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Edit settings"
    And I press "Cancel"
    And I follow "Grade assignment"
      #	Old version 3.1 or less
#    And I follow "View all submissions"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Arasy Guaraní" "table_row"
    And I set the following fields to these values:
      | Grade | 69 |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Edit settings"
    And I press "Cancel"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "Users that met the activity conditions"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    But "Arasy Guaraní" "text" should not exist in the ".generaltable" "css_element"
  
  
  
    
    
    
	