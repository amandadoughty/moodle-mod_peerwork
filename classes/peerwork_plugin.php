<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Plugin class.
 *
 * @package    mod_peerwork
 * @copyright  2020 Amanda Doughty
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerwork;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class for peerwork_plugin (calculator).
 *
 * @package   mod_peerwork
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class peerwork_plugin {

    /** @var peerwork $peerwork the peerwork record */
    protected $peerwork;
    /** @var string $type peerwork plugin type */
    private $type = '';
    /** @var string $error error message */
    private $error = '';
    /** @var boolean|null $enabledcache Cached lookup of the is_enabled function */
    private $enabledcache = null;
    /** @var boolean|null $enabledcache Cached lookup of the is_visible function */
    private $visiblecache = null;

    /**
     * Constructor for the abstract plugin type class
     *
     * @param stdClass|null $peerwork
     * @param string $type
     *
     */
    final public function __construct($peerwork, $type) {
        $this->peerwork = $peerwork;
        $this->type = $type;
    }

    /**
     * Is this the first plugin in the list?
     *
     * @return bool
     */
    final public function is_first() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');

        if ($order == 0) {
            return true;
        }
        return false;
    }

    /**
     * Is this the last plugin in the list?
     *
     * @return bool
     */
    final public function is_last() {
        $lastindex = count(core_component::get_plugin_list($this->get_subtype())) - 1;
        $currentindex = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        if ($lastindex == $currentindex) {
            return true;
        }

        return false;
    }

    /**
     * This function should be overridden to provide an array of elements that can be added to a moodle
     * form for display in the settings page for the peerwork.
     * @param MoodleQuickForm $mform The form to add the elements to
     * @return $array
     */
    public function get_settings(\MoodleQuickForm $mform) {
        return;
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        return;
    }

    /**
     * The peerwork subtype is responsible for saving it's own settings as the database table for the
     * standard type cannot be modified.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(\stdClass $formdata) {
        return true;
    }

    /**
     * Save the error message from the last error
     *
     * @param string $msg - the error description
     */
    final protected function set_error($msg) {
        $this->error = $msg;
    }

    /**
     * What was the last error?
     *
     * @return string
     */
    final public function get_error() {
        return $this->error;
    }

    /**
     * Should return the name of this plugin type.
     *
     * @return string - the name
     */
    abstract public function get_name();

    /**
     * Should return the subtype of this plugin.
     *
     * @return string - either 'assignsubmission' or 'feedback'
     */
    abstract public function get_subtype();

    /**
     * Should return the type of this plugin.
     *
     * @return string - the type
     */
    final public function get_type() {
        return $this->type;
    }

    /**
     * Get the installed version of this plugin
     *
     * @return string
     */
    final public function get_version() {
        $version = get_config($this->get_subtype() . '_' . $this->get_type(), 'version');
        if ($version) {
            return $version;
        } else {
            return '';
        }
    }

    /**
     * Get the required moodle version for this plugin
     *
     * @return string
     */
    final public function get_requires() {
        $requires = get_config($this->get_subtype() . '_' . $this->get_type(), 'requires');
        if ($requires) {
            return $requires;
        } else {
            return '';
        }
    }

    /**
     * Save any custom data for this form submission
     *
     * @param stdClass $submissionorgrade - assign_submission or assign_grade.
     *              For submission plugins this is the submission data,
     *              for feedback plugins it is the grade data
     * @param stdClass $data - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save(stdClass $submissionorgrade, stdClass $data) {
        return true;
    }

    /**
     * Set this plugin to enabled
     *
     * @return bool
     */
    final public function enable() {
        $this->enabledcache = true;
        return $this->set_config('enabled', 1);
    }

    /**
     * Set this plugin to disabled
     *
     * @return bool
     */
    final public function disable() {
        $this->enabledcache = false;
        return $this->set_config('enabled', 0);
    }

    /**
     * Allows hiding this plugin if it is not enabled.
     *
     * @return bool - if false - this plugin will not accept calculator
     */
    public function is_enabled() {
        if ($this->enabledcache === null) {
            $this->enabledcache = $this->get_config('enabled');
        }
        return $this->enabledcache;
    }

    /**
     * Get the numerical sort order for this plugin
     *
     * @return int
     */
    final public function get_sort_order() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        return $order ? $order : 0;
    }

    /**
     * Is this plugin enabled?
     *
     * @return bool
     */
    final public function is_visible() {
        if ($this->visiblecache === null) {
            $disabled = get_config($this->get_subtype() . '_' . $this->get_type(), 'disabled');
            $this->visiblecache = !$disabled;
        }
        return $this->visiblecache;
    }

    /**
     * Has this plugin got a custom settings.php file?
     *
     * @return bool
     */
    final public function has_admin_settings() {
        global $CFG;

        $pluginroot = $CFG->dirroot . '/mod/peerwork/' . substr($this->get_subtype(), strlen('peerwork')) . '/' . $this->get_type();
        $settingsfile = $pluginroot . '/settings.php';
        return file_exists($settingsfile);
    }

    /**
     * Set a configuration value for this plugin
     *
     * @param string $name The config key
     * @param string $value The config value
     * @return bool
     */
    final public function set_config($name, $value) {
        global $DB;

        if ($this->peerwork) {
            $dbparams = [
                'peerwork' => $this->peerwork->id,
                'subtype' => $this->get_subtype(),
                'plugin' => $this->get_type(),
                'name' => $name
            ];
            $current = $DB->get_record('peerwork_plugin_config', $dbparams, '*', IGNORE_MISSING);

            if ($current) {
                $current->value = $value;
                return $DB->update_record('peerwork_plugin_config', $current);
            } else {
                $setting = new \stdClass();
                $setting->peerwork = $this->peerwork->id;
                $setting->subtype = $this->get_subtype();
                $setting->plugin = $this->get_type();
                $setting->name = $name;
                $setting->value = $value;

                return $DB->insert_record('peerwork_plugin_config', $setting) > 0;
            }
        }

        return false;
    }

    /**
     * Get a configuration value for this plugin
     *
     * @param mixed $setting The config key (string) or null
     * @return mixed string | false
     */
    final public function get_config($setting = null) {
        global $DB;

        if ($setting) {
            if ($this->peerwork) {
                $dbparams = [
                    'peerwork' => $this->peerwork->id,
                    'subtype' => $this->get_subtype(),
                    'plugin' => $this->get_type(),
                    'name' => $setting
                ];
                $result = $DB->get_record('peerwork_plugin_config', $dbparams, '*', IGNORE_MISSING);
                if ($result) {
                    return $result->value;
                }
            }
            return false;
        }

        $config = new \stdClass();

        if ($this->peerwork) {
            $dbparams = [
                'peerwork' => $this->peerwork->id,
                'subtype' => $this->get_subtype(),
                'plugin' => $this->get_type()
            ];
            $results = $DB->get_records('peerwork_plugin_config', $dbparams);

            if (is_array($results)) {
                foreach ($results as $setting) {
                    $name = $setting->name;
                    $config->$name = $setting->value;
                }
            }
        }

        return $config;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submissionorgrade assign_submission or assign_grade The new submission or grade
     * @return string
     */
    public function format_for_log(stdClass $submissionorgrade) {
        // Format the info for each submission plugin add_to_log.
        return '';
    }

    /**
     * The peerwork has been deleted - remove the plugin specific data
     *
     * @return bool
     */
    public function delete_instance() {
        return true;
    }

    /**
     * If this plugin can participate in a webservice (save_submission or save_grade),
     * return a list of external_params to be included in the definition of that webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        return null;
    }

    /**
     * If true, the plugin will appear on the module settings page and can be
     * enabled/disabled per peerwork instance.
     *
     * @return bool
     */
    public function is_configurable() {
        return true;
    }

    /**
     * Return the plugin configs for external functions,
     * in some cases the configs will need formatting or be returned only if the current user has some capabilities enabled.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return array();
    }
}
