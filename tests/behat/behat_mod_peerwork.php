<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions for peerwork activity.
 *
 * @package   mod_peerwork
 * @category  test
 * @copyright 2020 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_general.php');
require_once(__DIR__ . '/../../../../lib/tests/behat/behat_forms.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Peerwork activity definitions.
 *
 * @package   mod_peerwork
 * @category  test
 * @copyright 2020 Amanda Doughty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_peerwork extends behat_base {
    /**
     * Sets the grade for the specified peer in the specified criteria.
     *
     * @When /^I give "(?P<peer_string>[^"]*)" grade "(?P<grade_string>[^"]*)" for criteria "(?P<criteria_string>[^"]*)"$/
     *
     * @param string $peer
     * @param string $grade
     * @param string $criteria
     */
    public function i_give_grade_for_criteria($peer, $grade, $criteria) {
        $node = $this->find('xpath', "//div[contains(@class,'mod_peerwork_criteriaheader') and contains(., '"  . $criteria . "')]");
        $criterionid = $node->getParent()->getAttribute('data-criterionid');
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "grade_idx_{$criterionid}[{$studentid}]";
        $fieldxpath = "//input[@name='" . $fieldlocator . "' and @type='radio' and @value='" . $grade . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, $grade]);
    }

    /**
     * Sets the justification for the specified peer in the specified criteria.
     *
     * @When /^I give "(?P<peer_string>[^"]*)" justification "(?P<justification_string>[^"]*)" for criteria "(?P<criteria_string>[^"]*)"$/
     *
     * @param string $peer
     * @param string $justification
     * @param string $criteria
     */
    public function i_give_justification_for_criteria($peer, $justification, $criteria) {
        $node = $this->find('xpath', "//div[contains(@class,'mod_peerwork_criteriaheader') and contains(., '"  . $criteria . "')]");
        $criterionid = $node->getParent()->getAttribute('data-criterionid');
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "justification_{$criterionid}[{$studentid}]";
        $fieldxpath = "//textarea[@name='" . $fieldlocator . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, $justification]);
    }

    /**
     * Enables overrde of the grade for the specified peer in the specified criteria.
     *
     * @When /^I enable overriden "(?P<peer_string>[^"]*)" grade for criteria "(?P<criteria_string>[^"]*)"$/
     *
     * @param string $peer
     * @param string $criteria
     */
    public function i_enable_overriden_grade_for_criteria($peer, $criteria) {
        $criterionid = $this->get_criteria_id($criteria);
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "overridden_idx_{$criterionid}[{$studentid}]";
        $fieldxpath = "//input[@name='" . $fieldlocator . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, 1]);
    }

    /**
     * Overrides the grade for the specified peer in the specified criteria.
     *
     * @When /^I override "(?P<peer_string>[^"]*)" grade for criteria "(?P<criteria_string>[^"]*)" with "(?P<grade_string>[^"]*)" "(?P<comment_string>[^"]*)"$/
     *
     * @param string $peer
     * @param string $criteria
     * @param string $grade
     * @param string $comments
     */
    public function i_override_grade_for_criteria_with($peer, $criteria, $grade, $comments) {
        $criterionid = $this->get_criteria_id($criteria);
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "gradeoverride_idx_{$criterionid}[{$studentid}]";
        $fieldxpath = "//select[@name='" . $fieldlocator . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, $grade]);

        $fieldlocator = "comments_idx_{$criterionid}[{$studentid}]";
        $fieldxpath = "//textarea[@name='" . $fieldlocator . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, $comments]);
    }

    /**
     * Sets the revised grade for a student.
     *
     * @When /^I give "(?P<peer_string>[^"]*)" revised grade "(?P<grade_string>[^"]*)"$/
     *
     * @param string $peer
     * @param string $grade
     */
    public function i_give_revised_grade($peer, $grade) {
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "id_grade_$studentid";
        $fieldxpath = "//input[@id='" . $fieldlocator . "']";

        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$fieldxpath, $grade]);
    }

    /**
     * Checks that a peer grade field is disabled.
     *
     * @When /^"(?P<criteria_string>[^"]*)" "(?P<peer_string>[^"]*)" rating should be disabled$/
     *
     * @param string $criteria
     * @param string $peer
     */
    public function rating_should_be_disabled($criteria, $peer) {
        $node = $this->find('xpath', "//div[contains(@class,'mod_peerwork_criteriaheader') and contains(., '"  . $criteria . "')]");
        $criterionid = $node->getParent()->getAttribute('data-criterionid');
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "grade_idx_{$criterionid}[{$studentid}]";

        $this->execute('behat_general::the_element_should_be_disabled', [$fieldlocator, 'field']);
    }

    /**
     * Checks that a peer grade field is enabled.
     *
     * @When /^"(?P<criteria_string>[^"]*)" "(?P<peer_string>[^"]*)" rating should be enabled$/
     *
     * @param string $criteria
     * @param string $peer
     */
    public function rating_should_be_enabled($criteria, $peer) {
        $node = $this->find('xpath', "//div[contains(@class,'mod_peerwork_criteriaheader') and contains(., '"  . $criteria . "')]");
        $criterionid = $node->getParent()->getAttribute('data-criterionid');
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "grade_idx_{$criterionid}[{$studentid}]";

        $this->execute('behat_general::the_element_should_be_enabled', [$fieldlocator, 'field']);
    }

    /**
     * Checks that a criteria justification field is disabled.
     *
     * @When /^criteria "(?P<criteria_string>[^"]*)" "(?P<peer_string>[^"]*)" justification should be disabled$/
     *
     * @param string $criteria
     * @param string $peer
     */
    public function criteria_justification_should_be_disabled($criteria, $peer) {
        $node = $this->find('xpath', "//div[contains(@class,'mod_peerwork_criteriaheader') and contains(., '"  . $criteria . "')]");
        $criterionid = $node->getParent()->getAttribute('data-criterionid');
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "justification_{$criterionid}[{$studentid}]";

        $this->execute('behat_general::the_element_should_be_disabled', [$fieldlocator, 'field']);
    }

    /**
     * Checks that a peer justification field is disabled.
     *
     * @When /^peer "(?P<peer_string>[^"]*)" justification should be disabled$/
     *
     * @param string $peer
     */
    public function peer_justification_should_be_disabled($peer) {
        $studentid = $this->get_student_id($peer);
        $fieldlocator = "justifications[{$studentid}]";

        $this->execute('behat_general::the_element_should_be_disabled', [$fieldlocator, 'field']);
    }

    /**
     * Checks that the calculator can be changed before grades are released.
     *
     * @Then /^I can change the calculator before grades are released$/
     *
     */
    public function i_can_change_the_calculator_before_grades_are_released() {
        // Behat steps are only relevant when more than one calculator plugin is installed.
        $calculators = core_component::get_plugin_list('peerworkcalculator');

        if (count($calculators) > 1) {
            $this->execute(
                'behat_general::should_not_exist_in_the',
                ['.alert-warning', 'css_element', 'Calculator settings', 'fieldset']
            );
            $this->execute('behat_general::should_not_exist', ['Recalculate grades', 'select']);
            $this->execute('behat_general::the_element_should_be_enabled', ['Calculator', 'select']);
        }
    }

    /**
     * Checks that the calculator can be changed after grades are released.
     *
     * @Then /^I can change the calculator after grades are released$/
     *
     */
    public function i_can_change_the_calculator_after_grades_are_released() {
        // Behat steps are only relevant when more than one calculator plugin is installed.
        $calculators = core_component::get_plugin_list('peerworkcalculator');

        if (count($calculators) > 1) {
            $this->execute('behat_general::the_element_should_be_enabled', ['Recalculate grades', 'select']);
            $this->execute('behat_general::the_element_should_be_disabled', ['Calculator', 'select']);
            $this->execute('behat_forms::i_set_the_field_to', ['Recalculate grades', 'Yes']);
            $this->execute('behat_general::the_element_should_be_enabled', ['Calculator', 'select']);
        }
    }

    /**
     * Checks that the disabled calculator is not updated before submissions are graded.
     *
     * @Then /^the disabled calculator is not updated before grading$/
     *
     */
    public function disabled_calculator_not_updated_before_grading() {
        // Behat steps are only relevant when more than one calculator plugin is installed.
        $calculators = core_component::get_plugin_list('peerworkcalculator');

        if (count($calculators) > 1) {
            $this->execute('behat_forms::the_field_matches_value', ['Calculator', 'Web PA']);
        }
    }

    /**
     * Checks that the disabled calculator is not updated after submissions are graded.
     *
     * @Then /^the disabled calculator is not updated after grading$/
     *
     */
    public function disabled_calculator_not_updated_after_grading() {
        // Behat steps are only relevant when more than one calculator plugin is installed.
        $calculators = core_component::get_plugin_list('peerworkcalculator');

        if (count($calculators) > 1) {
            $this->execute('behat_forms::the_field_matches_value', ['Calculator', 'Web PA']);
        }
    }

    /**
     * Returns the id of the student with the given username.
     *
     * Please note that this function requires the student to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $username
     * @return string
     * @throws ExpectationException
     */
    protected function get_student_id($username) {
        global $DB;
        try {
            return $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no student in the database with the username '%s'", $username));
        }
    }

    /**
     * Returns the id of the criteria with the given name.
     *
     * Please note that this function requires the criteria to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $description
     * @return string
     * @throws ExpectationException
     */
    protected function get_criteria_id($description) {
        global $DB;
        try {
            $sql = "SELECT id
                    FROM {peerwork_criteria}
                    WHERE " . $DB->sql_compare_text('description') . " = " . $DB->sql_compare_text(':description');
            $record = $DB->get_records_sql($sql, ['description' => $description]);

            // Function array_key_first() for PHP > 7.3.
            return current(array_keys($record));
        } catch (dml_missing_record_exception $ex) {
            throw new Exception(sprintf("There is no criteria in the database with the description '%s'", $name));
        }
    }

    /**
     * Remove the specified user from the group.
     * You should be in the groups page when running this step.
     * The user should be specified like "Firstname Lastname".
     *
     * @Given /^I remove "(?P<user_fullname_string>(?:[^"]|\\")*)" user from "(?P<group_name_string>(?:[^"]|\\")*)" group members$/
     *
     * @param string $userfullname
     * @param string $groupname
     * @throws ElementNotFoundException Thrown by behat_base::find
     *
     */
    public function i_remove_user_from_group_members($userfullname, $groupname) {

        $userfullname = behat_context_helper::escape($userfullname);

        // Using a xpath liternal to avoid problems with quotes and double quotes.
        $groupname = behat_context_helper::escape($groupname);

        // We don't know the option text as it contains the number of users in the group.
        $select = $this->find_field('groups');
        $xpath = "//select[@id='groups']/descendant::option[contains(., $groupname)]";
        $groupoption = $this->find('xpath', $xpath);
        $fulloption = $groupoption->getText();
        $select->selectOption($fulloption);

        // This is needed by some drivers to ensure relevant event is triggred and button is enabled.
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof \Moodle\BehatExtension\Driver\MoodleSelenium2Driver) {
            $script = "Syn.trigger('change', {}, {{ELEMENT}})";
            $driver->triggerSynScript($select->getXpath(), $script);
        }
        $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);

        // Here we don't need to wait for the AJAX response.
        $this->find_button(get_string('adduserstogroup', 'group'))->click();

        // Wait for add/remove members page to be loaded.
        $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);

        // Getting the option and selecting it.
        $select = $this->find_field('removeselect');
        $xpath = "//select[@id='removeselect']/descendant::option[contains(., $userfullname)]";
        $memberoption = $this->find('xpath', $xpath);
        $fulloption = $memberoption->getText();
        $select->selectOption($fulloption);

        // Click add button.
        $this->find_button(get_string('remove'))->click();

        // Wait for the page to load.
        $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);

        // Returning to the main groups page.
        $this->find_button(get_string('backtogroups', 'group'))->click();
    }
}
