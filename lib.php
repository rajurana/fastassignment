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
 * This file contains the moodle hooks for the fastassignment module.
 *
 * It delegates most functions to the assignment class.
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Adds an assignment instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 * @param stdClass $data
 * @param mod_fastassignment_mod_form $form
 * @return int The instance id of the new assignment
 */
function fastassignment_add_instance(stdClass $data, mod_fastassignment_mod_form $form = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $assignment = new fastassignment(context_module::instance($data->coursemodule), null, null);
    return $assignment->add_instance($data, true);
}

/**
 * delete an assignment instance
 * @param int $id
 * @return bool
 */
function fastassignment_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
    $cm = get_coursemodule_from_instance('fastassignment', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $assignment = new fastassignment($context, null, null);
    return $assignment->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all assignment submissions and feedbacks in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array
 */
function fastassignment_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $status = array();
    $params = array('courseid'=>$data->courseid);
    $sql = "SELECT a.id FROM {fastassignment} a WHERE a.course=:courseid";
    $course = $DB->get_record('course', array('id'=>$data->courseid), '*', MUST_EXIST);
    if ($assigns = $DB->get_records_sql($sql, $params)) {
        foreach ($assigns as $fastassignment) {
            $cm = get_coursemodule_from_instance('fastassignment',
                                                 $fastassignment->id,
                                                 $data->courseid,
                                                 false,
                                                 MUST_EXIST);
            $context = context_module::instance($cm->id);
            $assignment = new fastassignment($context, $cm, $course);
            $status = array_merge($status, $assignment->reset_userdata($data));
        }
    }
    return $status;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every assignment event in the site is checked, else
 * only assignment events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @param int|stdClass $instance fastassignment module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function fastassignment_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('fastassignment', array('id' => $instance), '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                fastassignment_prepare_update_events($instance);
                return true;
            } else {
                $course = get_course($instance->course);
                fastassignment_prepare_update_events($instance, $course, $cm);
                return true;
            }
        }
    }

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$assigns = $DB->get_records('fastassignment', array('course' => $courseid))) {
            return false;
        }
        // Get course from courseid parameter.
        if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
            return false;
        }
    } else {
        if (!$assigns = $DB->get_records('fastassignment')) {
            return false;
        }
    }
    foreach ($assigns as $fastassignment) {
        fastassignment_prepare_update_events($fastassignment);
    }

    return true;
}

/**
 * This actually updates the normal and completion calendar events.
 *
 * @param  stdClass $fastassignment Assignment object (from DB).
 * @param  stdClass $course Course object.
 * @param  stdClass $cm Course module object.
 */
