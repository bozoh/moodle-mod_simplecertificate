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
      | Course Test 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course Test 1" course homepage
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Grade assignment |
      | Description | Grade this assignment to revoke restriction on restricted assignment |
      | assignsubmission_onlinetext_enabled | 0 |
      | assignsubmission_file_enabled | 0 |
    
    And I log out

  Scenario: Verify if list all user without any grading restrictions
    Given I log in as "teacher1"
    And I am on "Course Test 1" course homepage
    And I turn editing mode on
    And I add a "Simple Certificate" to section "2" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
    And I turn editing mode off
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "All users"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    And "Arasy Guaraní" "text" should exist in the ".generaltable" "css_element"
    
  @javascript  
  Scenario: Verify options: list all users,  Users that met the activity conditions with grading restrictions
  	Given I log in as "teacher1"
    And I am on "Course Test 1" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "70" to the user "Tumé Arandú" for the grade item "Grade assignment"
    And I give the grade "69" to the user "Arasy Guaraní" for the grade item "Grade assignment"
    And I press "Save changes"
    And I am on "Course Test 1" course homepage
    And I turn editing mode on
    And I add a "Simple Certificate" to section "2" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
    And I setup a grade restrinction to "Test Simple Certificate" with "Grade assignment" min grade "70"
    And I am on "Course Test 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "Users that met the activity conditions"
    Then I should see "Tumé Arandú"
    And I should not see "Arasy Guaraní"
    Then I am on "Course Test 1" course homepage
    And I follow "Test Simple Certificate"
    And I click on "Bulk operations" "link"
    And I set the field "issuelist" to "All users"
    Then I should see "Arasy Guaraní"
    And I should see "Tumé Arandú"