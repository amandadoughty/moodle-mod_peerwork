@cul @mod @mod_peerwork @mod_peerwork_override_peer_grade
Feature: Overide the grades given by a peer
  In order to test that a peer grade can be overridden
  As a teacher
  The I should be able to edit the peer grade

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student0 | Student   | 0        | student0@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student0 | C1     | student        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
    And the following "group members" exist:
      | user     | group |
      | student0 | G1    |
      | student1 | G1    |
      | student2 | G1    |
      | student3 | G1    |
    And the following config values are set as admin:
      | calculator         | webpa | peerwork |
      | overridepeergrades | 1     | peerwork |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible with usernames    |
      | Require justification   | Disabled                  |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "1" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Override the grades given by a student to their peer
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "Select" "link" in the "#memberdropdown" "css_element"
    And I click on "Student 1" "link" in the "#memberdropdown" "css_element"
    And I enable overriden "student2" grade for criteria "Criteria 1"
    And I override "student2" grade for criteria "Criteria 1" with "0" "Very poor"
    And I press "Save changes"
    And I expand all fieldsets
    Then "Student 1" row "Student 2" column of "Criteria 1" table should contain "0"
    And "Overridden peer grade: 1 Comment: Very poor" "icon" should exist in the "Criteria 1" "table"
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I expand all fieldsets
    And "Grade before overrides: " "icon" should exist in the "mod-peerwork-grader-table" "table"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 1: Not yet competent" in the "Peer grades" "table_row"

  @javascript
  Scenario: Override the grades when student has not submitted
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "Select" "link" in the "#memberdropdown" "css_element"
    And I click on "Student 3" "link" in the "#memberdropdown" "css_element"
    And I enable overriden "student2" grade for criteria "Criteria 1"
    And I override "student2" grade for criteria "Criteria 1" with "0" "Very poor"
    And I press "Save changes"
    And I expand all fieldsets
    Then "Student 3" row "Student 2" column of "Criteria 1" table should contain "0"
    And "Overridden peer grade: None. Comment: Very poor" "icon" should exist in the "Criteria 1" "table"
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 3: Not yet competent" in the "Peer grades" "table_row"

  @javascript
  Scenario: Override the grades without providing a comment
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "Select" "link" in the "#memberdropdown" "css_element"
    And I click on "Student 3" "link" in the "#memberdropdown" "css_element"
    And I enable overriden "student2" grade for criteria "Criteria 1"
    And I override "student2" grade for criteria "Criteria 1" with "0" ""
    And I press "Save changes"
    Then I should see "Please give your reason for overriding this peer grade."

  @javascript
  Scenario: Override the grades after they have released
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "93.33" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 93.33 |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "Select" "link" in the "#memberdropdown" "css_element"
    And I click on "Student 1" "link" in the "#memberdropdown" "css_element"
    And I enable overriden "student2" grade for criteria "Criteria 1"
    And I override "student2" grade for criteria "Criteria 1" with "0" "Very poor"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "40.00" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 40.00 |
    And I log out
