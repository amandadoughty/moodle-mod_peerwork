@cul @mod @mod_peerwork @mod_peerwork_display_grades
Feature: View the peer grades and justification of a submission
    In order to test the peer grades and justification are displaying correctly
    As a student
    I need to view my peer grades and justification

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

  @javascript
  Scenario: Student views grades when grades are hidden and justification is disabled.
    Given I log in as "teacher1"
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I follow "Group 1"
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
    Then "Peer grades" "table_row" should not exist
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are hidden and justification is hidden.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Hidden from students      |
      | Require justification   | Hidden from students      |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then "Peer grades" "table_row" should not exist
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are hidden and justification is anonymous.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Hidden from students      |
      | Require justification   | Visible anonymous         |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then "Peer grades" "table_row" should not exist
    And I should not see "Student 1: Did well" in the "Justifications" "table_row"
    And I should see "Did well" in the "Justifications" "table_row"

  @javascript
  Scenario: Student views grades when grades are hidden and justification is visible.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Hidden from students      |
      | Require justification   | Visible with usernames    |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerwork name"
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
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then "Peer grades" "table_row" should not exist
    And I should see "Student 1:Did well" in the "Justifications" "table_row"

  @javascript
  Scenario: Student views grades when grades are anonymous and justification is disabled.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible anonymous         |
      | Require justification   | Disabled                  |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerwork name"
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should not see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should see "Competent" in the "Peer grades" "table_row"
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are anonymous and justification is hidden.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible anonymous         |
      | Require justification   | Hidden from students      |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should not see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should see "Competent" in the "Peer grades" "table_row"
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are anonymous and justification is anonymous.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible anonymous         |
      | Require justification   | Visible anonymous         |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should not see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should see "Competent" in the "Peer grades" "table_row"
    And I should not see "Student 1:Did well" in the "Justifications" "table_row"
    And I should see "Did well" in the "Justifications" "table_row"

  @javascript
  Scenario: Student views grades when grades are anonymous and justification is visible.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible anonymous         |
      | Require justification   | Visible with usernames    |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should not see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should see "Competent" in the "Peer grades" "table_row"
    And I should see "Student 1:Did well" in the "Justifications" "table_row"

  @javascript
  Scenario: Student views grades when grades are visible and justification is disabled.
    Given I log in as "teacher1"
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 1: Competent" in the "Peer grades" "table_row"
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are visible and justification is hidden.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible with usernames    |
      | Require justification   | Hidden from students      |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 1: Competent" in the "Peer grades" "table_row"
    And "Justifications" "table_row" should not exist

  @javascript
  Scenario: Student views grades when grades are visible and justification is anonymous.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible with usernames    |
      | Require justification   | Visible anonymous         |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
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
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should not see "Student 1:Did well" in the "Justifications" "table_row"
    And I should see "Did well" in the "Justifications" "table_row"

  @javascript
  Scenario: Student views grades when grades are visible and justification is visible.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Peer grades visibility  | Visible with usernames    |
      | Require justification   | Visible with usernames    |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student1
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
    And I set the following fields to these values:
      | Group grade out of 100 | 80 |
    And I press "Save changes"
    And I follow "Peer Assessment"
    And I press "Release all grades for all groups"
    And I log out
    And I am on the "Test peerwork name" "peerwork activity" page logged in as student2
    Then I should see "Student 1: Competent" in the "Peer grades" "table_row"
    And I should see "Student 1:Did well" in the "Justifications" "table_row"
