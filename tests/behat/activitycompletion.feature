@cul @mod @mod_peerwork @mod_peerwork_submission
Feature: Assignment submissions

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
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
      | calculator | webpa | peerwork |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "peerwork" activity to course "Course 1" section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Hidden from students      |
      | Require justification   | Disabled                  |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I am on the "Test peerwork name" "peerwork activity" page
    And I navigate to "Settings" in current page administration
    And I click on "Expand all" "link" in the "region-main" "region"
    And I set the following fields to these values:
      | Completion tracking  | 2 |
      | Grade peers in group | 1 |
    And I press "Save and return to course"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"

  @javascript
  Scenario: Students who grades every peer is shown as completed
    When I am on "Course 1" course homepage
    And "Done" "button" should exist in the "Test peerwork name" "activity"
    And I log out

  @javascript
  Scenario: Students who has not graded every peer is not shown as completed
    And I log in as "student2"
    When I am on "Course 1" course homepage
    And "Done" "button" should not exist in the "Test peerwork name" "activity"
    And "To do" "button" should exist in the "Test peerwork name" "activity"
    And I log out

  @javascript
  Scenario: Student completions must display correctly in completion report
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    And "Student 1, Test peerwork name: Completed" "icon" should exist in the "Student 1" "table_row"
    And "Student 2, Test peerwork name: Not completed" "icon" should exist in the "Student 2" "table_row"
