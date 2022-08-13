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

namespace tool_dataflows\local\execution;

use tool_dataflows\step;

/**
 * Manager for step variables.
 *
 * @package   tool_dataflows
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class variables_step {

    private $vars;
    private $stepdef;
    private $vm;

    public function __construct(step $stepdef, variables $vm) {
        $this->stepdef = $stepdef;
        $this->vm = $vm;
        $this->vars = new \stdClass();

        $this->vars->name = $stepdef->name;
        $this->vars->alias = $stepdef->alias;
        $this->vars->type = $stepdef->type;
        $this->vars->config = $stepdef->get_raw_config();
        $this->vars->vars = $stepdef->vars;
    }

    /**
     * Sets a variable in the tree.
     *
     * @param string $name The name of the variable is dot format (e.g. 'config.destination').
     * @param mixed $value.
     */
    public function set(string $name, $value) {
        $levels = explode('.', $name);
        $this->set_at_level($this->vars, $levels, $value);
        $this->vm->invalidate();
    }

    /**
     * Sets a variable using an array to define the name
     *
     * @param object $vars
     * @param array $levels
     * @param mixed $value
     */
    private function set_at_level(object $vars, array $levels, $value) {
        if (count($levels) == 1) {
            $vars->{$levels[0]} = $value;
        } else {
            if (isset($vars->{$levels[0]})) {
                if (!is_object($vars->{$levels[0]})) {
                    throw new \moodle_exception('trying to dereference through a non-object.');
                }
            } else {
                $vars->{$levels[0]} = new \stdClass();
            }
            $this->set_at_level($vars->{$levels[0]}, array_splice($levels, 1), $value);
        }
    }

    /**
     * Gets a variable
     *
     * @param string $name The name in dot format. Could be relative to the step or global.
     * @return mixed
     */
    public function get(string $name) {
        // First we try $name as a local reference.
        $value = $this->vm->get("steps.{$this->stepdef->alias}.$name");
        if ($value !== null) {
            return $value;
        }

        // Then we try it as a global one.
        return $this->vm->get($name);
    }

    /**
     * Magic getter. Used by variabels class. Should not be called elsewhere.
     *
     * @param string $p
     * @return mixed
     */
    public function __get(string $p) {
        return $this->vars->$p;
    }
}
