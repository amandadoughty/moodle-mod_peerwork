@cul @mod @mod_peerwork @mod_peerwork_prevent_changes_after_submission
Feature: Prevent some changes after a submission has been made
  In order to test the assessment setting options
  As a student
  The visibility of grades and justification should not change after I have relied on them in my submission

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
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Edit the assessment settings
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    Then "Peer grades visibility" "field" should not be visible
    And "Require justification" "field" should not be visible
    And "Justification type" "field" should not be visible
    And the "Justification character limit" "field" should be disabled
    And "Criteria 1 scoring type" "field" should not be visible
    And "Allow students to self-grade along with peers" "field" should not be visible
