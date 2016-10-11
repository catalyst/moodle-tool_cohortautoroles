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
 * Assign role to cohort form.
 *
 * @package    tool_cohortautoroles
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cohortautoroles\form;
defined('MOODLE_INTERNAL') || die();

use moodleform;
use context_system;

require_once($CFG->libdir . '/formslib.php');

/**
 * Assign role to cohort form.
 *
 * @package    tool_cohortautoroles
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_role_cohort extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $userroles = get_roles_for_contextlevels(CONTEXT_USER);
        $sysroles = get_all_roles();
        $removeroles = array('user', 'guest');
        foreach ($sysroles as $sid => $role) {
            // Remove a few roles from the list that make no sense in using and could cause performance issues.
            if (in_array($role->shortname, $removeroles)) {
                unset($sysroles[$sid]);
            }
        }
        $names = role_get_names();

        if (empty($userroles)) {
            $output = $PAGE->get_renderer('tool_cohortautoroles');
            $warning = $output->notify_problem(get_string('noassignableroles', 'tool_cohortautoroles'));
            $mform->addElement('html', $warning);
            return;
        }

        $options = array();
        foreach ($sysroles as $roleid => $role) {
            $options[$roleid] = $names[$roleid]->localname;
        }
        $mform->addElement('select', 'sysroleid', get_string('selectsysrole', 'tool_cohortautoroles'), $options);
        $mform->addRule('sysroleid', null, 'required');

        $options = array();
        foreach ($userroles as $idx => $roleid) {
            $options[$roleid] = $names[$roleid]->localname;
        }
        $mform->addElement('select', 'roleid', get_string('selectrole', 'tool_cohortautoroles'), $options);
        $mform->addRule('roleid', null, 'required');

        $context = context_system::instance();
        $options = array(
            'ajax' => 'tool_lp/form-cohort-selector',
            'multiple' => true,
            'data-contextid' => $context->id,
            'data-includes' => 'all'
        );
        $mform->addElement('autocomplete', 'cohortids', get_string('selectcohorts', 'tool_cohortautoroles'), array(), $options);
        $mform->addRule('cohortids', null, 'required');
        $mform->addElement('submit', 'submit', get_string('assign', 'tool_cohortautoroles'));
    }

}
