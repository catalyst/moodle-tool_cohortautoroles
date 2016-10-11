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
 * Assign roles for a user.
 *
 * @package    tool_cohortautoroles
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

$removeid = optional_param('removecohortroleassignment', 0, PARAM_INT);

admin_externalpage_setup('toolcohortautoroles');
$context = context_system::instance();

$pageurl = new moodle_url('/admin/tool/cohortautoroles/index.php');

$output = $PAGE->get_renderer('tool_cohortautoroles');

echo $output->header();
$title = get_string('assignroletocohort', 'tool_cohortautoroles');
echo $output->heading($title);

$form = new tool_cohortautoroles\form\assign_role_cohort();

if ($removeid) {
    require_sesskey();

    $result = \tool_cohortautoroles\api::delete_cohort_role_assignment($removeid);
    if ($result) {
        $notification = get_string('cohortroleassignmentremoved', 'tool_cohortautoroles');
        echo $output->notify_success($notification);
    } else {
        $notification = get_string('cohortroleassignmentnotremoved', 'tool_cohortautoroles');
        echo $output->notify_problem($notification);
    }
    echo $output->continue_button(new moodle_url($pageurl));
} else if ($data = $form->get_data()) {
    require_sesskey();
    // We must create them all or none.
    $saved = 0;
    // Loop through userids and cohortids only if both of them are not empty.
    if (!empty($data->cohortids)) {
        foreach ($data->cohortids as $cohortid) {
            $params = (object) array('sysroleid' => $data->sysroleid, 'cohortid' => $cohortid, 'roleid' => $data->roleid);
            $result = \tool_cohortautoroles\api::create_cohort_role_assignment($params);
            if ($result) {
                $saved++;
            }
        }
    }
    if ($saved == 0) {
        $notification = get_string('nocohortroleassignmentssaved', 'tool_cohortautoroles');
        echo $output->notify_problem($notification);
    } else if ($saved == 1) {
        $notification = get_string('onecohortroleassignmentsaved', 'tool_cohortautoroles');
        echo $output->notify_success($notification);
    } else {
        $notification = get_string('acohortroleassignmentssaved', 'tool_cohortautoroles', $saved);
        echo $output->notify_success($notification);
    }

    echo $output->continue_button(new moodle_url($pageurl));
} else {
    $form->display();

    $title = get_string('existingcohortautoroles', 'tool_cohortautoroles');
    echo $output->heading($title);
    $url = new moodle_url('/admin/tool/cohortautoroles/index.php');
    $table = new tool_cohortautoroles\output\cohort_role_assignments_table(uniqid(), $url);
    echo $table->out(50, true);

    echo $output->spacer();
    echo $output->notify_message(get_string('backgroundsync', 'tool_cohortautoroles'));
}

echo $output->footer();