function fastassignment_prepare_update_events($fastassignment, $course = null, $cm = null) {
    global $DB;
    if (!isset($course)) {
        // Get course and course module for the assignment.
        list($course, $cm) = get_course_and_cm_from_instance($fastassignment->id, 'fastassignment', $fastassignment->course);
    }
    // Refresh the assignment's calendar events.
    $context = context_module::instance($cm->id);
    $assignment = new fastassignment($context, $cm, $course);
    $assignment->update_calendar($cm->id);
    // Refresh the calendar events also for the assignment overrides.
    $overrides = $DB->get_records('fastassignment_overrides', ['assignid' => $fastassignment->id], '',
                                  'id, groupid, userid, duedate, sortorder');
    foreach ($overrides as $override) {
        if (empty($override->userid)) {
            unset($override->userid);
        }
        if (empty($override->groupid)) {
            unset($override->groupid);
        }
        fastassignment_update_events($assignment, $override);
    }
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid The ID of the course to reset
 * @param string $type Optional type of assignment to limit the reset to a particular assignment type
 */
function fastassignment_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $params = array('moduletype'=>'fastassignment', 'courseid'=>$courseid);
    $sql = 'SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {fastassignment} a, {course_modules} cm, {modules} m
            WHERE m.name=:moduletype AND m.id=cm.module AND cm.instance=a.id AND a.course=:courseid';

    if ($assignments = $DB->get_records_sql($sql, $params)) {
        foreach ($assignments as $assignment) {
            fastassignment_grade_item_update($assignment, 'reset');
        }
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the assignment.
 * @param moodleform $mform form passed by reference
 */
function fastassignment_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'assignheader', get_string('modulenameplural', 'fastassignment'));
    $name = get_string('deleteallsubmissions', 'fastassignment');
    $mform->addElement('advcheckbox', 'reset_fastassignment_submissions', $name);
    $mform->addElement('advcheckbox', 'reset_fastassignment_user_overrides',
        get_string('removealluseroverrides', 'fastassignment'));
    $mform->addElement('advcheckbox', 'reset_fastassignment_group_overrides',
        get_string('removeallgroupoverrides', 'fastassignment'));
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function fastassignment_reset_course_form_defaults($course) {
    return array('reset_fastassignment_submissions' => 1,
            'reset_fastassignment_group_overrides' => 1,
            'reset_fastassignment_user_overrides' => 1);
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return object
 */
function fastassignment_update_instance(stdClass $data, $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
    $context = context_module::instance($data->coursemodule);
    $assignment = new fastassignment($context, null, null);
    return $assignment->update_instance($data);
}

/**
 * This function updates the events associated to the fastassignment.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @param fastassignment $fastassignment the fastassignment object.
 * @param object $override (optional) limit to a specific override
 */
function fastassignment_update_events($fastassignment, $override = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/calendar/lib.php');

    $assigninstance = $fastassignment->get_instance();

    // Load the old events relating to this fastassignment.
    $conds = array('modulename' => 'fastassignment', 'instance' => $assigninstance->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else if (isset($override->groupid)) {
            $conds['groupid'] = $override->groupid;
        } else {
            // This is not a valid override, it may have been left from a bad import or restore.
            $conds['groupid'] = $conds['userid'] = 0;
        }
    }
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    // Now make a to-do list of all that needs to be updated.
    if (empty($override)) {
        // We are updating the primary settings for the assignment, so we need to add all the overrides.
        $overrides = $DB->get_records('fastassignment_overrides', array('assignid' => $assigninstance->id), 'id ASC');
        // It is necessary to add an empty stdClass to the beginning of the array as the $oldevents
        // list contains the original (non-override) event for the module. If this is not included
        // the logic below will end up updating the wrong row when we try to reconcile this $overrides
        // list against the $oldevents list.
        array_unshift($overrides, new stdClass());
    } else {
        // Just do the one override.
        $overrides = array($override);
    }

    if (!empty($fastassignment->get_course_module())) {
        $cmid = $fastassignment->get_course_module()->id;
    } else {
        $cmid = get_coursemodule_from_instance('fastassignment', $assigninstance->id, $assigninstance->course)->id;
    }

    foreach ($overrides as $current) {
        $groupid   = isset($current->groupid) ? $current->groupid : 0;
        $userid    = isset($current->userid) ? $current->userid : 0;
        $duedate = isset($current->duedate) ? $current->duedate : $assigninstance->duedate;

        // Only add 'due' events for an override if they differ from the fastassignment default.
        $addclose = empty($current->id) || !empty($current->duedate);

        $event = new stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->description = format_module_intro('fastassignment', $assigninstance, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $assigninstance->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'fastassignment';
        $event->instance    = $assigninstance->id;
        $event->timestart   = $duedate;
        $event->timeduration = 0;
        $event->timesort    = $event->timestart + $event->timeduration;
        $event->visible     = instance_is_visible('fastassignment', $assigninstance);
        $event->eventtype   = FASTASSIGN_EVENT_TYPE_DUE;
        $event->priority    = null;

        // Determine the event name and priority.
        if ($groupid) {
            // Group override event.
            $params = new stdClass();
            $params->fastassignment = $assigninstance->name;
            $params->group = groups_get_group_name($groupid);
            if ($params->group === false) {
                // Group doesn't exist, just skip it.
                continue;
            }
            $eventname = get_string('overridegroupeventname', 'fastassignment', $params);
            // Set group override priority.
            if (isset($current->sortorder)) {
                $event->priority = $current->sortorder;
            }
        } else if ($userid) {
            // User override event.
            $params = new stdClass();
            $params->fastassignment = $assigninstance->name;
            $eventname = get_string('overrideusereventname', 'fastassignment', $params);
            // Set user override priority.
            $event->priority = CALENDAR_EVENT_USER_OVERRIDE_PRIORITY;
        } else {
            // The parent event.
            $eventname = $assigninstance->name;
        }

        if ($duedate && $addclose) {
            if ($oldevent = array_shift($oldevents)) {
                $event->id = $oldevent->id;
            } else {
                unset($event->id);
            }
            $event->name      = $eventname.' ('.get_string('duedate', 'fastassignment').')';
            calendar_event::create($event, false);
        }
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function fastassignment_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_COMMENT:
            return true;

        default:
            return null;
    }
}

/**
 * extend an assigment navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function fastassignment_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $navref->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    if (has_capability('mod/fastassignment:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/fastassignment/overrides.php', array('cmid' => $PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'fastassignment'),
            new moodle_url($url, array('mode' => 'group')),
            navigation_node::TYPE_SETTING, null, 'mod_fastassignment_groupoverrides');
        $navref->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'fastassignment'),
            new moodle_url($url, array('mode' => 'user')),
            navigation_node::TYPE_SETTING, null, 'mod_fastassignment_useroverrides');
        $navref->add_node($node, $beforekey);
    }

    // Link to gradebook.
    if (has_capability('gradereport/grader:view', $cm->context) &&
            has_capability('moodle/grade:viewall', $cm->context)) {
        $link = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
        $linkname = get_string('viewgradebook', 'fastassignment');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }

    // Link to download all submissions.
    if (has_any_capability(array('mod/fastassignment:grade', 'mod/fastassignment:viewgrades'), $context)) {
        $link = new moodle_url('/mod/fastassignment/view.php', array('id' => $cm->id, 'action'=>'grading'));
        $node = $navref->add(get_string('viewgrading', 'fastassignment'), $link, navigation_node::TYPE_SETTING);

        $link = new moodle_url('/mod/fastassignment/view.php', array('id' => $cm->id, 'action'=>'downloadall'));
        $node = $navref->add(get_string('downloadall', 'fastassignment'), $link, navigation_node::TYPE_SETTING);
    }

    if (has_capability('mod/fastassignment:revealidentities', $context)) {
        $dbparams = array('id'=>$cm->instance);
        $assignment = $DB->get_record('fastassignment', $dbparams, 'blindmarking, revealidentities');

        if ($assignment && $assignment->blindmarking && !$assignment->revealidentities) {
            $urlparams = array('id' => $cm->id, 'action'=>'revealidentities');
            $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
            $linkname = get_string('revealidentities', 'fastassignment');
            $node = $navref->add($linkname, $url, navigation_node::TYPE_SETTING);
        }
    }
}

/**
 * Add a get_coursemodule_info function in case any assignment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function fastassignment_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $dbparams = array('id'=>$coursemodule->instance);
    $fields = 'id, name, alwaysshowdescription, allowsubmissionsfromdate, intro, introformat, completionsubmit';
    if (! $assignment = $DB->get_record('fastassignment', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $assignment->name;
    if ($coursemodule->showdescription) {
        if ($assignment->alwaysshowdescription || time() > $assignment->allowsubmissionsfromdate) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('fastassignment', $assignment, $coursemodule->id, false);
        }
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $assignment->completionsubmit;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_fastassignment_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'fastassignment');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function fastassignment_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-fastassignment-*' => get_string('page-mod-fastassignment-x', 'fastassignment'),
        'mod-fastassignment-view' => get_string('page-mod-fastassignment-view', 'fastassignment'),
    );
    return $modulepagetype;
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function fastassignment_print_overview() {
    throw new coding_exception('fastassignment_print_overview() can not be used any more and is obsolete.');
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function fastassignment_get_mysubmission_details_for_print_overview() {
    throw new coding_exception('fastassignment_get_mysubmission_details_for_print_overview() can not be used any more and is obsolete.');
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function fastassignment_get_grade_details_for_print_overview() {
    throw new coding_exception('fastassignment_get_grade_details_for_print_overview() can not be used any more and is obsolete.');
}

/**
 * Print recent activity from all assignments in a given course
 *
 * This is used by the recent activity block
 * @param mixed $course the course to print activity for
 * @param bool $viewfullnames boolean to determine whether to show full names or not
 * @param int $timestart the time the rendering started
 * @return bool true if activity was printed, false otherwise.
 */
function fastassignment_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    // Do not use log table if possible, it may be huge.

    $dbparams = array($timestart, $course->id, 'fastassignment', FASTASSIGN_SUBMISSION_STATUS_SUBMITTED);
    $namefields = user_picture::fields('u', null, 'userid');
    if (!$submissions = $DB->get_records_sql("SELECT asb.id, asb.timemodified, cm.id AS cmid, um.id as recordid,
                                                     $namefields
                                                FROM {fastassignment_submission} asb
                                                     JOIN {fastassignment} a      ON a.id = asb.assignment
                                                     JOIN {course_modules} cm ON cm.instance = a.id
                                                     JOIN {modules} md        ON md.id = cm.module
                                                     JOIN {user} u            ON u.id = asb.userid
                                                LEFT JOIN {fastassignment_user_mappng} um ON um.userid = u.id AND um.assignment = a.id
                                               WHERE asb.timemodified > ? AND
                                                     asb.latest = 1 AND
                                                     a.course = ? AND
                                                     md.name = ? AND
                                                     asb.status = ?
                                            ORDER BY asb.timemodified ASC", $dbparams)) {
         return false;
    }

    $modinfo = get_fast_modinfo($course);
    $show    = array();
    $grader  = array();

    $showrecentsubmissions = get_config('fastassignment', 'showrecentsubmissions');

    foreach ($submissions as $submission) {
        if (!array_key_exists($submission->cmid, $modinfo->get_cms())) {
            continue;
        }
        $cm = $modinfo->get_cm($submission->cmid);
        if (!$cm->uservisible) {
            continue;
        }
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }

        $context = context_module::instance($submission->cmid);
        // The act of submitting of assignment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!array_key_exists($cm->id, $grader)) {
                $grader[$cm->id] = has_capability('moodle/grade:viewall', $context);
            }
            if (!$grader[$cm->id]) {
                continue;
            }
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups',  $context)) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newsubmissions', 'fastassignment').':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->get_cm($submission->cmid);
        $context = context_module::instance($submission->cmid);
        $fastassignment = new fastassignment($context, $cm, $cm->course);
        $link = $CFG->wwwroot.'/mod/fastassignment/view.php?id='.$cm->id;
        // Obscure first and last name if blind marking enabled.
        if ($fastassignment->is_blind_marking()) {
            $submission->firstname = get_string('participant', 'mod_fastassignment');
            if (empty($submission->recordid)) {
                $submission->recordid = $fastassignment->get_uniqueid_for_user($submission->userid);
            }
            $submission->lastname = $submission->recordid;
        }
        print_recent_activity_note($submission->timemodified,
                                   $submission,
                                   $cm->name,
                                   $link,
                                   false,
                                   $viewfullnames);
    }

    return true;
}

