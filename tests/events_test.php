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
 * Contains the event tests for the module fastassignment.
 *
 * @package   mod_fastassignment
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/fastassignment/tests/generator.php');
require_once($CFG->dirroot . '/mod/fastassignment/tests/fixtures/event_mod_fastassignment_fixtures.php');
require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

/**
 * Contains the event tests for the module fastassignment.
 *
 * @package   mod_fastassignment
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fastassignment_events_testcase extends advanced_testcase {
    // Use the generator helper.
    use mod_fastassignment_test_generator;

    /**
     * Basic tests for the submission_created() abstract class.
     */
    public function test_base_event() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        $data = array(
            'context' => $modcontext,
        );

        $event = \mod_fastassignment_unittests\event\nothing_happened::create($data);
        $fastassignment = $event->get_assign();
        $this->assertDebuggingCalled();
        $this->assertInstanceOf('fastassignment', $fastassignment);

        $event = \mod_fastassignment_unittests\event\nothing_happened::create($data);
        $event->set_assign($fastassignment);
        $assign2 = $event->get_assign();
        $this->assertDebuggingNotCalled();
        $this->assertSame($fastassignment, $assign2);
    }

    /**
     * Basic tests for the submission_created() abstract class.
     */
    public function test_submission_created() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        // Standard Event parameters.
        $params = array(
            'context' => $modcontext,
            'courseid' => $course->id
        );

        $eventinfo = $params;
        $eventinfo['other'] = array(
            'submissionid' => '17',
            'submissionattempt' => 0,
            'submissionstatus' => 'submitted'
        );

        $sink = $this->redirectEvents();
        $event = \mod_fastassignment_unittests\event\submission_created::create($eventinfo);
        $event->trigger();
        $result = $sink->get_events();
        $event = reset($result);
        $sink->close();

        $this->assertEquals($modcontext->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);

        // Check that an error occurs when teamsubmission is not set.
        try {
            \mod_fastassignment_unittests\event\submission_created::create($params);
            $this->fail('Other must contain the key submissionid.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        // Check that the submission status debugging is fired.
        $subinfo = $params;
        $subinfo['other'] = array('submissionid' => '23');
        try {
            \mod_fastassignment_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionattempt.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }

        $subinfo['other'] = array('submissionattempt' => '0');
        try {
            \mod_fastassignment_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionstatus.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }

    /**
     * Basic tests for the submission_updated() abstract class.
     */
    public function test_submission_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment');
        $instance = $generator->create_instance(array('course' => $course->id));
        $modcontext = context_module::instance($instance->cmid);

        // Standard Event parameters.
        $params = array(
            'context' => $modcontext,
            'courseid' => $course->id
        );

        $eventinfo = $params;
        $eventinfo['other'] = array(
            'submissionid' => '17',
            'submissionattempt' => 0,
            'submissionstatus' => 'submitted'
        );

        $sink = $this->redirectEvents();
        $event = \mod_fastassignment_unittests\event\submission_updated::create($eventinfo);
        $event->trigger();
        $result = $sink->get_events();
        $event = reset($result);
        $sink->close();

        $this->assertEquals($modcontext->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);

        // Check that an error occurs when teamsubmission is not set.
        try {
            \mod_fastassignment_unittests\event\submission_created::create($params);
            $this->fail('Other must contain the key submissionid.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        // Check that the submission status debugging is fired.
        $subinfo = $params;
        $subinfo['other'] = array('submissionid' => '23');
        try {
            \mod_fastassignment_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionattempt.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }

        $subinfo['other'] = array('submissionattempt' => '0');
        try {
            \mod_fastassignment_unittests\event\submission_created::create($subinfo);
            $this->fail('Other must contain the key submissionstatus.');
        } catch (Exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }

    public function test_extension_granted() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $now = time();
        $tomorrow = $now + DAYSECS;
        $yesterday = $now - DAYSECS;

        $fastassignment = $this->create_instance($course, [
            'duedate' => $yesterday,
            'cutoffdate' => $yesterday,
        ]);
        $sink = $this->redirectEvents();

        $fastassignment->testable_save_user_extension($student->id, $tomorrow);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\extension_granted', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);

        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'grant extension',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            $student->id,
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_locked() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);
        $sink = $this->redirectEvents();

        $fastassignment->lock_submission($student->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\submission_locked', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'lock submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('locksubmissionforstudent', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student))),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_identities_revealed() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course, ['blindmarking' => 1]);
        $sink = $this->redirectEvents();

        $fastassignment->reveal_identities();

        $events = $sink->get_events();
        $eventscount = 0;

        foreach ($events as $event) {
            if ($event instanceof \mod_fastassignment\event\identities_revealed) {
                $eventscount++;
                $this->assertInstanceOf('\mod_fastassignment\event\identities_revealed', $event);
                $this->assertEquals($fastassignment->get_context(), $event->get_context());
                $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
                $expected = array(
                    $fastassignment->get_course()->id,
                    'fastassignment',
                    'reveal identities',
                    'view.php?id=' . $fastassignment->get_course_module()->id,
                    get_string('revealidentities', 'fastassignment'),
                    $fastassignment->get_course_module()->id
                );
                $this->assertEventLegacyLogData($expected, $event);
            }
        }

        $this->assertEquals(1, $eventscount);
        $sink->close();
    }

    /**
     * Test the submission_status_viewed event.
     */
    public function test_submission_status_viewed() {
        global $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/a_url');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\submission_status_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewownsubmissionstatus', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_submission_status_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);
        $submission = $fastassignment->get_user_submission($student->id, true);
        $submission->status = FASTASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $fastassignment->testable_update_submission($submission, $student->id, true, false);

        $sink = $this->redirectEvents();
        $fastassignment->revert_to_draft($student->id);

        $events = $sink->get_events();
        $this->assertCount(2, $events);
        $event = $events[1];
        $this->assertInstanceOf('\mod_fastassignment\event\submission_status_updated', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($submission->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals(FASTASSIGN_SUBMISSION_STATUS_DRAFT, $event->other['newstatus']);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'revert submission to draft',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('reverttodraftforstudent', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student))),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_marker_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        $sink = $this->redirectEvents();
        $fastassignment->testable_process_set_batch_marking_allocation($student->id, $teacher->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\marker_updated', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals($teacher->id, $event->other['markerid']);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'set marking allocation',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('setmarkerallocationforlog', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student), 'marker' => fullname($teacher))),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_workflow_state_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        // Test process_set_batch_marking_workflow_state.
        $sink = $this->redirectEvents();
        $fastassignment->testable_process_set_batch_marking_workflow_state($student->id, FASTASSIGN_MARKING_WORKFLOW_STATE_INREVIEW);

        $events = $sink->get_events();
        $eventcount = 0;
        foreach ($events as $event) {
            if ($event instanceof \mod_fastassignment\event\submission_graded) {
                $eventcount++;
                $this->assertInstanceOf('\mod_fastassignment\event\submission_graded', $event);
                $this->assertEquals($fastassignment->get_context(), $event->get_context());
            }
            if ($event instanceof \mod_fastassignment\event\workflow_state_updated) {
                $eventcount++;
                $this->assertInstanceOf('\mod_fastassignment\event\workflow_state_updated', $event);
                $this->assertEquals($fastassignment->get_context(), $event->get_context());
                $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
                $this->assertEquals($student->id, $event->relateduserid);
                $this->assertEquals($teacher->id, $event->userid);
                $this->assertEquals(FASTASSIGN_MARKING_WORKFLOW_STATE_INREVIEW, $event->other['newstate']);
                $expected = array(
                    $fastassignment->get_course()->id,
                    'fastassignment',
                    'set marking workflow state',
                    'view.php?id=' . $fastassignment->get_course_module()->id,
                    get_string('setmarkingworkflowstateforlog', 'fastassignment', array('id' => $student->id,
                        'fullname' => fullname($student), 'state' => FASTASSIGN_MARKING_WORKFLOW_STATE_INREVIEW)),
                    $fastassignment->get_course_module()->id
                );
                $this->assertEventLegacyLogData($expected, $event);
            }
        }
        $this->assertEquals(2, $eventcount);
        $sink->close();

        // Test setting workflow state in apply_grade_to_user.
        $sink = $this->redirectEvents();
        $data = new stdClass();
        $data->grade = '50.0';
        $data->workflowstate = 'readyforrelease';
        $fastassignment->testable_apply_grade_to_user($data, $student->id, 0);

        $events = $sink->get_events();
        $this->assertCount(4, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\workflow_state_updated', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals(FASTASSIGN_MARKING_WORKFLOW_STATE_READYFORRELEASE, $event->other['newstate']);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'set marking workflow state',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('setmarkingworkflowstateforlog', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student), 'state' => FASTASSIGN_MARKING_WORKFLOW_STATE_READYFORRELEASE)),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test setting workflow state in process_save_quick_grades.
        $sink = $this->redirectEvents();

        $data = array(
            'grademodified_' . $student->id => time(),
            'gradeattempt_' . $student->id => '',
            'quickgrade_' . $student->id => '60.0',
            'quickgrade_' . $student->id . '_workflowstate' => 'inmarking'
        );
        $fastassignment->testable_process_save_quick_grades($data);

        $events = $sink->get_events();
        $this->assertCount(4, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\workflow_state_updated', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals(FASTASSIGN_MARKING_WORKFLOW_STATE_INMARKING, $event->other['newstate']);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'set marking workflow state',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('setmarkingworkflowstateforlog', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student), 'state' => FASTASSIGN_MARKING_WORKFLOW_STATE_INMARKING)),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_duplicated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $fastassignment = $this->create_instance($course);
        $submission1 = $fastassignment->get_user_submission($student->id, true, 0);
        $submission2 = $fastassignment->get_user_submission($student->id, true, 1);
        $submission2->status = FASTASSIGN_SUBMISSION_STATUS_REOPENED;
        $fastassignment->testable_update_submission($submission2, $student->id, time(), $fastassignment->get_instance()->teamsubmission);

        $sink = $this->redirectEvents();
        $notices = null;
        $fastassignment->copy_previous_attempt($notices);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\submission_duplicated', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($submission2->id, $event->objectid);
        $this->assertEquals($student->id, $event->userid);
        $submission2->status = FASTASSIGN_SUBMISSION_STATUS_DRAFT;
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'submissioncopied',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            $fastassignment->testable_format_submission_for_log($submission2),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_unlocked() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);
        $sink = $this->redirectEvents();

        $fastassignment->unlock_submission($student->id);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_fastassignment\event\submission_unlocked', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($fastassignment->get_instance()->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'unlock submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('unlocksubmissionforstudent', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student))),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    public function test_submission_graded() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher->ignoresesskey = true;
        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        // Test apply_grade_to_user.
        $sink = $this->redirectEvents();

        $data = new stdClass();
        $data->grade = '50.0';
        $fastassignment->testable_apply_grade_to_user($data, $student->id, 0);
        $grade = $fastassignment->get_user_grade($student->id, false, 0);

        $events = $sink->get_events();
        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_fastassignment\event\submission_graded', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'grade submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            $fastassignment->format_grade_for_log($grade),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test process_save_quick_grades.
        $sink = $this->redirectEvents();

        $grade = $fastassignment->get_user_grade($student->id, false);
        $data = array(
            'grademodified_' . $student->id => time(),
            'gradeattempt_' . $student->id => $grade->attemptnumber,
            'quickgrade_' . $student->id => '60.0'
        );
        $fastassignment->testable_process_save_quick_grades($data);
        $grade = $fastassignment->get_user_grade($student->id, false);
        $this->assertEquals(60.0, $grade->grade);

        $events = $sink->get_events();
        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_fastassignment\event\submission_graded', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'grade submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            $fastassignment->format_grade_for_log($grade),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();

        // Test update_grade.
        $sink = $this->redirectEvents();
        $data = clone($grade);
        $data->grade = '50.0';
        $fastassignment->update_grade($data);
        $grade = $fastassignment->get_user_grade($student->id, false, 0);
        $this->assertEquals(50.0, $grade->grade);
        $events = $sink->get_events();

        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\mod_fastassignment\event\submission_graded', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($grade->id, $event->objectid);
        $this->assertEquals($student->id, $event->relateduserid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'grade submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            $fastassignment->format_grade_for_log($grade),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $sink->close();
    }

    /**
     * Test the submission_viewed event.
     */
    public function test_submission_viewed() {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);
        $submission = $fastassignment->get_user_submission($student->id, true);

        // We need to set the URL in order to view the submission.
        $PAGE->set_url('/a_url');
        // A hack - these variables are used by the view_plugin_content function to
        // determine what we actually want to view - would usually be set in URL.
        global $_POST;
        $_POST['plugin'] = 'comments';
        $_POST['sid'] = $submission->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('viewpluginfastassignsubmission');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\submission_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($submission->id, $event->objectid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view submission',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewsubmissionforuser', 'fastassignment', $student->id),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the feedback_viewed event.
     */
    public function test_feedback_viewed() {
        global $DB, $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);
        $submission = $fastassignment->get_user_submission($student->id, true);

        // Insert a grade for this submission.
        $grade = new stdClass();
        $grade->assignment = $fastassignment->get_instance()->id;
        $grade->userid = $student->id;
        $gradeid = $DB->insert_record('fastassignment_grades', $grade);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/a_url');
        // A hack - these variables are used by the view_plugin_content function to
        // determine what we actually want to view - would usually be set in URL.
        global $_POST;
        $_POST['plugin'] = 'comments';
        $_POST['gid'] = $gradeid;
        $_POST['sid'] = $submission->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('viewpluginfastassignfeedback');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\feedback_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEquals($gradeid, $event->objectid);
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view feedback',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewfeedbackforuser', 'fastassignment', $student->id),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the grading_form_viewed event.
     */
    public function test_grading_form_viewed() {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/a_url');
        // A hack - this variable is used by the view_single_grade_page function.
        global $_POST;
        $_POST['rownum'] = 1;
        $_POST['userid'] = $student->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('grade');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\grading_form_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view grading form',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewgradingformforstudent', 'fastassignment', array('id' => $student->id,
                'fullname' => fullname($student))),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the grading_table_viewed event.
     */
    public function test_grading_table_viewed() {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($teacher);

        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the feedback.
        $PAGE->set_url('/a_url');
        // A hack - this variable is used by the view_single_grade_page function.
        global $_POST;
        $_POST['rownum'] = 1;
        $_POST['userid'] = $student->id;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('grading');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\grading_table_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view submission grading table',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewsubmissiongradingtable', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the submission_form_viewed event.
     */
    public function test_submission_form_viewed() {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/a_url');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('editsubmission');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\submission_form_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view submit assignment form',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('editsubmission', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the submission_form_viewed event.
     */
    public function test_submission_confirmation_form_viewed() {
        global $PAGE;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/a_url');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('submit');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\submission_confirmation_form_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view confirm submit assignment form',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewownsubmissionform', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the reveal_identities_confirmation_page_viewed event.
     */
    public function test_reveal_identities_confirmation_page_viewed() {
        global $PAGE;
        $this->resetAfterTest();

        // Set to the admin user so we have the permission to reveal identities.
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->create_instance($course);

        // We need to set the URL in order to view the submission form.
        $PAGE->set_url('/a_url');

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->view('revealidentities');
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\reveal_identities_confirmation_page_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewrevealidentitiesconfirm', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the statement_accepted event.
     */
    public function test_statement_accepted() {
        // We want to be a student so we can submit assignments.
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);

        // We do not want to send any messages to the student during the PHPUNIT test.
        set_config('submissionreceipts', false, 'fastassignment');

        $fastassignment = $this->create_instance($course);

        // Create the data we want to pass to the submit_for_grading function.
        $data = new stdClass();
        $data->submissionstatement = 'We are the Borg. You will be assimilated. Resistance is futile. - do you agree
            to these terms?';

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->submit_for_grading($data, array());
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\statement_accepted', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'submission statement accepted',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('submissionstatementacceptedlog',
                'mod_fastassignment',
                fullname($student)),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        // Enable the online text submission plugin.
        $submissionplugins = $fastassignment->get_submission_plugins();
        foreach ($submissionplugins as $plugin) {
            if ($plugin->get_type() === 'onlinetext') {
                $plugin->enable();
                break;
            }
        }

        // Create the data we want to pass to the save_submission function.
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => 'Online text',
            'format' => FORMAT_HTML,
            'itemid' => file_get_unused_draft_itemid()
        );
        $data->submissionstatement = 'We are the Borg. You will be assimilated. Resistance is futile. - do you agree
            to these terms?';

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->save_submission($data, $notices);
        $events = $sink->get_events();
        $event = $events[2];

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\statement_accepted', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the batch_set_workflow_state_viewed event.
     */
    public function test_batch_set_workflow_state_viewed() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->testable_view_batch_set_workflow_state($student->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\batch_set_workflow_state_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view batch set marking workflow state',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewbatchsetmarkingworkflowstate', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the batch_set_marker_allocation_viewed event.
     */
    public function test_batch_set_marker_allocation_viewed() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $fastassignment = $this->create_instance($course);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->testable_view_batch_markingallocation($student->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\batch_set_marker_allocation_viewed', $event);
        $this->assertEquals($fastassignment->get_context(), $event->get_context());
        $expected = array(
            $fastassignment->get_course()->id,
            'fastassignment',
            'view batch set marker allocation',
            'view.php?id=' . $fastassignment->get_course_module()->id,
            get_string('viewbatchmarkingallocation', 'fastassignment'),
            $fastassignment->get_course_module()->id
        );
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override created event.
     *
     * There is no external API for creating a user override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_user_override_created() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'relateduserid' => 2,
            'context' => context_module::instance($fastassignment->cmid),
            'other' => array(
                'assignid' => $fastassignment->id
            )
        );
        $event = \mod_fastassignment\event\user_override_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\user_override_created', $event);
        $this->assertEquals(context_module::instance($fastassignment->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override created event.
     *
     * There is no external API for creating a group override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_group_override_created() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'context' => context_module::instance($fastassignment->cmid),
            'other' => array(
                'assignid' => $fastassignment->id,
                'groupid' => 2
            )
        );
        $event = \mod_fastassignment\event\group_override_created::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\group_override_created', $event);
        $this->assertEquals(context_module::instance($fastassignment->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override updated event.
     *
     * There is no external API for updating a user override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_user_override_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'relateduserid' => 2,
            'context' => context_module::instance($fastassignment->cmid),
            'other' => array(
                'assignid' => $fastassignment->id
            )
        );
        $event = \mod_fastassignment\event\user_override_updated::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\user_override_updated', $event);
        $this->assertEquals(context_module::instance($fastassignment->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override updated event.
     *
     * There is no external API for updating a group override, so the unit test will simply
     * create and trigger the event and ensure the event data is returned as expected.
     */
    public function test_group_override_updated() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment')->create_instance(['course' => $course->id]);

        $params = array(
            'objectid' => 1,
            'context' => context_module::instance($fastassignment->cmid),
            'other' => array(
                'assignid' => $fastassignment->id,
                'groupid' => 2
            )
        );
        $event = \mod_fastassignment\event\group_override_updated::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\group_override_updated', $event);
        $this->assertEquals(context_module::instance($fastassignment->cmid), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the user override deleted event.
     */
    public function test_user_override_deleted() {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assigninstance = $this->getDataGenerator()->create_module('fastassignment', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('fastassignment', $assigninstance->id, $course->id);
        $context = context_module::instance($cm->id);
        $fastassignment = new fastassignment($context, $cm, $course);

        // Create an override.
        $override = new stdClass();
        $override->fastassignment = $assigninstance->id;
        $override->userid = 2;
        $override->id = $DB->insert_record('fastassignment_overrides', $override);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->delete_override($override->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\user_override_deleted', $event);
        $this->assertEquals(context_module::instance($cm->id), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the group override deleted event.
     */
    public function test_group_override_deleted() {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assigninstance = $this->getDataGenerator()->create_module('fastassignment', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('fastassignment', $assigninstance->id, $course->id);
        $context = context_module::instance($cm->id);
        $fastassignment = new fastassignment($context, $cm, $course);

        // Create an override.
        $override = new stdClass();
        $override->fastassignment = $assigninstance->id;
        $override->groupid = 2;
        $override->id = $DB->insert_record('fastassignment_overrides', $override);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $fastassignment->delete_override($override->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_fastassignment\event\group_override_deleted', $event);
        $this->assertEquals(context_module::instance($cm->id), $event->get_context());
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the course module viewed event.
     */
    public function test_course_module_viewed() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fastassignment = $this->create_instance($course);

        $context = $fastassignment->get_context();

        $params = array(
            'context' => $context,
            'objectid' => $fastassignment->get_instance()->id
        );

        $event = \mod_fastassignment\event\course_module_viewed::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_fastassignment\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
    }

    /**
     * Test that all events generated with blindmarking enabled are anonymous
     */
    public function test_anonymous_events() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_fastassignment');
        $instance = $generator->create_instance(array('course' => $course->id, 'blindmarking' => 1));

        $cm = get_coursemodule_from_instance('fastassignment', $instance->id, $course->id);
        $context = context_module::instance($cm->id);
        $fastassignment = new fastassignment($context, $cm, $course);

        $this->setUser($teacher);
        $sink = $this->redirectEvents();

        $fastassignment->lock_submission($student1->id);

        $events = $sink->get_events();
        $event = reset($events);

        $this->assertTrue((bool)$event->anonymous);

        $fastassignment->reveal_identities();
        $sink = $this->redirectEvents();
        $fastassignment->lock_submission($student2->id);

        $events = $sink->get_events();
        $event = reset($events);

        $this->assertFalse((bool)$event->anonymous);
    }

}
