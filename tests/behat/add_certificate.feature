@mod @mod_simplecertificate @add_simplecertificate
Feature: Add a simplecertificate
  In order to create certificate for students
  As a teacher
  I need to create a certificate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript 
  Scenario: Add a very basic certificate and verify if a student can donwload
    When I log in as "teacher1"
    #Moodle 3.2 and below
    #And I follow "Course 1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Simple Certificate" to section "1" and I fill the form with:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
	And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test Simple Certificate"
    Then I press "Get Certificate"
    And I should see a pop-up window

  @javascript
  Scenario: Show certificate greyed-out to students when grade condition is not satisfied
    Given  I log in as "teacher1"
    #And I am on site homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Grade assignment |
      | Description | Grade this assignment to revoke restriction on restricted assignment |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    # Adding the page like this because id_availableform_enabled needs to be clicked to trigger the action.
    And I add a "Simple Certificate" to section "2"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Grade" "button" in the "Add restriction..." "dialogue"
    And I click on "min" "checkbox"
    #I don't know why this select is not setting with I set filed step, so i put this work around 
    And I select "Grade assignment" option from the "id" 
    And I set the following fields to these values:
      | Certificate Name | Test Simple Certificate |
      | Certificate Text | Test Simple Certificate |
      | minval | 20 |
   #   | id | Grade assignment |
    And I press "Save and return to course"
    And I log out
    When I log in as "student1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    Then I should see "Not available unless: You achieve a required score in Grade assignment"
    #for moodle 3.2 or below
    #And "Test Simple Certificate" activity should be hidden
    And I should not see "Test page name"
    And I follow "Grade assignment"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I should see "Submitted for grading"
    And I log out
    And I log in as "teacher1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I follow "Grade assignment"
#	Old version 3.1 or less
#    And I follow "View all submissions"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Sam1 Student1" "table_row"
    And I set the following fields to these values:
      | Grade | 20 |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Edit settings"
    And I log out
    And I log in as "student1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And "Test Simple Certificate" activity should be visible
    And I should not see "Not available unless: You achieve a required score in Grade assignment"
    And I follow "Test Simple Certificate"
    Then I press "Get Certificate"
    And I should see a pop-up window
    
    
	