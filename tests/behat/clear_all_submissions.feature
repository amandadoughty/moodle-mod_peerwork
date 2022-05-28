@cul @_file_upload @mod @mod_peerwork @mod_peerwork_clear_all_submissions
Feature: Clear submissions
    In order to test clearing submissions
    As a teacher
    I need to be able to delete all content from submissions

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
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Hidden from students      |
      | Require justification   | Visible with usernames    |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "Assignment" filemanager
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | Poor     |
      | Student 2 | Did well |
      | Student 3 | Exceeded |
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | Poor     |
      | Student 1 | Did well |
      | Student 3 | Exceeded |
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | Poor     |
      | Student 1 | Did well |
      | Student 2 | Exceeded |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Teachers can clear submissions
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    Then I should see "empty.txt" in the "Peer submission and grades" "fieldset"
    And "Student 3" row "Student 1" column of "Criteria 1" table should contain "1"
    And the following should exist in the "justificationbyforstudent2" table:
        | -1-       | -2- |
        | Student 0 | Poor |
        | Student 1 | Did well |
        | Student 3 | Exceeded |
    And I follow "Peer Assessment"
    And I press "Clear all submissions"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    And I follow "Group 1"
    And I expand all fieldsets
    Then I should see "Nothing submitted yet" in the "Peer submission and grades" "fieldset"
    And "Student 3" row "Student 1" column of "Criteria 1" table should contain "-"
    And the following should exist in the "justificationbyforstudent2" table:
        | -1-       | -2- |
        | Student 0 | None given |
        | Student 1 | None given |
        | Student 3 | None given |
    And I log out
