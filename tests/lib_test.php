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
 * Unit tests for (some of) mod/fastassignment/lib.php.
 *
 * @package    mod_fastassignment
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/fastassignment/lib.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
require_once($CFG->dirroot . '/mod/fastassignment/tests/generator.php');

use \core_calendar\local\api as calendar_local_api;
use \core_calendar\local\event\container as calendar_event_container;

/**
 * Unit tests for (some of) mod/fastassignment/lib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_fastassignment_lib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_fastassignment_test_generator;

    /**
     * Test that fastassignment_print_recent_activity shows ungraded submitted assignments.
     */
    public function test_print_recent_activity() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);
        $this->submit_for_grading($student, $fastassignment);

        $this->setUser($teacher);
        $this->expectOutputRegex('/submitted:/');
        fastassignment_print_recent_activity($course, true, time() - 3600);
    }

    /**
     * Test that fastassignment_print_recent_activity does not display any warnings when a custom fullname has been configured.
     */
    public function test_print_recent_activity_fullname() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);
        $this->submit_for_grading($student, $fastassignment);

        $this->setUser($teacher);
        $this->expectOutputRegex('/submitted:/');
        set_config('fullnamedisplay', 'firstname, lastnamephonetic');
        fastassignment_print_recent_activity($course, false, time() - 3600);
    }

    /**
     * Test that fastassignment_print_recent_activity shows the blind marking ID.
     */
    public function test_print_recent_activity_fullname_blind_marking() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $fastassignment = $this->create_instance($course, [
                'blindmarking' => 1,
            ]);
        $this->add_submission($student, $fastassignment);
        $this->submit_for_grading($student, $fastassignment);

        $this->setUser($teacher);
        $uniqueid = $fastassignment->get_uniqueid_for_user($student->id);
        $expectedstr = preg_quote(get_string('participant', 'mod_fastassignment'), '/') . '.*' . $uniqueid;
        $this->expectOutputRegex("/{$expectedstr}/");
        fastassignment_print_recent_activity($course, false, time() - 3600);
    }

    /**
     * Test that fastassignment_get_recent_mod_activity fetches the assignment correctly.
     */
    public function test_fastassignment_get_recent_mod_activity() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);
        $this->add_submission($student, $fastassignment);
        $this->submit_for_grading($student, $fastassignment);

        $index = 1;
        $activities = [
            $index => (object) [
                'type' => 'fastassignment',
                'cmid' => $fastassignment->get_course_module()->id,
            ],
        ];

        $this->setUser($teacher);
        fastassignment_get_recent_mod_activity($activities, $index, time() - HOURSECS, $course->id, $fastassignment->get_course_module()->id);

        $activity = $activities[1];
        $this->assertEquals("fastassignment", $activity->type);
        $this->assertEquals($student->id, $activity->user->id);
    }

    /**
     * Ensure that fastassignment_user_complete displays information about drafts.
     */
    public function test_fastassignment_user_complete() {
        global $PAGE, $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['submissiondrafts' => 1]);
        $this->add_submission($student, $fastassignment);

        $PAGE->set_url(new moodle_url('/mod/fastassignment/view.php', array('id' => $fastassignment->get_course_module()->id)));

        $submission = $fastassignment->get_user_submission($student->id, true);
        $submission->status = FASTASSIGN_SUBMISSION_STATUS_DRAFT;
        $DB->update_record('fastassignment_submission', $submission);

        $this->expectOutputRegex('/Draft/');
        fastassignment_user_complete($course, $student, $fastassignment->get_course_module(), $fastassignment->get_instance());
    }

    /**
     * Ensure that fastassignment_user_outline fetches updated grades.
     */
    public function test_fastassignment_user_outline() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        $this->add_submission($student, $fastassignment);
        $this->submit_for_grading($student, $fastassignment);
        $this->mark_submission($teacher, $fastassignment, $student, 50.0);

        $this->setUser($teacher);
        $data = $fastassignment->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $fastassignment->update_grade($data);

        $result = fastassignment_user_outline($course, $student, $fastassignment->get_course_module(), $fastassignment->get_instance());

        $this->assertRegExp('/50.5/', $result->info);
    }

    /**
     * Ensure that fastassignment_get_completion_state reflects the correct status at each point.
     */
    public function test_fastassignment_get_completion_state() {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, [
                'submissiondrafts' => 0,
                'completionsubmit' => 1
            ]);

        $this->setUser($student);
        $result = fastassignment_get_completion_state($course, $fastassignment->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->add_submission($student, $fastassignment);
        $result = fastassignment_get_completion_state($course, $fastassignment->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->submit_for_grading($student, $fastassignment);
        $result = fastassignment_get_completion_state($course, $fastassignment->get_course_module(), $student->id, false);
        $this->assertTrue($result);

        $this->mark_submission($teacher, $fastassignment, $student, 50.0);
        $result = fastassignment_get_completion_state($course, $fastassignment->get_course_module(), $student->id, false);
        $this->assertTrue($result);
    }

    /**
     * Tests for mod_fastassignment_refresh_events.
     */
    public function test_fastassignment_refresh_events() {
        global $DB;

        $this->resetAfterTest();

        $duedate = time();
        $newduedate = $duedate + DAYSECS;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, [
                'duedate' => $duedate,
            ]);

        $instance = $fastassignment->get_instance();
        $eventparams = [
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE,
            'groupid' => 0
        ];

        // Make sure the calendar event for assignment 1 matches the initial due date.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $duedate);

        // Manually update assignment 1's due date.
        $DB->update_record('fastassignment', (object) [
            'id' => $instance->id,
            'duedate' => $newduedate,
            'course' => $course->id
        ]);

        // Then refresh the assignment events of assignment 1's course.
        $this->assertTrue(fastassignment_refresh_events($course->id));

        // Confirm that the assignment 1's due date event now has the new due date after refresh.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Create a second course and assignment.
        $othercourse = $this->getDataGenerator()->create_course();;
        $otherassign = $this->create_instance($othercourse, [
            'duedate' => $duedate,
        ]);
        $otherinstance = $otherassign->get_instance();

        // Manually update assignment 1 and 2's due dates.
        $newduedate += DAYSECS;
        $DB->update_record('fastassignment', (object)[
            'id' => $instance->id,
            'duedate' => $newduedate,
            'course' => $course->id
        ]);
        $DB->update_record('fastassignment', (object)[
            'id' => $otherinstance->id,
            'duedate' => $newduedate,
            'course' => $othercourse->id
        ]);

        // Refresh events of all courses and check the calendar events matches the new date.
        $this->assertTrue(fastassignment_refresh_events());

        // Check the due date calendar event for assignment 1.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Check the due date calendar event for assignment 2.
        $eventparams['instance'] = $otherinstance->id;
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // In case the course ID is passed as a numeric string.
        $this->assertTrue(fastassignment_refresh_events('' . $course->id));

        // Non-existing course ID.
        $this->assertFalse(fastassignment_refresh_events(-1));

        // Invalid course ID.
        $this->assertFalse(fastassignment_refresh_events('aaa'));
    }

    public function test_fastassignment_core_calendar_is_event_visible_duedate_event_as_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // The teacher should see the due date event.
        $this->setUser($teacher);
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event));
    }

    public function test_fastassignment_core_calendar_is_event_visible_duedate_event_for_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Now, log out.
        $this->setUser();

        // The teacher should see the due date event.
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event, $teacher->id));
    }

    public function test_fastassignment_core_calendar_is_event_visible_duedate_event_as_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // The student should care about the due date event.
        $this->setUser($student);
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event));
    }

    public function test_fastassignment_core_calendar_is_event_visible_duedate_event_for_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Now, log out.
        $this->setUser();

        // The student should care about the due date event.
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event, $student->id));
    }

    public function test_fastassignment_core_calendar_is_event_visible_gradingduedate_event_as_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // The teacher should see the due date event.
        $this->setUser($teacher);
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event));
    }


    public function test_fastassignment_core_calendar_is_event_visible_gradingduedate_event_for_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // Now, log out.
        $this->setUser();

        // The teacher should see the due date event.
        $this->assertTrue(mod_fastassignment_core_calendar_is_event_visible($event, $teacher->id));
    }

    public function test_fastassignment_core_calendar_is_event_visible_gradingduedate_event_as_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // The student should not see the due date event.
        $this->setUser($student);
        $this->assertFalse(mod_fastassignment_core_calendar_is_event_visible($event));
    }


    public function test_fastassignment_core_calendar_is_event_visible_gradingduedate_event_for_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // Now, log out.
        $this->setUser();

        // The student should not see the due date event.
        $this->assertFalse(mod_fastassignment_core_calendar_is_event_visible($event, $student->id));
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_as_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // The teacher should see the event.
        $this->setUser($teacher);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);

        // The teacher should not have an action for a due date event.
        $this->assertNull($actionevent);
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_for_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Now, log out.
        $this->setUser();

        // Decorate action event for a teacher.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $teacher->id);

        // The teacher should not have an action for a due date event.
        $this->assertNull($actionevent);
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_as_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // The student should see the event.
        $this->setUser($student);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('addsubmission', 'fastassignment'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_for_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Now, log out.
        $this->setUser();

        // Decorate action event for a student.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('addsubmission', 'fastassignment'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_gradingduedate_as_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        $this->setUser($teacher);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('grade'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(0, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_gradingduedate_for_teacher() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // Now, log out.
        $this->setUser();

        // Decorate action event for a teacher.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $teacher->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('grade'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(0, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_gradingduedate_as_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        $this->setUser($student);
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('grade'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(0, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_gradingduedate_for_student() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Create a calendar event.
        $this->setAdminUser();
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_GRADINGDUE);

        // Now, log out.
        $this->setUser();

        // Decorate action event for a student.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('grade'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(0, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_as_student_submitted() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Submit as the student.
        $this->add_submission($student, $fastassignment);
        $this->submit_for_grading($student, $fastassignment);

        // Confirm there was no event to action.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);
        $this->assertNull($actionevent);
    }

    public function test_fastassignment_core_calendar_provide_event_action_duedate_for_student_submitted() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course, ['fastassignsubmission_onlinetext_enabled' => 1]);

        $this->setAdminUser();

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment, FASTASSIGN_EVENT_TYPE_DUE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Submit as the student.
        $this->add_submission($student, $fastassignment);
        $this->submit_for_grading($student, $fastassignment);

        // Now, log out.
        $this->setUser();

        // Confirm there was no event to action.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $student->id);
        $this->assertNull($actionevent);
    }

    public function test_fastassignment_core_calendar_provide_event_action_already_completed() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $fastassignment = $this->create_instance($course,
         ['completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS]);

        // Get some additional data.
        $cm = get_coursemodule_from_instance('fastassignment', $fastassignment->get_instance()->id);

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_fastassignment_core_calendar_provide_event_action_already_completed_for_user() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $fastassignment = $this->create_instance($course,
         ['completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS]);

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Get some additional data.
        $cm = get_coursemodule_from_instance('fastassignment', $fastassignment->get_instance()->id);

        // Create a calendar event.
        $event = $this->create_action_event($course, $fastassignment,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed for the student.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_fastassignment_core_calendar_provide_event_action($event, $factory, $student->id);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param \stdClass $course The course the assignment is in
     * @param fastassignment $fastassignment The assignment to create an event for
     * @param string $eventtype The event type. eg. FASTASSIGN_EVENT_TYPE_DUE.
     * @return bool|calendar_event
     */
    private function create_action_event($course, $fastassignment, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'fastassignment';
        $event->courseid = $course->id;
        $event->instance = $fastassignment->get_instance()->id;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_fastassignment_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $cm1 = $this->create_instance($course, ['completion' => '2', 'completionsubmit' => '1'])->get_course_module();
        $cm2 = $this->create_instance($course, ['completion' => '2', 'completionsubmit' => '0'])->get_course_module();

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = (object) [
            'customdata' => [
                'customcompletionrules' => [
                    'completionsubmit' => '1',
                ],
            ],
            'completion' => 2,
        ];

        $activeruledescriptions = [get_string('completionsubmit', 'fastassignment')];
        $this->assertEquals(mod_fastassignment_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_fastassignment_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_fastassignment_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_fastassignment_get_completion_active_rule_descriptions(new stdClass()), []);
    }

    /**
     * Test that if some grades are not set, they are left alone and not rescaled
     */
    public function test_fastassignment_rescale_activity_grades_some_unset() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $otherstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // As a teacher.
        $this->setUser($teacher);
        $fastassignment = $this->create_instance($course);

        // Grade the student.
        $data = ['grade' => 50];
        $fastassignment->testable_apply_grade_to_user((object)$data, $student->id, 0);

        // Try getting another students grade. This will give a grade of FASTASSIGN_GRADE_NOT_SET (-1).
        $fastassignment->get_user_grade($otherstudent->id, true);

        // Rescale.
        fastassignment_rescale_activity_grades($course, $fastassignment->get_course_module(), 0, 100, 0, 10);

        // Get the grades for both students.
        $studentgrade = $fastassignment->get_user_grade($student->id, true);
        $otherstudentgrade = $fastassignment->get_user_grade($otherstudent->id, true);

        // Make sure the real grade is scaled, but the FASTASSIGN_GRADE_NOT_SET stays the same.
        $this->assertEquals($studentgrade->grade, 5);
        $this->assertEquals($otherstudentgrade->grade, FASTASSIGN_GRADE_NOT_SET);
    }

    /**
     * Return false when there are not overrides for this fastassignment instance.
     */
    public function test_fastassignment_is_override_calendar_event_no_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);

        $instance = $fastassignment->get_instance();
        $event = new \calendar_event((object)[
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'userid' => $student->id,
        ]);

        $this->assertFalse($fastassignment->is_override_calendar_event($event));
    }

    /**
     * Return false if the given event isn't an fastassignment module event.
     */
    public function test_fastassignment_is_override_calendar_event_no_nodule_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $userid = $student->id;
        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);

        $instance = $fastassignment->get_instance();
        $event = new \calendar_event((object)[
            'userid' => $userid
        ]);

        $this->assertFalse($fastassignment->is_override_calendar_event($event));
    }

    /**
     * Return false if there is overrides for this use but they belong to another fastassignment
     * instance.
     */
    public function test_fastassignment_is_override_calendar_event_different_fastassignment_instance() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);
        $instance = $fastassignment->get_instance();

        $otherassign = $this->create_instance($course, ['duedate' => $duedate]);
        $otherinstance = $otherassign->get_instance();

        $event = new \calendar_event((object) [
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'userid' => $student->id,
        ]);

        $DB->insert_record('fastassignment_overrides', (object) [
                'assignid' => $otherinstance->id,
                'userid' => $student->id,
            ]);

        $this->assertFalse($fastassignment->is_override_calendar_event($event));
    }

    /**
     * Return true if there is a user override for this event and fastassignment instance.
     */
    public function test_fastassignment_is_override_calendar_event_user_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);

        $instance = $fastassignment->get_instance();
        $event = new \calendar_event((object) [
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'userid' => $student->id,
        ]);


        $DB->insert_record('fastassignment_overrides', (object) [
                'assignid' => $instance->id,
                'userid' => $student->id,
            ]);

        $this->assertTrue($fastassignment->is_override_calendar_event($event));
    }

    /**
     * Return true if there is a group override for the event and fastassignment instance.
     */
    public function test_fastassignment_is_override_calendar_event_group_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);
        $instance = $fastassignment->get_instance();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $instance->course));

        $event = new \calendar_event((object) [
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'groupid' => $group->id,
        ]);

        $DB->insert_record('fastassignment_overrides', (object) [
                'assignid' => $instance->id,
                'groupid' => $group->id,
            ]);

        $this->assertTrue($fastassignment->is_override_calendar_event($event));
    }

    /**
     * Unknown event types should not have any limit restrictions returned.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_unkown_event_type() {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => 'SOME RANDOM EVENT'
        ]);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * Override events should not have any limit restrictions returned.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_override_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'userid' => $student->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE
        ]);

        $record = (object) [
            'assignid' => $instance->id,
            'userid' => $student->id,
        ];

        $DB->insert_record('fastassignment_overrides', $record);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertFalse($min);
        $this->assertFalse($max);
    }

    /**
     * Assignments configured without a submissions from and cutoff date should not have
     * any limits applied.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_due_no_limit() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, [
            'duedate' => $duedate,
            'allowsubmissionsfromdate' => 0,
            'cutoffdate' => 0,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE
        ]);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * Assignments should be bottom and top bound by the submissions from date and cutoff date
     * respectively.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_due_with_limits() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $duedate = time();
        $submissionsfromdate = $duedate - DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $fastassignment = $this->create_instance($course, [
            'duedate' => $duedate,
            'allowsubmissionsfromdate' => $submissionsfromdate,
            'cutoffdate' => $cutoffdate,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE
        ]);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertEquals($submissionsfromdate, $min[0]);
        $this->assertNotEmpty($min[1]);
        $this->assertEquals($cutoffdate, $max[0]);
        $this->assertNotEmpty($max[1]);
    }

    /**
     * Assignment grading due date should not have any limits of no due date and cutoff date is set.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_gradingdue_no_limit() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $fastassignment = $this->create_instance($course, [
            'duedate' => 0,
            'allowsubmissionsfromdate' => 0,
            'cutoffdate' => 0,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_GRADINGDUE
        ]);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * Assignment grading due event is minimum bound by the due date, if it is set.
     */
    public function test_mod_fastassignment_core_calendar_get_valid_event_timestart_range_gradingdue_with_due_date() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $duedate = time();
        $fastassignment = $this->create_instance($course, ['duedate' => $duedate]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_GRADINGDUE
        ]);

        list($min, $max) = mod_fastassignment_core_calendar_get_valid_event_timestart_range($event, $instance);
        $this->assertEquals($duedate, $min[0]);
        $this->assertNotEmpty($min[1]);
        $this->assertNull($max);
    }

    /**
     * Non due date events should not update the assignment due date.
     */
    public function test_mod_fastassignment_core_calendar_event_timestart_updated_non_due_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $submissionsfromdate = $duedate - DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $fastassignment = $this->create_instance($course, [
            'duedate' => $duedate,
            'allowsubmissionsfromdate' => $submissionsfromdate,
            'cutoffdate' => $cutoffdate,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_GRADINGDUE,
            'timestart' => $duedate + 1
        ]);

        mod_fastassignment_core_calendar_event_timestart_updated($event, $instance);

        $newinstance = $DB->get_record('fastassignment', ['id' => $instance->id]);
        $this->assertEquals($duedate, $newinstance->duedate);
    }

    /**
     * Due date override events should not change the assignment due date.
     */
    public function test_mod_fastassignment_core_calendar_event_timestart_updated_due_event_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $submissionsfromdate = $duedate - DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $fastassignment = $this->create_instance($course, [
            'duedate' => $duedate,
            'allowsubmissionsfromdate' => $submissionsfromdate,
            'cutoffdate' => $cutoffdate,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'userid' => $student->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE,
            'timestart' => $duedate + 1
        ]);

        $record = (object) [
            'assignid' => $instance->id,
            'userid' => $student->id,
            'duedate' => $duedate + 1,
        ];

        $DB->insert_record('fastassignment_overrides', $record);

        mod_fastassignment_core_calendar_event_timestart_updated($event, $instance);

        $newinstance = $DB->get_record('fastassignment', ['id' => $instance->id]);
        $this->assertEquals($duedate, $newinstance->duedate);
    }

    /**
     * Due date events should update the assignment due date.
     */
    public function test_mod_fastassignment_core_calendar_event_timestart_updated_due_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        $duedate = time();
        $newduedate = $duedate + 1;
        $submissionsfromdate = $duedate - DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $fastassignment = $this->create_instance($course, [
            'duedate' => $duedate,
            'allowsubmissionsfromdate' => $submissionsfromdate,
            'cutoffdate' => $cutoffdate,
        ]);
        $instance = $fastassignment->get_instance();

        $event = new \calendar_event((object) [
            'courseid' => $instance->course,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE,
            'timestart' => $newduedate
        ]);

        mod_fastassignment_core_calendar_event_timestart_updated($event, $instance);

        $newinstance = $DB->get_record('fastassignment', ['id' => $instance->id]);
        $this->assertEquals($newduedate, $newinstance->duedate);
    }

    /**
     * If a student somehow finds a way to update the due date calendar event
     * then the callback should not be executed to update the assignment due
     * date as well otherwise that would be a security issue.
     */
    public function test_student_role_cant_update_due_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $roleid = $this->getDataGenerator()->create_role();
        $role = $DB->get_record('role', ['id' => $roleid]);
        $user = $this->getDataGenerator()->create_and_enrol($course, $role->shortname);

        $this->setAdminUser();

        $mapper = calendar_event_container::get_event_mapper();
        $now = time();
        $duedate = (new DateTime())->setTimestamp($now);
        $newduedate = (new DateTime())->setTimestamp($now)->modify('+1 day');
        $fastassignment = $this->create_instance($course, [
            'course' => $course->id,
            'duedate' => $duedate->getTimestamp(),
        ]);
        $instance = $fastassignment->get_instance();

        $record = $DB->get_record('event', [
            'courseid' => $course->id,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE
        ]);

        $event = new \calendar_event($record);

        fastassignment_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        fastassignment_capability('moodle/course:manageactivities', CAP_PROHIBIT, $roleid, $context, true);

        $this->setUser($user);

        calendar_local_api::update_event_start_day(
            $mapper->from_legacy_event_to_event($event),
            $newduedate
        );

        $newinstance = $DB->get_record('fastassignment', ['id' => $instance->id]);
        $newevent = \calendar_event::load($event->id);
        // The due date shouldn't have changed even though we updated the calendar
        // event.
        $this->assertEquals($duedate->getTimestamp(), $newinstance->duedate);
        $this->assertEquals($newduedate->getTimestamp(), $newevent->timestart);
    }

    /**
     * A teacher with the capability to modify an assignment module should be
     * able to update the assignment due date by changing the due date calendar
     * event.
     */
    public function test_teacher_role_can_update_due_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        $this->setAdminUser();

        $mapper = calendar_event_container::get_event_mapper();
        $now = time();
        $duedate = (new DateTime())->setTimestamp($now);
        $newduedate = (new DateTime())->setTimestamp($now)->modify('+1 day');
        $fastassignment = $this->create_instance($course, [
            'course' => $course->id,
            'duedate' => $duedate->getTimestamp(),
        ]);
        $instance = $fastassignment->get_instance();

        $record = $DB->get_record('event', [
            'courseid' => $course->id,
            'modulename' => 'fastassignment',
            'instance' => $instance->id,
            'eventtype' => FASTASSIGN_EVENT_TYPE_DUE
        ]);

        $event = new \calendar_event($record);

        fastassignment_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        fastassignment_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context, true);

        $this->setUser($user);
        // Trigger and capture the event when adding a contact.
        $sink = $this->redirectEvents();

        calendar_local_api::update_event_start_day(
            $mapper->from_legacy_event_to_event($event),
            $newduedate
        );

        $triggeredevents = $sink->get_events();
        $moduleupdatedevents = array_filter($triggeredevents, function($e) {
            return is_a($e, 'core\event\course_module_updated');
        });

        $newinstance = $DB->get_record('fastassignment', ['id' => $instance->id]);
        $newevent = \calendar_event::load($event->id);
        // The due date shouldn't have changed even though we updated the calendar
        // event.
        $this->assertEquals($newduedate->getTimestamp(), $newinstance->duedate);
        $this->assertEquals($newduedate->getTimestamp(), $newevent->timestart);
        // Confirm that a module updated event is fired when the module
        // is changed.
        $this->assertNotEmpty($moduleupdatedevents);
    }

    /**
     * A user who does not have capabilities to add events to the calendar should be able to create an assignment.
     */
    public function test_creation_with_no_calendar_capabilities() {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $roleid = self::getDataGenerator()->create_role();
        self::getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        fastassignment_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $context, true);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_fastassignment');
        // Create an instance as a user without the calendar capabilities.
        $this->setUser($user);
        $time = time();
        $params = array(
            'course' => $course->id,
            'allowsubmissionsfromdate' => $time,
            'duedate' => $time + 500,
            'cutoffdate' => $time + 600,
            'gradingduedate' => $time + 700,
        );
        $generator->create_instance($params);
    }
}
