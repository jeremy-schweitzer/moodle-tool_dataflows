<?php
// This file is part of Moodle - https://moodle.org/
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
 * Abort Step ..Trait
 *
 * This trait allows both flow/connector implementations to share core
 * functionality. This should be moved to the "main" step type in an ideal
 * world, and live there directly instead, but will need to be done as such
 * until support for dual steps are fully supported.
 *
 * @package    tool_dataflows
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_dataflows\local\step;

trait abort_trait {

    /**
     * Executes the step, aborting the whole dataflow.
     *
     * @param mixed|null $input
     * @return mixed
     */
    public function execute($input = null) {
        $config = $this->get_config();

        // If the condition was left empty (e.g. empty string), it should always abort.
        if ($config->condition === '') {
            throw new \Exception('Aborting');
        }

        // If a condition was set, it should not abort if the result is false. be evaluated and abort if 'true'.
        if ($config->condition === false) {
            return $input;
        }
        var_dump($config->condition);

        throw new \Exception('Aborting');
    }

    /**
     * Return the definition of the fields available in this form.
     *
     * @return array
     */
    public static function form_define_fields(): array {
        return [
            'condition' => ['type' => PARAM_TEXT, 'required' => true],
        ];
    }

    /**
     * Inputs
     *
     * @param \MoodleQuickForm $mform
     */
    public function form_add_custom_inputs(\MoodleQuickForm &$mform) {
        $mform->addElement('text', 'config_condition', get_string('connector_abort_if:condition', 'tool_dataflows'));
    }
}
