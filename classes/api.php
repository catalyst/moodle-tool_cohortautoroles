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
 * Class exposing the api for the cohortautoroles tool.
 *
 * @package    tool_cohortautoroles
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_cohortautoroles;
defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_system;
use core_competency\invalid_persistent_exception;

/**
 * Class for doing things with cohort roles.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Create a cohort role assignment from a record containing all the data for the class.
     *
     * Requires moodle/role:manage capability at the system context.
     *
     * @param stdClass $record Record containing all the data for an instance of the class.
     * @return competency
     */
    public static function create_cohort_role_assignment(stdClass $record) {
        $cohortroleassignment = new cohort_role_assignment(0, $record);
        $context = context_system::instance();

        // First we do a permissions check.
        require_capability('moodle/role:manage', $context);

        // Validate before we check for existing records.
        if (!$cohortroleassignment->is_valid()) {
            throw new invalid_persistent_exception($cohortroleassignment->get_errors());
        }

        $existing = cohort_role_assignment::get_record((array) $record);
        if (!empty($existing)) {
            return false;
        } else {
            // OK - all set.
            $cohortroleassignment->create();
        }
        return $cohortroleassignment;
    }

    /**
     * Delete a cohort role assignment by id.
     *
     * Requires moodle/role:manage capability at the system context.
     *
     * @param int $id The record to delete. This will also remove this role from the user for all users in the system.
     * @return boolean
     */
    public static function delete_cohort_role_assignment($id) {
        $cohortroleassignment = new cohort_role_assignment($id);
        $context = context_system::instance();

        // First we do a permissions check.
        require_capability('moodle/role:manage', $context);

        // OK - all set.
        return $cohortroleassignment->delete();
    }

    /**
     * Perform a search based on the provided filters and return a paginated list of records.
     *
     * Requires moodle/role:manage capability at the system context.
     *
     * @param string $sort The column to sort on
     * @param string $order ('ASC' or 'DESC')
     * @param int $skip Number of records to skip (pagination)
     * @param int $limit Max of records to return (pagination)
     * @return array of cohort_role_assignment
     */
    public static function list_cohort_role_assignments($sort = '', $order = 'ASC', $skip = 0, $limit = 0) {
        $context = context_system::instance();

        // First we do a permissions check.
        require_capability('moodle/role:manage', $context);

        // OK - all set.
        return cohort_role_assignment::get_records(array(), $sort, $order, $skip, $limit);
    }

    /**
     * Perform a search based on the provided filters and return a paginated list of records.
     *
     * Requires moodle/role:manage capability at system context.
     *
     * @return int
     */
    public static function count_cohort_role_assignments() {
        $context = context_system::instance();

        // First we do a permissions check.
        require_capability('moodle/role:manage', $context);

        // OK - all set.
        return cohort_role_assignment::count_records();
    }

    /**
     * Sync all roles - adding and deleting role assignments as required.
     *
     * Slow. Should only be called from a background task.
     *
     * Requires moodle/role:manage capability at the system context.
     *
     * @return array('rolesadded' => array of (useridassignedto, useridassignedover, roleid),
     *               'rolesremoved' => array of (useridassignedto, useridassignedover, roleid))
     */
    public static function sync_all_cohort_roles() {
        global $DB;

        $context = context_system::instance();

        // First we do a permissions check.
        require_capability('moodle/role:manage', $context);

        // Ok ready to go.
        $rolesadded = array();
        $rolesremoved = array();

        // Get all cohort role assignments and group them by user and role.
        $all = cohort_role_assignment::get_records(array(), 'sysroleid, roleid');
        // We build an better structure to loop on.
        $info = array();
        foreach ($all as $cra) {
            if (!isset($info[$cra->get_roleid()])) {
                $info[$cra->get_roleid()] = array();
            }
            array_push($info[$cra->get_roleid()], $cra->get_cohortid());
        }

        // Warning - this intentionally gets role assignments at all levels, we should probably add a config option to allow
        // only roles assigned at the system level to be used.
        foreach ($info as $roleid => $cohorts) {
            list($cohortsql, $params) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED);
            list($cohortsql2, $params2) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED, 'param2');
            $params = array_merge($params, $params2);

            $params['usercontext'] = CONTEXT_USER;
            $params['roleid'] = $roleid;
            $params['sysroleid'] = $cra->get_sysroleid();
            $params['component'] = 'tool_cohortautoroles';

            $sql = 'SELECT '.$DB->sql_concat('u2.id', 'cm2.cohortid', 'mentors.userid') // Make first column unique.
                .',u2.id as userid, cm2.cohortid as cohortid, mentors.userid as mentorid, ctx.id as contextid, ra.id as roleassign
                      FROM {user} u2
                      JOIN {context} ctx on u2.id = ctx.instanceid AND ctx.contextlevel = :usercontext
                      JOIN {cohort_members} cm2 ON u2.id = cm2.userid AND cm2.cohortid '. $cohortsql2 .'
                      JOIN (SELECT u.id as userid, cm.cohortid
                              FROM {user} u
                              JOIN {cohort_members} cm ON u.id = cm.userid AND cm.cohortid '. $cohortsql. '
                              JOIN {role_assignments} sysra ON sysra.roleid = :sysroleid and u.id = sysra.userid) mentors
                                   ON mentors.cohortid = cm2.cohortid
                      LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id
                                                     AND ra.roleid = :roleid and ra.userid = mentors.userid
                                                     AND ra.component = :component
                      WHERE ra.id IS NULL';
            $toadd = $DB->get_records_sql($sql, $params);

            foreach ($toadd as $add) {
                mtrace('addroleid'.$roleid. ' mentor:'.$add->mentorid.' context:'.$add->contextid);
                role_assign($roleid, $add->mentorid, $add->contextid, 'tool_cohortautoroles');
                $rolesadded[] = array(
                    'useridassignedto' => $add->mentorid,
                    'useridassignedover' => $add->userid,
                    'roleid' => $roleid
                );
            }
        }

        // And for each user+role combo - find user context not in the cohort with a role assigned.
        // If the role was assigned by this component, unassign the role.
        foreach ($info as $roleid => $cohorts) {
            // Now we are looking for entries NOT in the cohort.
            list($cohortsql, $params) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED);
            list($cohortsql2, $params2) = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED, 'param2');
            $params = array_merge($params, $params2);

            $params['usercontext'] = CONTEXT_USER;
            $params['roleid'] = $roleid;
            $params['sysroleid'] = $cra->get_sysroleid();
            $params['component'] = 'tool_cohortautoroles';

            $sql = 'SELECT '.$DB->sql_concat('u2.id', 'cm2.cohortid', 'ctx.id', 'ra.userid') // Make first column unique.
                         . ', u2.id as userid, cm2.cohortid as cohortid, ctx.id as contextid
                            , ra.userid as mentorid, mentors.userid as mentoridset
                      FROM {user} u2
                      JOIN {context} ctx on u2.id = ctx.instanceid AND ctx.contextlevel = :usercontext
                      JOIN {cohort_members} cm2 ON u2.id = cm2.userid AND cm2.cohortid '. $cohortsql .'
                 JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.roleid = :roleid AND ra.component = :component
                 LEFT JOIN (SELECT u.id as userid, cm.cohortid
                            FROM {user} u
                            JOIN {cohort_members} cm ON u.id = cm.userid AND cm.cohortid '. $cohortsql2 . '
                            JOIN {role_assignments} sysra ON sysra.roleid = :sysroleid AND u.id = sysra.userid) mentors
                                 ON mentors.userid = ra.userid
                     WHERE mentors.userid IS NULL';
            $toremove = $DB->get_records_sql($sql, $params);
            foreach ($toremove as $remove) {
                mtrace('remove'.$roleid. ' mentor:'.$remove->mentorid.' context:'.$remove->contextid);
                role_unassign($roleid, $remove->mentorid, $remove->contextid, 'tool_cohortautoroles');
                $rolesremoved[] = array(
                    'useridassignedto' => $remove->mentorid,
                    'useridassignedover' => $remove->userid,
                    'roleid' => $roleid
                );
            }
        }

        return array('rolesadded' => $rolesadded, 'rolesremoved' => $rolesremoved);
    }

}
