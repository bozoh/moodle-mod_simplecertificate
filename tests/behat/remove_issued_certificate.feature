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
    And the following "activities" exist:
      | activity           | name                    | course | idnumber    | section |
      | simplecertificate  | Test Simple Certificate | C1     | cert1       | 2       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Test Simple Certificate" "link" in the "#region-main" "css_element"
    And I click on "Bulk operations" "link"
    And I set the field "bulk-type-select" to "Download certificates in a zip file"
    And I check 'Tumé Arandú' on list
    And I check 'Arasy Guaraní' on list
    And I press "Send"
    And I am on "Course 1" course homepage
    And I click on "Test Simple Certificate" "link" in the "#region-main" "css_element"

  @javascript
  Scenario: Verify if list all user is listed
    Given I click on "Issued certificates" "link"
    Then "Tumé Arandú" "text" should exist in the ".generaltable" "css_element"
    And "Arasy Guaraní" "text" should exist in the ".generaltable" "css_element"

  @javascript
  Scenario: Delete selected certificates
    Given I click on "Issued certificates" "link"
    And I check 'Arasy Guaraní' on list
    And I set the field "bulk-type-select" to "Delete Selected"
    And I press "Send"
    And I click on "Delete" "button" in the ".modal-dialog" "css_element"
    Then I should see "Tumé Arandú"
    And I should not see "Arasy Guaraní"

  @javascript
  Scenario: Delete All certificates
    Given I click on "Issued certificates" "link"
    And I set the field "bulk-type-select" to "Delete All"
    And I press "Send"
    And I click on "Delete" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Tumé Arandú"
    And I should not see "Arasy Guaraní"
