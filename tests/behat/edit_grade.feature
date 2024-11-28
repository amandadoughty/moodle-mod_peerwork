@cul @mod @mod_peerwork @mod_peerwork_edit_grade
Feature: Edit the grade of a submission
  In order to test the grading edit options
  As a student
  I need to see the correct final grade

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
      | calculator | webpa | peerwork |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "peerwork" activity to course "Course 1" section "1" and I fill the form with:
      | Peer assessment           | Test peerwork name        |
      | Description               | Test peerwork description |
      | Peer grades visibility    | Hidden from students      |
      | Require justification     | Disabled                  |
      | Criteria 1 description    | Criteria 1                |
      | Criteria 1 scoring type   | Default competence scale  |
      | Peer assessment weighting | 0                         |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
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
  Scenario: View the calculated grade.
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then I should see "80" in the "My final grade" "table_row"

  @javascript
  Scenario: View the revised grade.
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I give "student1" revised grade "70"
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then I should see "70" in the "My final grade" "table_row"

  @javascript
  Scenario: View the gradebook overridden grade.
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "60.00" to the user "Student 1" for the grade item "Test peerwork name"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerwork name"
    Then I should see "60" in the "My final grade" "table_row"

  @javascript
  Scenario: Cannot view the gradebook hidden grade.
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the following settings for grade item "Test peerwork name" of type "gradeitem" on "grader" page:
      | Hidden | 1 |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then "My final grade" "table_row" should not exist
