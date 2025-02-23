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

use tool_dataflows\local\variables\var_root;
use tool_dataflows\local\variables\var_object_visible;

trait set_variable_trait {

    /**
     * Executes the step, fetcing the config and actioning the step.
     *
     * @param mixed|null $input
     * @return mixed
     */
    public function execute($input = null) {
        $stepvars = $this->get_variables();
        $config = $stepvars->get('config');
        $rootvars = $this->get_variables_root();
        $this->run($rootvars, $config->field, $config->value);

        return $input;
    }

    /**
     * Main handler of this step, split out to make it easier to test.
     *
     * @param var_root $varobject
     * @param string $field
     * @param mixed $value
     */
    public function run(var_root $varobject, string $field, $value) {
        // Check and persist the change if the field points to a dataflow vars.
        $levels = var_object_visible::name_to_levels($field);
        if (
            count($levels) > 2
            && $levels[0] === 'dataflow'
            && $levels[1] === 'vars'
        ) {
            $this->persist_dataflow_vars($levels, $value);
        }

        // Set the value in the variable tree.
        $varobject->set($field, $value);
    }

    /**
     * Set and save the dataflow vars' field
     *
     * @param array $levels
     * @param mixed $value
     */
    public function persist_dataflow_vars($levels, $value) {
        $dataflow = $this->stepdef->dataflow;
        $vars = $dataflow->vars;
        $lastlevel = end($levels);

        // Set the variables as expected.
        $currentlevel = $vars;
        for ($i = 2; $i < count($levels); $i++) {
            if ($lastlevel !== $levels[$i]) {
                $currentlevel->{$levels[$i]} = new \stdClass;
                $currentlevel = $currentlevel->{$levels[$i]};
            } else {
                $currentlevel->{$levels[$i]} = $value;
            }
        }

        // Save the dataflow.
        $dataflow->set_dataflow_vars($vars);
        $dataflow->save();
    }

    /**
     * Return the definition of the fields available in this form.
     *
     * @return array
     */
    public static function form_define_fields(): array {
        return [
            'field' => ['type' => PARAM_TEXT, 'required' => true],
            'value' => ['type' => PARAM_TEXT, 'required' => true],
        ];
    }

    /**
     * Custom form inputs
     *
     * @param \MoodleQuickForm $mform
     */
    public function form_add_custom_inputs(\MoodleQuickForm &$mform) {
        $mform->addElement('text', 'config_field', get_string('set_variable:field', 'tool_dataflows'));
        $mform->addElement('static', 'config_field_help', '', get_string('set_variable:field_help', 'tool_dataflows'));

        $mform->addElement('text', 'config_value', get_string('set_variable:value', 'tool_dataflows'));
        $mform->addElement('static', 'config_field_help', '', get_string('set_variable:value_help', 'tool_dataflows'));
    }
}
