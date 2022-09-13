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

namespace tool_dataflows\local\step;

use tool_dataflows\helper;

/**
 * Hash file flow step
 *
 * @package    tool_dataflows
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flow_hash_file extends flow_step {

    /** @var int[] number of output flows (min, max). */
    protected $outputflows = [0, 1];

    /** @var int[] number of output connectors (min, max). */
    protected $outputconnectors = [0, 1];

    /**
     * Return the definition of the fields available in this form.
     *
     * @return array
     */
    public static function form_define_fields(): array {
        return [
            'path'      => ['type' => PARAM_TEXT, 'required' => true],
            'algorithm' => ['type' => PARAM_TEXT, 'required' => true],
        ];
    }

    /**
     * Allows each step type to determine a list of optional/required form
     * inputs for their configuration
     *
     * It's recommended you prefix the additional config related fields to avoid
     * conflicts with any existing fields.
     *
     * @param \MoodleQuickForm $mform
     */
    public function form_add_custom_inputs(\MoodleQuickForm &$mform) {
        // Path to the file.
        $mform->addElement('text', 'config_path', get_string('flow_hash_file:path', 'tool_dataflows'));
        // Hashing algorithm.
        $mform->addElement('text', 'config_algorithm', get_string('flow_hash_file:algorithm', 'tool_dataflows'));
        // Help text listing all the possible algorithms available.
        $mform->addElement(
            'static',
            'config_algorithm_help',
            '',
            get_string('flow_hash_file:algorithm_help', 'tool_dataflows', implode(', ', hash_algos()))
        );
    }

    /**
     * Executes the step
     *
     * This will take the input and perform S3 interaction functions.
     *
     * @return bool Returns true if successful, false otherwise.
     */
    public function execute($input = null) {
        $config = $this->get_config();
        $filename = $this->enginestep->engine->resolve_path($config->path);
        $hash = hash_file($config->algorithm, $filename);
        $this->log("The file hash value: {$hash}");
        $this->set_variables('hash', $hash);
        return $input;
    }

    /**
     * Perform any extra validation that is required only for runs.
     *
     * @return true|array Will return true or an array of errors.
     */
    public function validate_for_run() {
        $config = $this->stepdef->config;

        $errors = [];

        $error = helper::path_validate($config->path);
        if ($error !== true) {
            $errors['config_path'] = $error;
        }

        return empty($errors) ? true : $errors;
    }

}
