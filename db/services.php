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
 * Web service for mod fastassignment
 * @package    mod_fastassignment
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

        'mod_fastassignment_copy_previous_attempt' => array(
            'classname'     => 'mod_fastassignment_external',
            'methodname'    => 'copy_previous_attempt',
            'classpath'     => 'mod/fastassignment/externallib.php',
            'description'   => 'Copy a students previous attempt to a new attempt.',
            'type'          => 'write',
            'capabilities'  => 'mod/fastassignment:view, mod/fastassignment:submit'
        ),

        'mod_fastassignment_get_grades' => array(
                'classname'   => 'mod_fastassignment_external',
                'methodname'  => 'get_grades',
                'classpath'   => 'mod/fastassignment/externallib.php',
                'description' => 'Returns grades from the assignment',
                'type'        => 'read',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_get_assignments' => array(
                'classname'   => 'mod_fastassignment_external',
                'methodname'  => 'get_assignments',
                'classpath'   => 'mod/fastassignment/externallib.php',
                'description' => 'Returns the courses and assignments for the users capability',
                'type'        => 'read',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_get_submissions' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'get_submissions',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Returns the submissions for assignments',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_get_user_flags' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'get_user_flags',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Returns the user flags for assignments',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_set_user_flags' => array(
                'classname'   => 'mod_fastassignment_external',
                'methodname'  => 'set_user_flags',
                'classpath'   => 'mod/fastassignment/externallib.php',
                'description' => 'Creates or updates user flags',
                'type'        => 'write',
                'capabilities'=> 'mod/fastassignment:grade',
                'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_get_user_mappings' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'get_user_mappings',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Returns the blind marking mappings for assignments',
                'type' => 'read',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_revert_submissions_to_draft' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'revert_submissions_to_draft',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Reverts the list of submissions to draft status',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_lock_submissions' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'lock_submissions',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Prevent students from making changes to a list of submissions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_unlock_submissions' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'unlock_submissions',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Allow students to make changes to a list of submissions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_save_submission' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'save_submission',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Update the current students submission',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_submit_for_grading' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'submit_for_grading',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Submit the current students assignment for grading',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_save_grade' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'save_grade',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Save a grade update for a single student.',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_save_grades' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'save_grades',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Save multiple grade updates for an assignment.',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_save_user_extensions' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'save_user_extensions',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Save a list of assignment extensions',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_reveal_identities' => array(
                'classname' => 'mod_fastassignment_external',
                'methodname' => 'reveal_identities',
                'classpath' => 'mod/fastassignment/externallib.php',
                'description' => 'Reveal the identities for a blind marking assignment',
                'type' => 'write',
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_view_grading_table' => array(
                'classname'     => 'mod_fastassignment_external',
                'methodname'    => 'view_grading_table',
                'classpath'     => 'mod/fastassignment/externallib.php',
                'description'   => 'Trigger the grading_table_viewed event.',
                'type'          => 'write',
                'capabilities'  => 'mod/fastassignment:view, mod/fastassignment:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_view_submission_status' => array(
            'classname'     => 'mod_fastassignment_external',
            'methodname'    => 'view_submission_status',
            'classpath'     => 'mod/fastassignment/externallib.php',
            'description'   => 'Trigger the submission status viewed event.',
            'type'          => 'write',
            'capabilities'  => 'mod/fastassignment:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_get_submission_status' => array(
            'classname'     => 'mod_fastassignment_external',
            'methodname'    => 'get_submission_status',
            'classpath'     => 'mod/fastassignment/externallib.php',
            'description'   => 'Returns information about an assignment submission status for a given user.',
            'type'          => 'read',
            'capabilities'  => 'mod/fastassignment:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_list_participants' => array(
                'classname'     => 'mod_fastassignment_external',
                'methodname'    => 'list_participants',
                'classpath'     => 'mod/fastassignment/externallib.php',
                'description'   => 'List the participants for a single assignment, with some summary info about their submissions.',
                'type'          => 'read',
                'ajax'          => true,
                'capabilities'  => 'mod/fastassignment:view, mod/fastassignment:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

        'mod_fastassignment_submit_grading_form' => array(
                'classname'     => 'mod_fastassignment_external',
                'methodname'    => 'submit_grading_form',
                'classpath'     => 'mod/fastassignment/externallib.php',
                'description'   => 'Submit the grading form data via ajax',
                'type'          => 'write',
                'ajax'          => true,
                'capabilities'  => 'mod/fastassignment:grade',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),
        'mod_fastassignment_get_participant' => array(
                'classname'     => 'mod_fastassignment_external',
                'methodname'    => 'get_participant',
                'classpath'     => 'mod/fastassignment/externallib.php',
                'description'   => 'Get a participant for an assignment, with some summary info about their submissions.',
                'type'          => 'read',
                'ajax'          => true,
                'capabilities'  => 'mod/fastassignment:view, mod/fastassignment:viewgrades',
                'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),
        'mod_fastassignment_view_assign' => array(
            'classname'     => 'mod_fastassignment_external',
            'methodname'    => 'view_assign',
            'classpath'     => 'mod/fastassignment/externallib.php',
            'description'   => 'Update the module completion status.',
            'type'          => 'write',
            'capabilities'  => 'mod/fastassignment:view',
            'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
        ),

);