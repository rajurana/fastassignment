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
 * Privacy class for requesting user data.
 *
 * @package    fastassignfeedback_comments
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace fastassignfeedback_comments\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \mod_fastassignment\privacy\fastassignment_plugin_request_data;
use \mod_fastassignment\privacy\useridlist;

/**
 * Privacy class for requesting user data.
 *
 * @package    fastassignfeedback_comments
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \mod_fastassignment\privacy\fastassignfeedback_provider,
        \mod_fastassignment\privacy\fastassignfeedback_user_provider {

    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection) : collection {
        $data = [
            'assignment' => 'privacy:metadata:assignmentid',
            'grade' => 'privacy:metadata:gradepurpose',
            'commenttext' => 'privacy:metadata:commentpurpose'
        ];
        $collection->add_database_table('fastassignfeedback_comments', $data, 'privacy:metadata:tablesummary');
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');

        return $collection;
    }

    /**
     * No need to fill in this method as all information can be acquired from the fastassignment_grades table in the mod fastassignment
     * provider.
     *
     * @param  int $userid The user ID.
     * @param  contextlist $contextlist The context list.
     */
    public static function get_context_for_userid_within_feedback(int $userid, contextlist $contextlist) {
        // This uses the fastassignment_grades table.
    }

    /**
     * This also does not need to be filled in as this is already collected in the mod fastassignment provider.
     *
     * @param  useridlist $useridlist A list of user IDs
     */
    public static function get_student_user_ids(useridlist $useridlist) {
        // Not required.
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the fastassignment_grades table then please fill in this method.
     *
     * @param  \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // Not required.
    }

    /**
     * Export all user data for this plugin.
     *
     * @param  fastassignment_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_feedback_user_data(fastassignment_plugin_request_data $exportdata) {
        // Get that comment information and jam it into that exporter.
        $fastassignment = $exportdata->get_assign();
        $plugin = $fastassignment->get_plugin_by_type('fastassignfeedback', 'comments');
        $gradeid = $exportdata->get_pluginobject()->id;
        $comments = $plugin->get_feedback_comments($gradeid);
        if ($comments && !empty($comments->commenttext)) {
            $currentpath = array_merge(
                $exportdata->get_subcontext(),
                [get_string('privacy:commentpath', 'fastassignfeedback_comments')]
            );

            $comments->commenttext = writer::with_context($fastassignment->get_context())->rewrite_pluginfile_urls(
                $currentpath,
                FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
                FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
                $gradeid,
                $comments->commenttext
            );
            $data = (object)
            [
                'commenttext' => format_text($comments->commenttext, $comments->commentformat,
                    ['context' => $exportdata->get_context()])
            ];
            writer::with_context($exportdata->get_context())->export_data($currentpath, $data);
            writer::with_context($exportdata->get_context())->export_area_files($currentpath,
                FASTASSIGNFEEDBACK_COMMENTS_COMPONENT, FASTASSIGNFEEDBACK_COMMENTS_FILEAREA, $gradeid);
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param  fastassignment_plugin_request_data $requestdata Data useful for deleting user data from this sub-plugin.
     */
    public static function delete_feedback_for_context(fastassignment_plugin_request_data $requestdata) {
        $fastassignment = $requestdata->get_assign();
        $fs = get_file_storage();
        $fs->delete_area_files($requestdata->get_context()->id, FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            FASTASSIGNFEEDBACK_COMMENTS_FILEAREA);

        $plugin = $fastassignment->get_plugin_by_type('fastassignfeedback', 'comments');
        $plugin->delete_instance();
    }

    /**
     * Calling this function should delete all user data associated with this grade entry.
     *
     * @param  fastassignment_plugin_request_data $requestdata Data useful for deleting user data.
     */
    public static function delete_feedback_for_grade(fastassignment_plugin_request_data $requestdata) {
        global $DB;

        $fs = new \file_storage();
        $fs->delete_area_files($requestdata->get_context()->id, FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            FASTASSIGNFEEDBACK_COMMENTS_FILEAREA, $requestdata->get_pluginobject()->id);

        $DB->delete_records('fastassignfeedback_comments', ['assignment' => $requestdata->get_assignid(),
                'grade' => $requestdata->get_pluginobject()->id]);
    }

    /**
     * Deletes all feedback for the grade ids / userids provided in a context.
     * fastassignment_plugin_request_data contains:
     * - context
     * - fastassignment object
     * - grade ids (pluginids)
     * - user ids
     * @param  fastassignment_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_feedback_for_grades(fastassignment_plugin_request_data $deletedata) {
        global $DB;
        if (empty($deletedata->get_gradeids())) {
            return;
        }

        list($sql, $params) = $DB->get_in_or_equal($deletedata->get_gradeids(), SQL_PARAMS_NAMED);

        $fs = new \file_storage();
        $fs->delete_area_files_select(
                $deletedata->get_context()->id,
                FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
                FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
                $sql,
                $params
            );

        $params['assignment'] = $deletedata->get_assignid();
        $DB->delete_records_select('fastassignfeedback_comments', "assignment = :assignment AND grade $sql", $params);
    }
}
