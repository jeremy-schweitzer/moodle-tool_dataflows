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

use tool_dataflows\dataflow;
use tool_dataflows\step;

/**
 * Class for storing and managing the variables tree for a dataflow.
 *
 * @package   tool_dataflows
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class variables {

    private const PLACEHOLDER = '__PLACEHOLDER__';
    private const REPEAT_LIMIT = 100;

    private $variables;
    private $dataflow;
    private $isvalid = false;
    private $tree = null;

    public function __construct(dataflow $dataflow) {
        $this->dataflow = $dataflow;
        $this->variables = new \stdClass();

        $globalvars = new \stdClass();
        $globalvars->cfg = helper::get_cfg_vars();
        $globalvars->vars = Yaml::parse(get_config('tool_dataflows', 'global_vars'), Yaml::PARSE_OBJECT_FOR_MAP)
                ?? new \stdClass();

        $this->variables->global = $globalvars;

        $dataflowvars = new \stdClass();
        $dataflowvars->vars = $dataflow->get_vars(false);
        $dataflowvars->name = $dataflow->name;
        $dataflowvars->config = new \stdClass();
        $dataflowvars->config->enabled = $dataflow->enabled;
        $dataflowvars->config->concurrencyenabled = $dataflow->concurrencyenabled;
        $this->variables->dataflow = $globalvars;

        $this->variables->steps = new \stdClass();
        foreach ($dataflow->steps as $stepdef) {
            $this->variables->steps->{$stepdef->alias} = new variables_step($stepdef, $this);
        }
    }

    public function get_tree() {
        if (!$this->isvalid) {
            $this->reconstruct();
        }
        return $this->tree;
    }

    public function get($name) {
        if (!$this->isvalid) {
            $this->reconstruct();
        }
        $levels = explode('.', $name);
        return $this->get_at_level($this->tree, $levels);
    }

    protected function get_at_level(object $obj, array $levels) {
        if (!isset($obj->{$levels[0]})) {
            return null;
        }
        if (count($levels) == 1) {
            return $obj->{$levels[0]};
        }
        return $this->get_at_level($obj->{$levels[0]}, array_slice($levels, 1));
    }

    public function reconstruct(?step $currentstep = null) {
        // Make a clean copy of the current variable definitions.
        $this->tree = new \stdClass();
        $this->clone($this->tree, $this->variables);
        if (isset($currentstep)) {
            // TODO copy step into tree root.
        }

        // Go through the tree and resolve expression. Do this repeatedly to catch expressions that resolve into expressions.
        for ($i = 0; $i < self::REPEAT_LIMIT; ++$i) {
            if (!$this->tree_walk('', $this->tree)) {
                break;
            }
        }
        $this->isvalid = true;
    }

    private function clone(object $newtree, object $oldtree) {
        foreach ($oldtree as $key => $value) {
            if (is_object($value)) {
                $newtree->$key = new \stdClass();
                $this->clone($newtree->$key, $oldtree->$key);
            } else {
                $newtree->$key = $value;
            }
        }
    }

    private function tree_walk(string $name, object $tree): bool {
        $foundexpression = false;
        foreach ($tree as $key => &$value) {
            if (is_object($value)) {
                $foundexpression |= $this->tree_walk("$name.$key", $value);
            } else {
                $foundexpression |= $this->resolve_expression("$name.$key", $value);
            }
        }
        return $foundexpression;
    }

    private function resolve_expression(string $name, &$value): bool {
        $parser = parser::get_parser();

        if (!$parser->has_expression($value))) {
            return false;
        }
        $resolved = $parser->evaluate($value, $this->tree);
        if ($resolved === self::PLACEHOLDER) {
            throw new \moodle_exception(
                'recursiveexpressiondetected',
                'tool_dataflows',
                '',
                ltrim($name, '.');
            );
        }
        if (isset($resolved)) {
            $value = $resolved;
        }
        return true;
    }

    public function invalidate() {
        $this->isvalid = false;
    }
}
