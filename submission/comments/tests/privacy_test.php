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
 * Unit tests for fastassignsubmission_comments.
 *
 * @package    fastassignsubmission_comments
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/fastassignment/tests/privacy_test.php');

/**
 * Unit tests for mod/fastassignment/submission/comments/classes/privacy/
 *
 * @copyright  2018 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fastassignsubmission_comments_privacy_testcase extends \mod_fastassignment\tests\mod_fastassignment_privacy_testcase {

    /**
     * Convenience function for creating feedback data.
     *
     * @param  object   $fastassignment         fastassignment object
     * @param  stdClass $student        user object
     * @param  string   $submissiontext Submission text
     * @return array   Submission plugin object and the submission object and the comment object.
     */
    protected function create_comment_submission($fastassignment, $student, $submissiontext) {

        $submission = $fastassignment->get_user_submission($student->id, true);

        $plugin = $fastassignment->get_submission_plugin_by_type('comments');

        $context = $fastassignment->get_context();
        $options = new stdClass();
        $options->area = 'submission_comments';
        $options->course = $fastassignment->get_course();
        $options->context = $context;
        $options->itemid = $submission->id;
        $options->component = 'fastassignsubmission_comments';
        $options->showcount = true;
        $options->displaycancel = true;

        $comment = new comment($options);
        $comment->set_post_permission(true);

        $this->setUser($student);

        $comment->add($submissiontext);

        return [$plugin, $submission, $comment];
    }

    /**
     * Quick test to make sure that get_metadata returns something.
     */
    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('fastassignsubmission_comments');
        $collection = \fastassignsubmission_comments\privacy\provider::get_metadata($collection);
        $this->assertNotEmpty($collection);
    }

    /**
     * Test returning the context for a user who has made a comment in an assignment.
     */
    public function test_get_context_for_userid_within_submission() {
        $this->resetAfterTest();
        // Create course, assignment, submission, and then a feedback comment.
        $course = $this->getDataGenerator()->create_course();
        // Student.
        $user1 = $this->getDataGenerator()->create_user();
        // Teacher.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'editingteacher');
        $fastassignment = $this->create_instance(['course' => $course]);

        $context = $fastassignment->get_context();

        $studentcomment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($fastassignment, $user1, $studentcomment);
        $teachercomment = 'From the teacher';
        $this->setUser($user2);
        $comment->add($teachercomment);

        $contextlist = new \core_privacy\local\request\contextlist();
        \fastassignsubmission_comments\privacy\provider::get_context_for_userid_within_submission($user2->id, $contextlist);
        $this->assertEquals($context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Test returning student ids given a user ID.
     */
    public function test_get_student_user_ids() {
        $this->resetAfterTest();
        // Create course, assignment, submission, and then a feedback comment.
        $course = $this->getDataGenerator()->create_course();
        // Student.
        $user1 = $this->getDataGenerator()->create_user();
        // Teacher.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'editingteacher');
        $fastassignment = $this->create_instance(['course' => $course]);

        $context = $fastassignment->get_context();

        $studentcomment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($fastassignment, $user1, $studentcomment);
        $teachercomment = 'From the teacher';
        $this->setUser($user2);
        $comment->add($teachercomment);

        $useridlist = new mod_fastassignment\privacy\useridlist($user2->id, $fastassignment->get_instance()->id);
        \fastassignsubmission_comments\privacy\provider::get_student_user_ids($useridlist);
        $this->assertEquals($user1->id, $useridlist->get_userids()[0]->id);
    }

    /**
     * Test returning users related to a given context.
     */
    public function test_get_userids_from_context() {
        // Get a bunch of users making comments.
        // Some in one context some in another.
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        // Only in first context.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // First and second context.
        $user3 = $this->getDataGenerator()->create_user();
        // Second context only.
        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $assign1 = $this->create_instance(['course' => $course]);
        $assign2 = $this->create_instance(['course' => $course]);

        $assigncontext1 = $assign1->get_context();
        $assigncontext2 = $assign2->get_context();

        $user1comment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($assign1, $user1, $user1comment);
        $user2comment = 'From user 2';
        $this->setUser($user2);
        $comment->add($user2comment);
        $user3comment = 'User 3 comment';
        $this->setUser($user3);
        $comment->add($user3comment);
        $user4comment = 'Comment from user 4';
        list($plugin, $submission, $comment) = $this->create_comment_submission($assign2, $user4, $user4comment);
        $user3secondcomment = 'Comment on user 4 post.';
        $this->setUser($user3);
        $comment->add($user3comment);

        $userlist = new \core_privacy\local\request\userlist($assigncontext1, 'fastassignsubmission_comments');
        \fastassignsubmission_comments\privacy\provider::get_userids_from_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertCount(3, $userids);
        // User 1,2 and 3 are the expected ones in the array. User 4 isn't.
        $this->assertContains($user1->id, $userids);
        $this->assertContains($user2->id, $userids);
        $this->assertContains($user3->id, $userids);
        $this->assertNotContains($user4->id, $userids);
    }

    /**
     * Test that comments are exported for a user.
     */
    public function test_export_submission_user_data() {
        $this->resetAfterTest();
        // Create course, assignment, submission, and then a feedback comment.
        $course = $this->getDataGenerator()->create_course();
        // Student.
        $user1 = $this->getDataGenerator()->create_user();
        // Teacher.
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'editingteacher');
        $fastassignment = $this->create_instance(['course' => $course]);

        $context = $fastassignment->get_context();

        $studentcomment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($fastassignment, $user1, $studentcomment);
        $teachercomment = 'From the teacher';
        $this->setUser($user2);
        $comment->add($teachercomment);

        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertFalse($writer->has_any_data());

        // The student should be able to see the teachers feedback.
        $exportdata = new \mod_fastassignment\privacy\fastassignment_plugin_request_data($context, $fastassignment, $submission);
        \fastassignsubmission_comments\privacy\provider::export_submission_user_data($exportdata);
        $exportedcomments = $writer->get_data(['Comments']);

        // Can't rely on these comments coming out in order.
        if ($exportedcomments->comments[0]->userid == $user1->id) {
            $exportedstudentcomment = $exportedcomments->comments[0]->content;
            $exportedteachercomment = $exportedcomments->comments[1]->content;
        } else {
            $exportedstudentcomment = $exportedcomments->comments[1]->content;
            $exportedteachercomment = $exportedcomments->comments[0]->content;
        }
        $this->assertCount(2, $exportedcomments->comments);
        $this->assertContains($studentcomment, $exportedstudentcomment);
        $this->assertContains($teachercomment, $exportedteachercomment);
    }

    /**
     * Test that all comments are deleted for this context.
     */
    public function test_delete_submission_for_context() {
        global $DB;
        $this->resetAfterTest();

        // Create course, assignment, submission, and then a feedback comment.
        $course = $this->getDataGenerator()->create_course();
        // Student.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // Teacher.
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'editingteacher');
        $fastassignment = $this->create_instance(['course' => $course]);

        $context = $fastassignment->get_context();

        $studentcomment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($fastassignment, $user1, $studentcomment);
        $studentcomment = 'Comment from user 2';
        list($plugin2, $submission2, $comment2) = $this->create_comment_submission($fastassignment, $user2, $studentcomment);
        $teachercomment1 = 'From the teacher';
        $teachercomment2 = 'From the teacher for second student.';
        $this->setUser($user3);
        $comment->add($teachercomment1);
        $comment2->add($teachercomment2);

        // Only need the context in this plugin for this operation.
        $requestdata = new \mod_fastassignment\privacy\fastassignment_plugin_request_data($context, $fastassignment);
        \fastassignsubmission_comments\privacy\provider::delete_submission_for_context($requestdata);

        $results = $DB->get_records('comments', ['contextid' => $context->id]);
        $this->assertEmpty($results);
    }

    /**
     * Test that the comments for a user are deleted.
     */
    public function test_delete_submission_for_userid() {
        global $DB;
        $this->resetAfterTest();
        // Create course, assignment, submission, and then a feedback comment.
        $course = $this->getDataGenerator()->create_course();
        // Student.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // Teacher.
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'editingteacher');
        $fastassignment = $this->create_instance(['course' => $course]);

        $context = $fastassignment->get_context();

        $studentcomment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($fastassignment, $user1, $studentcomment);
        $studentcomment = 'Comment from user 2';
        list($plugin2, $submission2, $comment2) = $this->create_comment_submission($fastassignment, $user2, $studentcomment);
        $teachercomment1 = 'From the teacher';
        $teachercomment2 = 'From the teacher for second student.';
        $this->setUser($user3);
        $comment->add($teachercomment1);
        $comment2->add($teachercomment2);

        // Provide full details to delete the comments.
        $requestdata = new \mod_fastassignment\privacy\fastassignment_plugin_request_data($context, $fastassignment, null, [], $user1);
        \fastassignsubmission_comments\privacy\provider::delete_submission_for_userid($requestdata);

        $results = $DB->get_records('comments', ['contextid' => $context->id]);
        // We are only deleting the comments for user1 (one comment) so we should have three left.
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            // Check that none of the comments are from user1.
            $this->assertNotEquals($user1->id, $result->userid);
        }
    }

    /**
     * Test deletion of all submissions for a context works.
     */
    public function test_delete_submissions() {
        global $DB;
        // Get a bunch of users making comments.
        // Some in one context some in another.
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        // Only in first context.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // First and second context.
        $user3 = $this->getDataGenerator()->create_user();
        // Second context only.
        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $assign1 = $this->create_instance(['course' => $course]);
        $assign2 = $this->create_instance(['course' => $course]);

        $assigncontext1 = $assign1->get_context();
        $assigncontext2 = $assign2->get_context();

        $user1comment = 'Comment from user 1';
        list($plugin, $submission, $comment) = $this->create_comment_submission($assign1, $user1, $user1comment);
        $user2comment = 'From user 2';
        $this->setUser($user2);
        $comment->add($user2comment);
        $user3comment = 'User 3 comment';
        $this->setUser($user3);
        $comment->add($user3comment);
        $user4comment = 'Comment from user 4';
        list($plugin, $submission, $comment) = $this->create_comment_submission($assign2, $user4, $user4comment);
        $user3secondcomment = 'Comment on user 4 post.';
        $this->setUser($user3);
        $comment->add($user3comment);

        // There should be three entries. One for the first three users.
        $results = $DB->get_records('comments', ['contextid' => $assigncontext1->id]);
        $this->assertCount(3, $results);

        $deletedata = new \mod_fastassignment\privacy\fastassignment_plugin_request_data($assigncontext1, $assign1);
        $deletedata->set_userids([$user1->id, $user3->id]);
        \fastassignsubmission_comments\privacy\provider::delete_submissions($deletedata);

        // We should be left with just a comment from user 2.
        $results = $DB->get_records('comments', ['contextid' => $assigncontext1->id]);
        $this->assertCount(1, $results);
        $this->assertEquals($user2comment, current($results)->content);
    }
}