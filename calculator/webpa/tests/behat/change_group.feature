@peerworkcalculator @peerworkcalculator_webpa @peerworkcalculator_webpa_change_group @mod_peerwork @mod
Feature: Change the group membership
  In order to test changes in group membership
  As a teacher
  I need to see the updated grades

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
      | student4 | Student   | 4        | student4@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student0 | C1     | student        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student3 | G1    |
      | student4 | G1    |
    And the following config values are set as admin:
      | calculator         | webpa | peerwork |
      | overridepeergrades | 1     | peerwork |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "peerwork" activity to course "Course 1" section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name                     |
      | Description             | Test peerwork description              |
      | Peer grades visibility  | Hidden from students                   |
      | Require justification   | Disabled                               |
      | Criteria 1 description  | Criteria 1                             |
      | Criteria 1 scoring type | Separate and Connected ways of knowing |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student2" grade "2" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I give "student4" grade "2" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    And I press "Add submission"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "0" for criteria "Criteria 1"
    And I give "student4" grade "0" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    And I press "Add submission"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student4" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student4
    And I press "Add submission"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "0" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out

  @javascript
  Scenario: View the calculated grade after group membership changes.
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    Then I should see "48.00" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 48.00 |
    And I log out
    # Remove a member who gave grades.
    And I am on the "Course 1" "groups" page logged in as teacher1
    And I remove "Student 1 (student1@example.com)" user from "Group 1" group members
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    Then I should see "40.00" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 40.00 |
    And I log out

    # Add back the member who gave grades
    And I am on the "Course 1" "groups" page logged in as teacher1
    And I add "Student 1 (student1@example.com)" user to "Group 1" group members
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    Then I should see "48.00" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 48.00 |
    And I log out
    # Add a new member
    And I am on the "Course 1" "groups" page logged in as teacher1
    And I add "Student 0 (student0@example.com)" user to "Group 1" group members
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    Then I should see "50.00" in the "My final grade" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "View > User report" in the course gradebook
    Then the following should exist in the "user-grade" table:
      | Grade item         | Grade |
      | Test peerwork name | 50.00 |
    And I log out
    # Overrride a grade
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "Select" "link" in the "#memberdropdown" "css_element"
    And I click on "Student 2" "link" in the "#memberdropdown" "css_element"
    And I enable overriden "student3" grade for criteria "Criteria 1"
    And I override "student3" grade for criteria "Criteria 1" with "1" "Very good"
    And I press "Save changes"
    And I expand all fieldsets
    And "Grade before overrides: 50.00" "icon" should exist in the "mod-peerwork-grader-table" "table"
