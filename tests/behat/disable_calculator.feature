@cul @mod @mod_peerwork @mod_peerwork_disable_calculator
Feature: Disable a calculator which has been used in peerwork
    In order to test disabling the calculator
    As a teacher
    I need to edit the activity before and after submissions are graded

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
      | Criteria 1 description    | Criteria 1                |
      | Criteria 1 scoring type   | Default competence scale  |
      | Peer assessment weighting | 0                         |
    And I add a "Peer Assessment" to section "1" and I fill the form with:
      | Peer assessment           | Another test peerwork name        |
      | Description               | Another test peerwork description |
      | Criteria 1 description    | Criteria 1                        |
      | Criteria 1 scoring type   | Default competence scale          |
      | Peer assessment weighting | 0                                 |
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
    And I log out
    And the following config values are set as admin:
      | disabled | 1 | peerworkcalculator_webpa |

  @javascript
  Scenario: Disabled calculator not updated before submisssions are graded
    Given I am on the "Another test peerwork name" "peerwork activity editing" page logged in as teacher1
    And I expand all fieldsets
    Then the disabled calculator is not updated before grading

  @javascript
  Scenario: Disabled calculator still used after submisssions are graded
    Given I am on the "Test peerwork name" "peerwork activity" page logged in as teacher1
    And I press "Release all grades for all groups"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    Then the disabled calculator is not updated after grading