/**
 * Returns all assignments since a given time.
 *
 * @param array $activities The activity information is returned in this array
 * @param int $index The current index in the activities array
 * @param int $timestart The earliest activity to show
 * @param int $courseid Limit the search to this course
 * @param int $cmid The course module id
 * @param int $userid Optional user id
 * @param int $groupid Optional group id
 * @return void
 */
function fastassignment_get_recent_mod_activity(&$activities,
                                        &$index,
                                        $timestart,
                                        $courseid,
                                        $cmid,
                                        $userid=0,
                                        $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->get_cm($cmid);
    $params = array();
    if ($userid) {
        $userselect = 'AND u.id = :userid';
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;
    $params['submitted'] = FASTASSIGN_SUBMISSION_STATUS_SUBMITTED;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql('SELECT asb.id, asb.timemodified, ' .
                                                     $userfields .
                                             '  FROM {fastassignment_submission} asb
                                                JOIN {fastassignment} a ON a.id = asb.assignment
                                                JOIN {user} u ON u.id = asb.userid ' .
                                          $groupjoin .
                                            '  WHERE asb.timemodified > :timestart AND
                                                     asb.status = :submitted AND
                                                     a.id = :cminstance
                                                     ' . $userselect . ' ' . $groupselect .
                                            ' ORDER BY asb.timemodified ASC', $params)) {
         return;
    }

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $cmcontext      = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cmcontext);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cmcontext);


    $showrecentsubmissions = get_config('fastassignment', 'showrecentsubmissions');
    $show = array();
    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        // The act of submitting of assignment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!$grader) {
                continue;
            }
        }

        if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return;
    }

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($show as $id => $submission) {
            $userids[] = $submission->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'fastassignment', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $activity = new stdClass();

        $activity->type         = 'fastassignment';
        $activity->cmid         = $cm->id;
        $activity->name         = $aname;
        $activity->sectionnum   = $cm->sectionnum;
        $activity->timestamp    = $submission->timemodified;
        $activity->user         = new stdClass();
        if ($grader) {
            $activity->grade = $grades->items[0]->grades[$submission->userid]->str_long_grade;
        }

        $userfields = explode(',', user_picture::fields());
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                // Aliased in SQL above.
                $activity->user->{$userfield} = $submission->userid;
            } else {
                $activity->user->{$userfield} = $submission->{$userfield};
            }
        }
        $activity->user->fullname = fullname($submission, $viewfullnames);

        $activities[$index++] = $activity;
    }

    return;
}

