@cul @mod @mod_peerwork @mod_peerwork_grading_status
Feature: View the grading status of a submission
  In order to test the grading status for submissions is displaying correctly
  As a student
  I need to view my grading status

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
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment           | Test peerwork name        |
      | Description               | Test peerwork description |
      | Peer grades visibility    | Hidden from students      |
      | Require justification     | Disabled                  |
      | Criteria 1 description    | Criteria 1                |
      | Criteria 1 scoring type   | Default competence scale  |
      | Peer assessment weighting | 0                         |
    And I log out

  @javascript
  Scenario: View the grading status.
    # No submission made and submission is closed.
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Due date | ## -1 day ## |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerwork name"
    Then I should see "(over due by 1 day)" in the "Time remaining" "table_row"
    And I should see "Nothing submitted yet but due date passed 1 day ago." in the "Submission status" "table_row"
    And I should see "Not editable because: After due date and late submissions not allowed." in the "Submission status" "table_row"
    And I should see "Users who did not submit: Student 0, Student 1, Student 2, Student 3" in the "Submission status" "table_row"
    And "Add submission" "button" should not exist
    And "Edit submission" "button" should not exist
    And I log out
    # No submission made and submission is open.
    And I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Due date | ## 1 day 10 min ## |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then I should see "1 day" in the "Time remaining" "table_row"
    And I should see "Nothing submitted yet." in the "Submission status" "table_row"
    And I should see "Editable because: Assessment open." in the "Submission status" "table_row"
    And I should see "Users who still need to submit: Student 0, Student 1, Student 2, Student 3" in the "Submission status" "table_row"
    And "Add submission" "button" should exist
    # Submission made
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    Then I should see "1 day" in the "Time remaining" "table_row"
    And I should see "First submitted by Student 1 on" in the "Submission status" "table_row"
    And I should see "Editable because: Assessment open." in the "Submission status" "table_row"
    And I should see "Users who still need to submit: Student 0, Student 2, Student 3" in the "Submission status" "table_row"
    And "Add submission" "button" should not exist
    And "Edit submission" "button" should exist
    And I log out
    And I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Due date | ## -1 day ## |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerwork name"
    Then I should see "1 day" in the "Time remaining" "table_row"
    And I should see "First submitted by Student 1 on" in the "Submission status" "table_row"
    And I should see "Not editable because: After due date and late submissions not allowed." in the "Submission status" "table_row"
    And I should see "Users who did not submit: Student 0, Student 2, Student 3" in the "Submission status" "table_row"
    And "Add submission" "button" should not exist
    And "Edit submission" "button" should not exist
    And I log out
    # Submission graded
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then I should see "Graded by Teacher 1" in the "Submission status" "table_row"
    And I should see "Not editable because: Assessment already graded." in the "Submission status" "table_row"
    And "My final grade" "table_row" should not exist
    And I log out
    # Grades released
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I press "Release all grades for all groups"
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then I should see "Grades released by Teacher 1" in the "Submission status" "table_row"
    And I should see "Not editable because: Assessment already graded." in the "Submission status" "table_row"
    And I should see "80" in the "My final grade" "table_row"
