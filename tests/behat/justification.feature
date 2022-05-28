@cul @mod @mod_peerwork @mod_peerwork_justification
Feature: Peerwork justification
  In order to test the peer justification type setting is working correctly
  As a student
  I need to view a graded submission

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
      | availablescales | 2     | peerworkcalculator_webpa |
      | calculator      | webpa | peerwork                 |
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

  @javascript
  Scenario: Teacher views grades when justification is disabled.
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    Then "Justifications" "link" should not exist

  @javascript
  Scenario: Students do not give justification when set to 'Disabled'
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then "Justifications" "link" should not exist
    And I log out

  @javascript
  Scenario: Students must give justification when set to 'Hidden from students'
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Require justification | Hidden from students |
    And I press "Save and display"
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then "Justification" "link" should exist
    And I should see "Note: your comments will be hidden from your peers and only visible to teaching staff."
    And I log out

  @javascript
  Scenario: Students must give justification when set to 'Visible anonymous'
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Require justification | Visible anonymous |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then "Justification" "link" should exist
    And I should see "Note: your comments will be visible to your peers but anonymised, your username will not be shown next to comments you leave."
    And I log out

  @javascript
  Scenario: Students must give justification when set to 'Visible with usernames'
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Require justification | Visible with usernames |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then "Justification" "link" should exist
    And I should see "Note: your comments and your username will be visible to your peers."
    And I log out

  @javascript
  Scenario: Students cannot give justification longer than the setting 'Justification character limit'
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Require justification         | Visible with usernames |
      | Justification character limit | 10                     |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | This is more than 10 words. 1 2 3 4 5 6 7 8 9 10. |
      | Student 2 | Did well                                          |
      | Student 3 | Exceeded                                          |
    Then I should see "-39 character(s) remaining"
    And I press "Save changes"
    Then I should see "You must enter no more than 10 characters here"
    And I log out

  @javascript
  Scenario: Students must give justification for each criteria
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I press "Add 1 more criteria"
    And I set the following fields to these values:
      | Require justification   | Visible with usernames   |
      | Justification type      | Criteria                 |
      | Criteria 2 description  | Criteria 2               |
      | Criteria 2 scoring type | Default competence scale |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then "Justification" "link" should not exist
    And "Justification" "field" should appear after "Competent" "radio"
    And I log out

  @javascript
  Scenario: Teacher views grades when justification is enabled and has been submitted.
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I press "Add 1 more criteria"
    And I set the following fields to these values:
      | Require justification | Visible with usernames |
    And I press "Save and display"
    And I log out
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | Poor     |
      | Student 2 | Did well |
      | Student 3 | Exceeded |
    And I press "Save changes"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    Then "Justifications" "link" should exist
