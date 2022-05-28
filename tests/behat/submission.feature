@cul @mod @mod_peerwork @mod_peerwork_submission
Feature: Assignment submissions

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

  @javascript
  Scenario: Students must grade every peer
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    Then I should see "Please provide a rating for each one of your peers."
    And I log out

  @javascript
  Scenario: Students must grade themselves when Allow students to self-grade along with peers is set to yes
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Allow students to self-grade along with peers | Yes |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    Then I should see "Student 1 (you)" in the "Grade your peers" "fieldset"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    Then I should see "Please provide a rating for each one of your peers."
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    Then I should not see "Please provide a rating for each one of your peers."
    And I log out

  @javascript @_file_upload
  Scenario: Students can upload and view files
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Maximum number of uploaded files | 2 |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    When I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "Assignment" filemanager
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    Then I should see "First submitted by Student 1"
    And "empty.txt" "link" should exist
    And I press "Edit submission"
    And I upload "lib/tests/fixtures/upload_users.csv" file to "Assignment" filemanager
    And ".ffilemanager .fm-maxfiles .fp-btn-add" "css_element" should not be visible
    And I press "Save changes"
    And I should see "First submitted by Student 1"
    And "empty.txt" "link" should exist
    And "upload_users.csv" "link" should exist
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    And I press "Add submission"
    And ".ffilemanager .fm-maxfiles .fp-btn-add" "css_element" should not be visible
    And I delete "empty.txt" from "Assignment" filemanager
    And I give "student0" grade "1" for criteria "Criteria 1"
    And I give "student1" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I press "Save changes"
    And I should see "First submitted by Student 1"
    And I should see "Last edited on"
    And "empty.txt" "link" should not exist
    And "upload_users.csv" "link" should exist
    And I log out

  @javascript
  Scenario: Students cannot edit locked submissions - justification per peer
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I press "Add 1 more criteria"
    And I set the following fields to these values:
      | Require justification | Visible with usernames |
      | Justification type    | Peer                   |
      | Lock editing          | 1                      |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I set the following fields in the "Justification" "fieldset" to these values:
      | Student 0 | Poor     |
      | Student 2 | Did well |
      | Student 3 | Exceeded |
    And I click on "Save changes" "button"
    And I click on "Yes" "button"
    And I press "Edit submission"
    Then "Assignment" "field" should not be visible
    And "Criteria 1" "student0" rating should be disabled
    And "Criteria 1" "student2" rating should be disabled
    And "Criteria 1" "student3" rating should be disabled
    And peer "student0" justification should be disabled
    And peer "student2" justification should be disabled
    And peer "student3" justification should be disabled
    And I log out

  @javascript
  Scenario: Students cannot edit locked submissions - justification per criteria
    Given I am on the "Test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I press "Add 1 more criteria"
    And I set the following fields to these values:
      | Require justification | Visible with usernames |
      | Justification type    | Criteria               |
      | Lock editing          | 1                      |
    And I press "Save and display"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    And I press "Add submission"
    And I give "student0" grade "0" for criteria "Criteria 1"
    And I give "student2" grade "1" for criteria "Criteria 1"
    And I give "student3" grade "1" for criteria "Criteria 1"
    And I give "student0" justification "Poor" for criteria "Criteria 1"
    And I give "student2" justification "Did well " for criteria "Criteria 1"
    And I give "student3" justification "Exceeded" for criteria "Criteria 1"
    And I click on "Save changes" "button"
    And I click on "Yes" "button" in the "Are you sure?" "dialogue"
    And I press "Edit submission"
    Then "Assignment" "field" should not be visible
    And "Criteria 1" "student0" rating should be disabled
    And "Criteria 1" "student2" rating should be disabled
    And "Criteria 1" "student3" rating should be disabled
    And criteria "Criteria 1" "student0" justification should be disabled
    And criteria "Criteria 1" "student2" justification should be disabled
    And criteria "Criteria 1" "student3" justification should be disabled
    And I log out
