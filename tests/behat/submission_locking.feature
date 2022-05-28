@cul @mod @mod_peerwork @mod_peerwork_submission_locking
Feature: Lock and unlock submissions
  In order to test locked submissions
  As a teacher
  I need to be able to lock a peerwork activity and unlock
  individual submissions and students

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
      | Require justification   | Disabled                  |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
      | Lock editing            | 1                         |
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I click on "Save changes" "button"
    And I click on "Yes" "button"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I click on "Save changes" "button"
    And I click on "Yes" "button"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student3
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I click on "Save changes" "button"
    And I click on "Yes" "button"
    And I log out

  @javascript
  Scenario: Teachers can unlock submissions
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    Then "unlock_submission_btn" "link" should exist in the "Peer submission and grades" "fieldset"
    And I follow "unlock_submission_btn"
    And I click on "Yes" "button" in the "Are you sure?" "dialogue"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Edit submission"
    Then the "Assignment" "field" should be enabled
    And "Criteria 1" "student0" rating should be disabled
    And "Criteria 1" "student2" rating should be disabled
    And "Criteria 1" "student3" rating should be disabled
    And I log out

  @javascript
  Scenario: Teachers can unlock peer grades
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I expand all fieldsets
    And I click on "//a[@data-graderfullname='Student 1']" "xpath_element"
    And I click on "Yes" "button" in the "Are you sure?" "dialogue"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Edit submission"
    Then "Assignment" "field" should not be visible
    And "Criteria 1" "student0" rating should be enabled
    And "Criteria 1" "student2" rating should be enabled
    And "Criteria 1" "student3" rating should be enabled
    And I log out