/**
 * Print recent activity from all assignments in a given course
 *
 * This is used by course/recent.php
 * @param stdClass $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 */
function fastassignment_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="assignment-recent">';

    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, 'fastassignment');
        echo '<a href="' . $CFG->wwwroot . '/mod/fastassignment/view.php?id=' . $activity->cmid . '">';
        echo $activity->name;
        echo '</a>';
        echo '</div>';
    }

    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade').': ';
        echo $activity->grade;
        echo '</div>';
    }

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">";
    echo "{$activity->user->fullname}</a>  - " . userdate($activity->timestamp);
    echo '</div>';

    echo '</td></tr></table>';
}

/**
 * @deprecated since Moodle 3.8
 */
function fastassignment_scale_used() {
    throw new coding_exception('fastassignment_scale_used() can not be used anymore. Plugins can implement ' .
        '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Checks if scale is being used by any instance of assignment
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any assignment
 */
function fastassignment_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('fastassignment', array('grade'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function fastassignment_get_view_actions() {
    return array('view submission', 'view feedback');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function fastassignment_get_post_actions() {
    return array('upload', 'submit', 'submit for grading');
}

/**
 * Returns all other capabilities used by this module.
 * @return array Array of capability strings
 */
function fastassignment_get_extra_capabilities() {
    return ['gradereport/grader:view', 'moodle/grade:viewall'];
}

/**
 * Create grade item for given assignment.
 *
 * @param stdClass $fastassignment record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function fastassignment_grade_item_update($fastassignment, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($fastassignment->courseid)) {
        $fastassignment->courseid = $fastassignment->course;
    }

    $params = array('itemname'=>$fastassignment->name, 'idnumber'=>$fastassignment->cmidnumber);

    // Check if feedback plugin for gradebook is enabled, if yes then
    // gradetype = GRADE_TYPE_TEXT else GRADE_TYPE_NONE.
    $gradefeedbackenabled = false;

    if (isset($fastassignment->gradefeedbackenabled)) {
        $gradefeedbackenabled = $fastassignment->gradefeedbackenabled;
    } else if ($fastassignment->grade == 0) { // Grade feedback is needed only when grade == 0.
        require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
        $mod = get_coursemodule_from_instance('fastassignment', $fastassignment->id, $fastassignment->courseid);
        $cm = context_module::instance($mod->id);
        $assignment = new fastassignment($cm, null, null);
        $gradefeedbackenabled = $assignment->is_gradebook_feedback_enabled();
    }

    if ($fastassignment->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $fastassignment->grade;
        $params['grademin']  = 0;

    } else if ($fastassignment->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$fastassignment->grade;

    } else if ($gradefeedbackenabled) {
        // $fastassignment->grade == 0 and feedback enabled.
        $params['gradetype'] = GRADE_TYPE_TEXT;
    } else {
        // $fastassignment->grade == 0 and no feedback enabled.
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/fastassignment',
                        $fastassignment->courseid,
                        'mod',
                        'fastassignment',
                        $fastassignment->id,
                        0,
                        $grades,
                        $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $fastassignment record of fastassignment with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function fastassignment_get_user_grades($fastassignment, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $cm = get_coursemodule_from_instance('fastassignment', $fastassignment->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $assignment = new fastassignment($context, null, null);
    $assignment->set_instance($fastassignment);
    return $assignment->get_user_grades_for_gradebook($userid);
}

/**
 * Update activity grades.
 *
 * @param stdClass $fastassignment database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function fastassignment_update_grades($fastassignment, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($fastassignment->grade == 0) {
        fastassignment_grade_item_update($fastassignment);

    } else if ($grades = fastassignment_get_user_grades($fastassignment, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        fastassignment_grade_item_update($fastassignment, $grades);

    } else {
        fastassignment_grade_item_update($fastassignment);
    }
}

/**
 * List the file areas that can be browsed.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function fastassignment_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $areas = array(FASTASSIGN_INTROATTACHMENT_FILEAREA => get_string('introattachments', 'mod_fastassignment'));

    $assignment = new fastassignment($context, $cm, $course);
    foreach ($assignment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }
    foreach ($assignment->get_feedback_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }

    return $areas;
}

/**
 * File browsing support for fastassignment module.
 *
 * @param file_browser $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function fastassignment_get_file_info($browser,
                              $areas,
                              $course,
                              $cm,
                              $context,
                              $filearea,
                              $itemid,
                              $filepath,
                              $filename) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    $urlbase = $CFG->wwwroot.'/pluginfile.php';
    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;

    // Need to find where this belongs to.
    $assignment = new fastassignment($context, $cm, $course);
    if ($filearea === FASTASSIGN_INTROATTACHMENT_FILEAREA) {
        if (!has_capability('moodle/course:managefiles', $context)) {
            // Students can not peak here!
            return null;
        }
        if (!($storedfile = $fs->get_file($assignment->get_context()->id,
                                          'mod_fastassignment', $filearea, 0, $filepath, $filename))) {
            return null;
        }
        return new file_info_stored($browser,
                        $assignment->get_context(),
                        $storedfile,
                        $urlbase,
                        $filearea,
                        $itemid,
                        true,
                        true,
                        false);
    }

    $pluginowner = null;
    foreach ($assignment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if (array_key_exists($filearea, $pluginareas)) {
                $pluginowner = $plugin;
                break;
            }
        }
    }
    if (!$pluginowner) {
        foreach ($assignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible()) {
                $pluginareas = $plugin->get_file_areas();

                if (array_key_exists($filearea, $pluginareas)) {
                    $pluginowner = $plugin;
                    break;
                }
            }
        }
    }

    if (!$pluginowner) {
        return null;
    }

    $result = $pluginowner->get_file_info($browser, $filearea, $itemid, $filepath, $filename);
    return $result;
}

/**
 * Prints the complete info about a user's interaction with an assignment.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $fastassignment the database fastassignment record
 *
 * This prints the submission summary and feedback summary for this student.
 */
function fastassignment_user_complete($course, $user, $coursemodule, $fastassignment) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $context = context_module::instance($coursemodule->id);

    $assignment = new fastassignment($context, $coursemodule, $course);

    echo $assignment->view_student_summary($user, false);
}

/**
 * Rescale all grades for this activity and push the new grades to the gradebook.
 *
 * @param stdClass $course Course db record
 * @param stdClass $cm Course module db record
 * @param float $oldmin
 * @param float $oldmax
 * @param float $newmin
 * @param float $newmax
 */
function fastassignment_rescale_activity_grades($course, $cm, $oldmin, $oldmax, $newmin, $newmax) {
    global $DB;

    if ($oldmax <= $oldmin) {
        // Grades cannot be scaled.
        return false;
    }
    $scale = ($newmax - $newmin) / ($oldmax - $oldmin);
    if (($newmax - $newmin) <= 1) {
        // We would lose too much precision, lets bail.
        return false;
    }

    $params = array(
        'p1' => $oldmin,
        'p2' => $scale,
        'p3' => $newmin,
        'a' => $cm->instance
    );

    // Only rescale grades that are greater than or equal to 0. Anything else is a special value.
    $sql = 'UPDATE {fastassignment_grades} set grade = (((grade - :p1) * :p2) + :p3) where assignment = :a and grade >= 0';
    $dbupdate = $DB->execute($sql, $params);
    if (!$dbupdate) {
        return false;
    }

    // Now re-push all grades to the gradebook.
    $dbparams = array('id' => $cm->instance);
    $fastassignment = $DB->get_record('fastassignment', $dbparams);
    $fastassignment->cmidnumber = $cm->idnumber;

    fastassignment_update_grades($fastassignment);

    return true;
}

/**
 * Print the grade information for the assignment for this user.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $assignment
 */
function fastassignment_user_outline($course, $user, $coursemodule, $assignment) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/grade/grading/lib.php');

    $gradinginfo = grade_get_grades($course->id,
                                        'mod',
                                        'fastassignment',
                                        $assignment->id,
                                        $user->id);

    $gradingitem = $gradinginfo->items[0];
    $gradebookgrade = $gradingitem->grades[$user->id];

    if (empty($gradebookgrade->str_long_grade)) {
        return null;
    }
    $result = new stdClass();
    if (!$gradingitem->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
        $result->info = get_string('outlinegrade', 'fastassignment', $gradebookgrade->str_long_grade);
    } else {
        $result->info = get_string('grade') . ': ' . get_string('hidden', 'grades');
    }
    $result->time = $gradebookgrade->dategraded;

    return $result;
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in fastassignment settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function fastassignment_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $fastassignment = new fastassignment(null, $cm, $course);

    // If completion option is enabled, evaluate it and return true/false.
    if ($fastassignment->get_instance()->completionsubmit) {
        if ($fastassignment->get_instance()->teamsubmission) {
            $submission = $fastassignment->get_group_submission($userid, 0, false);
        } else {
            $submission = $fastassignment->get_user_submission($userid, false);
        }
        return $submission && $submission->status == FASTASSIGN_SUBMISSION_STATUS_SUBMITTED;
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}

/**
 * Serves intro attachment files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function fastassignment_pluginfile($course,
                $cm,
                context $context,
                $filearea,
                $args,
                $forcedownload,
                array $options=array()) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    if (!has_capability('mod/fastassignment:view', $context)) {
        return false;
    }

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
    $fastassignment = new fastassignment($context, $cm, $course);

    if ($filearea !== FASTASSIGN_INTROATTACHMENT_FILEAREA) {
        return false;
    }
    if (!$fastassignment->show_intro()) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_fastassignment/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Serve the grading panel as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function mod_fastassignment_output_fragment_gradingpanel($args) {
    global $CFG;

    $context = $args['context'];

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');
    $fastassignment = new fastassignment($context, null, null);

    $userid = clean_param($args['userid'], PARAM_INT);
    $attemptnumber = clean_param($args['attemptnumber'], PARAM_INT);
    $formdata = array();
    if (!empty($args['jsonformdata'])) {
        $serialiseddata = json_decode($args['jsonformdata']);
        parse_str($serialiseddata, $formdata);
    }
    $viewargs = array(
        'userid' => $userid,
        'attemptnumber' => $attemptnumber,
        'formdata' => $formdata
    );

    return $fastassignment->view('gradingpanel', $viewargs);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function fastassignment_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $updates = new stdClass();
    $updates = course_check_module_updates_since($cm, $from, array(FASTASSIGN_INTROATTACHMENT_FILEAREA), $filter);

    // Check if there is a new submission by the user or new grades.
    $select = 'assignment = :id AND userid = :userid AND (timecreated > :since1 OR timemodified > :since2)';
    $params = array('id' => $cm->instance, 'userid' => $USER->id, 'since1' => $from, 'since2' => $from);
    $updates->submissions = (object) array('updated' => false);
    $submissions = $DB->get_records_select('fastassignment_submission', $select, $params, '', 'id');
    if (!empty($submissions)) {
        $updates->submissions->updated = true;
        $updates->submissions->itemids = array_keys($submissions);
    }

    $updates->grades = (object) array('updated' => false);
    $grades = $DB->get_records_select('fastassignment_grades', $select, $params, '', 'id');
    if (!empty($grades)) {
        $updates->grades->updated = true;
        $updates->grades->itemids = array_keys($grades);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/fastassignment:viewgrades', $cm->context)) {
        $params = array('id' => $cm->instance, 'since1' => $from, 'since2' => $from);
        $select = 'assignment = :id AND (timecreated > :since1 OR timemodified > :since2)';

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers, SQL_PARAMS_NAMED);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->usersubmissions = (object) array('updated' => false);
        $submissions = $DB->get_records_select('fastassignment_submission', $select, $params, '', 'id');
        if (!empty($submissions)) {
            $updates->usersubmissions->updated = true;
            $updates->usersubmissions->itemids = array_keys($submissions);
        }

        $updates->usergrades = (object) array('updated' => false);
        $grades = $DB->get_records_select('fastassignment_grades', $select, $params, '', 'id');
        if (!empty($grades)) {
            $updates->usergrades->updated = true;
            $updates->usergrades->itemids = array_keys($grades);
        }
    }

    return $updates;
}

/**
 * Is the event visible?
 *
 * This is used to determine global visibility of an event in all places throughout Moodle. For example,
 * the FASTASSIGN_EVENT_TYPE_GRADINGDUE event will not be shown to students on their calendar.
 *
 * @param calendar_event $event
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return bool Returns true if the event is visible to the current user, false otherwise.
 */
function mod_fastassignment_core_calendar_is_event_visible(calendar_event $event, $userid = 0) {
    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['fastassignment'][$event->instance];
    $context = context_module::instance($cm->id);

    $fastassignment = new fastassignment($context, $cm, null);

    if ($event->eventtype == FASTASSIGN_EVENT_TYPE_GRADINGDUE) {
        return $fastassignment->can_grade($userid);
    } else {
        return true;
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_fastassignment_core_calendar_provide_event_action(calendar_event $event,
                                                       \core_calendar\action_factory $factory,
                                                       $userid = 0) {

    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['fastassignment'][$event->instance];
    $context = context_module::instance($cm->id);

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    $fastassignment = new fastassignment($context, $cm, null);

    // Apply overrides.
    $fastassignment->update_effective_access($userid);

    if ($event->eventtype == FASTASSIGN_EVENT_TYPE_GRADINGDUE) {
        $name = get_string('grade');
        $url = new \moodle_url('/mod/fastassignment/view.php', [
            'id' => $cm->id,
            'action' => 'grader'
        ]);
        $itemcount = $fastassignment->count_submissions_need_grading();
        $actionable = $fastassignment->can_grade($userid) && (time() >= $fastassignment->get_instance()->allowsubmissionsfromdate);
    } else {
        $usersubmission = $fastassignment->get_user_submission($userid, false);
        if ($usersubmission && $usersubmission->status === FASTASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            // The user has already submitted.
            // We do not want to change the text to edit the submission, we want to remove the event from the Dashboard entirely.
            return null;
        }

        $participant = $fastassignment->get_participant($userid);

        if (!$participant) {
            // If the user is not a participant in the assignment then they have
            // no action to take. This will filter out the events for teachers.
            return null;
        }

        // The user has not yet submitted anything. Show the addsubmission link.
        $name = get_string('addsubmission', 'fastassignment');
        $url = new \moodle_url('/mod/fastassignment/view.php', [
            'id' => $cm->id,
            'action' => 'editsubmission'
        ]);
        $itemcount = 1;
        $actionable = $fastassignment->is_any_submission_plugin_enabled() && $fastassignment->can_edit_submission($userid, $userid);
    }

    return $factory->create_instance(
        $name,
        $url,
        $itemcount,
        $actionable
    );
}

/**
 * Callback function that determines whether an action event should be showing its item count
 * based on the event type and the item count.
 *
 * @param calendar_event $event The calendar event.
 * @param int $itemcount The item count associated with the action event.
 * @return bool
 */
function mod_fastassignment_core_calendar_event_action_shows_item_count(calendar_event $event, $itemcount = 0) {
    // List of event types where the action event's item count should be shown.
    $eventtypesshowingitemcount = [
        FASTASSIGN_EVENT_TYPE_GRADINGDUE
    ];
    // For mod_fastassignment, item count should be shown if the event type is 'gradingdue' and there is one or more item count.
    return in_array($event->eventtype, $eventtypesshowingitemcount) && $itemcount > 0;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the sbumission start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * If the event does not have a valid timestart range then [false, false] will
 * be returned.
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_fastassignment_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);
    $fastassignment = new fastassignment($context, null, null);
    $fastassignment->set_instance($instance);

    return $fastassignment->get_valid_calendar_event_timestart_range($event);
}

/**
 * This function will update the fastassignment module according to the
 * event that has been modified.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $instance The module instance to get the range from
 */
function mod_fastassignment_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $instance) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

    if (empty($event->instance) || $event->modulename != 'fastassignment') {
        return;
    }

    if ($instance->id != $event->instance) {
        return;
    }

    if (!in_array($event->eventtype, [FASTASSIGN_EVENT_TYPE_DUE, FASTASSIGN_EVENT_TYPE_GRADINGDUE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;
    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $fastassignment = new fastassignment($context, $coursemodule, null);
    $fastassignment->set_instance($instance);

    if ($event->eventtype == FASTASSIGN_EVENT_TYPE_DUE) {
        // This check is in here because due date events are currently
        // the only events that can be overridden, so we can save a DB
        // query if we don't bother checking other events.
        if ($fastassignment->is_override_calendar_event($event)) {
            // This is an override event so we should ignore it.
            return;
        }

        $newduedate = $event->timestart;

        if ($newduedate != $instance->duedate) {
            $instance->duedate = $newduedate;
            $modified = true;
        }
    } else if ($event->eventtype == FASTASSIGN_EVENT_TYPE_GRADINGDUE) {
        $newduedate = $event->timestart;

        if ($newduedate != $instance->gradingduedate) {
            $instance->gradingduedate = $newduedate;
            $modified = true;
        }
    }

    if ($modified) {
        $instance->timemodified = time();
        // Persist the fastassignment instance changes.
        $DB->update_record('fastassignment', $instance);
        $fastassignment->update_calendar($coursemodule->id);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Return a list of all the user preferences used by mod_fastassignment.
 *
 * @return array
 */
function mod_fastassignment_user_preferences() {
    $preferences = array();
    $preferences['fastassignment_filter'] = array(
        'type' => PARAM_ALPHA,
        'null' => NULL_NOT_ALLOWED,
        'default' => ''
    );
    $preferences['fastassignment_workflowfilter'] = array(
        'type' => PARAM_ALPHA,
        'null' => NULL_NOT_ALLOWED,
        'default' => ''
    );
    $preferences['fastassignment_markerfilter'] = array(
        'type' => PARAM_ALPHANUMEXT,
        'null' => NULL_NOT_ALLOWED,
        'default' => ''
    );

    return $preferences;
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_fastassignment_get_path_from_pluginfile(string $filearea, array $args) : array {
    // fastassignment never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}
