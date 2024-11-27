@cul @mod @mod_peerwork @mod_peerwork_group_restriction
Feature: Group availability
  In order to test group availability
  As a teacher
  I need to be able to restrict a peerwork activity

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

  @javascript
  Scenario: Teachers can unlock submissions
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "peerwork" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Peer assessment         | Test peerwork name        |
      | Description             | Test peerwork description |
      | Criteria 1 description  | Criteria 1                |
      | Criteria 1 scoring type | Default competence scale  |
    And I expand all fieldsets
    And I press "Add restriction..."
    And I click on "Group" "button" in the "Add restriction..." "dialogue"
    And I set the field "Group" in the "Restrict access" "fieldset" to "Group 1"
    And I press "Save and display"
    Then I should see "Group 1"
    And I should not see "Group 2"
