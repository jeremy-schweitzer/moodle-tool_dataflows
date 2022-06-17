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
 * Trigger dataflow settings.
 *
 * @package    tool_dataflows
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_dataflows\steps_table;
use tool_dataflows\visualiser;
use tool_dataflows\dataflow;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

require_login();

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('tool_dataflows_overview', '', null, '', ['pagelayout' => 'report']);

$context = context_system::instance();

// Check for caps.
require_capability('tool/dataflows:managedataflows', $context);

$url = new moodle_url('/admin/tool/dataflows/view.php', ['id' => $id]);

// Configure any table specifics.
$table = new steps_table('dataflows_table');
$sqlfields = 'step.id,
              usr.*,
              step.*';
$sqlfrom = '{tool_dataflows_steps} step
  LEFT JOIN {user} usr
         ON usr.id = step.userid';
$sqlwhere = 'dataflowid = :dataflowid';
$sqlparams = ['dataflowid' => $id];
$table->set_sql($sqlfields, $sqlfrom, $sqlwhere, $sqlparams);
$table->make_columns();

// Configure the breadcrumb navigation.
$dataflow = new dataflow($id);

// Add the current page to the breadcrumb trail.
// We are unable to use breadcrumb_navigation() here because admin_externalpage_setup() already sets up a breadcrumb trail.
$PAGE->navbar->add($dataflow->name, new moodle_url('/admin/tool/dataflows/view.php', ['id' => $id]));

visualiser::display_steps_table($id, $table, $url, get_string('steps', 'tool_dataflows'));
