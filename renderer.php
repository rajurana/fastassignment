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
 * This file contains a renderer for the assignment class
 *
 * @package   mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/fastassignment/locallib.php');

use \mod_fastassignment\output\grading_app;

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the fastassignment module.
 *
 * @package mod_fastassignment
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fastassignment_renderer extends plugin_renderer_base {

    /**
     * Rendering assignment files
     *
     * @param context $context
     * @param int $userid
     * @param string $filearea
     * @param string $component
     * @return string
     */
    public function fastassignment_files(context $context, $userid, $filearea, $component) {
        return $this->render(new fastassignment_files($context, $userid, $filearea, $component));
    }

    /**
     * Rendering assignment files
     *
     * @param fastassignment_files $tree
     * @return string
     */
    public function render_fastassignment_files(fastassignment_files $tree) {
        $this->htmlid = html_writer::random_id('fastassignment_files_tree');
        $this->page->requires->js_init_call('M.mod_fastassignment.init_tree', array(true, $this->htmlid));
        $html = '<div id="'.$this->htmlid.'">';
        $html .= $this->htmllize_tree($tree, $tree->dir);
        $html .= '</div>';

        if ($tree->portfolioform) {
            $html .= $tree->portfolioform;
        }
        return $html;
    }

    /**
     * Utility function to add a row of data to a table with 2 columns where the first column is the table's header.
     * Modified the table param and does not return a value.
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @param array $firstattributes The first column attributes (optional)
     * @param array $secondattributes The second column attributes (optional)
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second, $firstattributes = [],
            $secondattributes = []) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell1->header = true;
        if (!empty($firstattributes)) {
            $cell1->attributes = $firstattributes;
        }
        $cell2 = new html_table_cell($second);
        if (!empty($secondattributes)) {
            $cell2->attributes = $secondattributes;
        }
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a grading message notification
     * @param fastassignment_gradingmessage $result The result to render
     * @return string
     */
    public function render_fastassignment_gradingmessage(fastassignment_gradingmessage $result) {
        $urlparams = array('id' => $result->coursemoduleid, 'action'=>'grading');
        if (!empty($result->page)) {
            $urlparams['page'] = $result->page;
        }
        $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
        $classes = $result->gradingerror ? 'notifyproblem' : 'notifysuccess';

        $o = '';
        $o .= $this->output->heading($result->heading, 4);
        $o .= $this->output->notification($result->message, $classes);
        $o .= $this->output->continue_button($url);
        return $o;
    }

    /**
     * Render the generic form
     * @param fastassignment_form $form The form to render
     * @return string
     */
    public function render_fastassignment_form(fastassignment_form $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }

    /**
     * Render the user summary
     *
     * @param fastassignment_user_summary $summary The user summary to render
     * @return string
     */
    public function render_fastassignment_user_summary(fastassignment_user_summary $summary) {
        $o = '';
        $supendedclass = '';
        $suspendedicon = '';

        if (!$summary->user) {
            return;
        }

        if ($summary->suspendeduser) {
            $supendedclass = ' usersuspended';
            $suspendedstring = get_string('userenrolmentsuspended', 'grades');
            $suspendedicon = ' ' . $this->pix_icon('i/enrolmentsuspended', $suspendedstring);
        }
        $o .= $this->output->container_start('usersummary');
        $o .= $this->output->box_start('boxaligncenter usersummarysection'.$supendedclass);
        if ($summary->blindmarking) {
            $o .= get_string('hiddenuser', 'fastassignment') . $summary->uniqueidforuser.$suspendedicon;
        } else {
            $o .= $this->output->user_picture($summary->user);
            $o .= $this->output->spacer(array('width'=>30));
            $urlparams = array('id' => $summary->user->id, 'course'=>$summary->courseid);
            $url = new moodle_url('/user/view.php', $urlparams);
            $fullname = fullname($summary->user, $summary->viewfullnames);
            $extrainfo = array();
            foreach ($summary->extrauserfields as $extrafield) {
                $extrainfo[] = $summary->user->$extrafield;
            }
            if (count($extrainfo)) {
                $fullname .= ' (' . implode(', ', $extrainfo) . ')';
            }
            $fullname .= $suspendedicon;
            $o .= $this->output->action_link($url, $fullname);
        }
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Render the submit for grading page
     *
     * @param fastassignment_submit_for_grading_page $page
     * @return string
     */
    public function render_fastassignment_submit_for_grading_page($page) {
        $o = '';

        $o .= $this->output->container_start('submitforgrading');
        $o .= $this->output->heading(get_string('confirmsubmissionheading', 'fastassignment'), 3);

        $cancelurl = new moodle_url('/mod/fastassignment/view.php', array('id' => $page->coursemoduleid));
        if (count($page->notifications)) {
            // At least one of the submission plugins is not ready for submission.

            $o .= $this->output->heading(get_string('submissionnotready', 'fastassignment'), 4);

            foreach ($page->notifications as $notification) {
                $o .= $this->output->notification($notification);
            }

            $o .= $this->output->continue_button($cancelurl);
        } else {
            // All submission plugins ready - show the confirmation form.
            $o .= $this->moodleform($page->confirmform);
        }
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * Render the header.
     *
     * @param fastassignment_header $header
     * @return string
     */
    public function render_fastassignment_header(fastassignment_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
            $args = ['contextname' => $header->context->get_context_name(false, true), 'subpage' => $header->subpage];
            $title = get_string('subpagetitle', 'fastassignment', $args);
        } else {
            $title = $header->context->get_context_name(false, true);
        }
        $courseshortname = $header->context->get_course_context()->get_context_name(false, true);
        $title = $courseshortname . ': ' . $title;
        $heading = format_string($header->fastassignment->name, false, array('context' => $header->context));

        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('fastassignment', $header->fastassignment, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the header for an individual plugin.
     *
     * @param fastassignment_plugin_header $header
     * @return string
     */
    public function render_fastassignment_plugin_header(fastassignment_plugin_header $header) {
        $o = $header->plugin->view_header();
        return $o;
    }

    /**
     * Render a table containing the current status of the grading process.
     *
     * @param fastassignment_grading_summary $summary
     * @return string
     */
    public function render_fastassignment_grading_summary(fastassignment_grading_summary $summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'fastassignment'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Visibility Status.
        $cell1content = get_string('hiddenfromstudents');
        $cell2content = (!$summary->isvisible) ? get_string('yes') : get_string('no');
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Status.
        if ($summary->teamsubmission) {
            if ($summary->warnofungroupedusers === fastassignment_grading_summary::WARN_GROUPS_REQUIRED) {
                $o .= $this->output->notification(get_string('ungroupedusers', 'fastassignment'));
            } else if ($summary->warnofungroupedusers === fastassignment_grading_summary::WARN_GROUPS_OPTIONAL) {
                $o .= $this->output->notification(get_string('ungroupedusersoptional', 'fastassignment'));
            }
            $cell1content = get_string('numberofteams', 'fastassignment');
        } else {
            $cell1content = get_string('numberofparticipants', 'fastassignment');
        }

        $cell2content = $summary->participantcount;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Drafts count and dont show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $cell1content = get_string('numberofdraftsubmissions', 'fastassignment');
            $cell2content = $summary->submissiondraftscount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $cell1content = get_string('numberofsubmittedassignments', 'fastassignment');
            $cell2content = $summary->submissionssubmittedcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if (!$summary->teamsubmission) {
                $cell1content = get_string('numberofsubmissionsneedgrading', 'fastassignment');
                $cell2content = $summary->submissionsneedgradingcount;
                $this->add_table_row_tuple($t, $cell1content, $cell2content);
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $cell1content = get_string('duedate', 'fastassignment');
            $duedate = $summary->duedate;
            if ($summary->courserelativedatesmode) {
                // Returns a formatted string, in the format '10d 10h 45m'.
                $diffstr = get_time_interval_string($duedate, $summary->coursestartdate);
                if ($duedate >= $summary->coursestartdate) {
                    $cell2content = get_string('relativedatessubmissionduedateafter', 'mod_fastassignment',
                        ['datediffstr' => $diffstr]);
                } else {
                    $cell2content = get_string('relativedatessubmissionduedatebefore', 'mod_fastassignment',
                        ['datediffstr' => $diffstr]);
                }
            } else {
                $cell2content = userdate($duedate);
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            // Time remaining.
            $cell1content = get_string('timeremaining', 'fastassignment');
            if ($summary->courserelativedatesmode) {
                $cell2content = get_string('relativedatessubmissiontimeleft', 'mod_fastassignment');
            } else {
                if ($duedate - $time <= 0) {
                    $cell2content = get_string('assignmentisdue', 'fastassignment');
                } else {
                    $cell2content = format_time($duedate - $time);
                }
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if ($duedate < $time) {
                $cell1content = get_string('latesubmissions', 'fastassignment');
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $cell2content = get_string('latesubmissionsaccepted', 'fastassignment', userdate($summary->cutoffdate));
                    } else {
                        $cell2content = get_string('nomoresubmissionsaccepted', 'fastassignment');
                    }

                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }
            }

        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the grading page.
        $o .= html_writer::start_tag('center');
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
        $o .= html_writer::link($url, get_string('viewgrading', 'mod_fastassignment'),
            ['class' => 'btn btn-secondary']);
        if ($summary->cangrade) {
            $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grader');
            $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
            $o .= html_writer::link($url, get_string('grade'),
                ['class' => 'btn btn-primary ml-1']);
        }
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= html_writer::end_tag('center');

        return $o;
    }

    /**
     * Render a table containing all the current grades and feedback.
     *
     * @param fastassignment_feedback_status $status
     * @return string
     */
    public function render_fastassignment_feedback_status(fastassignment_feedback_status $status) {
        $o = '';

        $o .= $this->output->container_start('feedback');
        $o .= $this->output->heading(get_string('feedback', 'fastassignment'), 3);
        $o .= $this->output->box_start('boxaligncenter feedbacktable');
        $t = new html_table();

        // Grade.
        if (isset($status->gradefordisplay)) {
            $cell1content = get_string('grade');
            $cell2content = $status->gradefordisplay;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            // Grade date.
            $cell1content = get_string('gradedon', 'fastassignment');
            $cell2content = userdate($status->gradeddate);
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        if ($status->grader) {
            // Grader.
            $cell1content = get_string('gradedby', 'fastassignment');
            $cell2content = $this->output->user_picture($status->grader) .
                            $this->output->spacer(array('width' => 30)) .
                            fullname($status->grader, $status->canviewfullnames);
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        foreach ($status->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() &&
                    $plugin->is_visible() &&
                    $plugin->has_user_summary() &&
                    !empty($status->grade) &&
                    !$plugin->is_empty($status->grade)) {

                $displaymode = fastassignment_feedback_plugin_feedback::SUMMARY;
                $pluginfeedback = new fastassignment_feedback_plugin_feedback($plugin,
                                                                      $status->grade,
                                                                      $displaymode,
                                                                      $status->coursemoduleid,
                                                                      $status->returnaction,
                                                                      $status->returnparams);
                $cell1content = $plugin->get_name();
                $cell2content = $this->render($pluginfeedback);
                $this->add_table_row_tuple($t, $cell1content, $cell2content);
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a compact view of the current status of the submission.
     *
     * @param fastassignment_submission_status_compact $status
     * @return string
     */
    public function render_fastassignment_submission_status_compact(fastassignment_submission_status_compact $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading(get_string('submission', 'fastassignment'), 3);
        $time = time();

        if ($status->teamsubmissionenabled) {
            $group = $status->submissiongroup;
            if ($group) {
                $team = format_string($group->name, false, $status->context);
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $team = '<span class="alert alert-error">' . get_string('noteam', 'fastassignment') . '</span>';
                } else if (count($status->usergroups) > 1) {
                    $team = '<span class="alert alert-error">' . get_string('multipleteams', 'fastassignment') . '</span>';
                }
            } else {
                $team = get_string('defaultteam', 'fastassignment');
            }
            $o .= $this->output->container(get_string('teamname', 'fastassignment', $team), 'teamname');
        }

        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != FASTASSIGN_SUBMISSION_STATUS_NEW) {
                $statusstr = get_string('submissionstatus_' . $status->submission->status, 'fastassignment');
                $o .= $this->output->container($statusstr, 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $o .= $this->output->container(get_string('noonlinesubmissions', 'fastassignment'), 'submissionstatus');
                } else {
                    $o .= $this->output->container(get_string('noattempt', 'fastassignment'), 'submissionstatus');
                }
            }
        } else {
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $o .= $this->output->container(get_string('nosubmission', 'fastassignment'), 'submissionstatus');
            } else if ($status->teamsubmission && $status->teamsubmission->status != FASTASSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_' . $teamstatus, 'fastassignment');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course' => $status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == fastassignment_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'fastassignment', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }
                $o .= $this->output->container($submissionsummary, 'submissionstatus' . $status->teamsubmission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $o .= $this->output->container(get_string('noonlinesubmissions', 'fastassignment'), 'submissionstatus');
                } else {
                    $o .= $this->output->container(get_string('nosubmission', 'fastassignment'), 'submissionstatus');
                }
            }
        }

        // Is locked?
        if ($status->locked) {
            $o .= $this->output->container(get_string('submissionslocked', 'fastassignment'), 'submissionlocked');
        }

        // Grading status.
        $statusstr = '';
        $classname = 'gradingstatus';
        if ($status->gradingstatus == FASTASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == FASTASSIGN_GRADING_STATUS_NOT_GRADED) {
            $statusstr = get_string($status->gradingstatus, 'fastassignment');
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $statusstr = get_string($gradingstatus, 'fastassignment');
        }
        if ($status->gradingstatus == FASTASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == FASTASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $classname = 'submissiongraded';
        } else {
            $classname = 'submissionnotgraded';
        }
        $o .= $this->output->container($statusstr, $classname);

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {

            if ($status->extensionduedate) {
                // Extension date.
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $classname = 'timeremaining';
            if ($duedate - $time <= 0) {
                if (!$submission ||
                        $submission->status != FASTASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $remaining = get_string('overdue', 'fastassignment', format_time($time - $duedate));
                        $classname = 'overdue';
                    } else {
                        $remaining = get_string('duedatereached', 'fastassignment');
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $remaining = get_string('submittedlate',
                                              'fastassignment',
                                              format_time($submission->timemodified - $duedate));
                        $classname = 'latesubmission';
                    } else {
                        $remaining = get_string('submittedearly',
                                               'fastassignment',
                                               format_time($submission->timemodified - $duedate));
                        $classname = 'earlysubmission';
                    }
                }
            } else {
                $remaining = get_string('paramtimeremaining', 'fastassignment', format_time($duedate - $time));
            }
            $o .= $this->output->container($remaining, $classname);
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == fastassignment_submission_status::GRADER_VIEW) {
            if ($status->canedit) {
                $o .= $this->output->container(get_string('submissioneditable', 'fastassignment'), 'submissioneditable');
            } else {
                $o .= $this->output->container(get_string('submissionnoteditable', 'fastassignment'), 'submissionnoteditable');
            }
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $o .= $this->output->container($status->gradingcontrollerpreview, 'gradingmethodpreview');
        }

        if ($submission) {

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        $pluginshowsummary
                    ) {

                        $displaymode = fastassignment_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new fastassignment_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();
                        $o .= $this->output->container($this->render($pluginsubmission), 'fastassignsubmission ' . $plugincomponent);
                    }
                }
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Render a table containing the current status of the submission.
     *
     * @param fastassignment_submission_status $status
     * @return string
     */
    public function render_fastassignment_submission_status(fastassignment_submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading(get_string('submissionstatusheading', 'fastassignment'), 3);
        $time = time();

        if ($status->allowsubmissionsfromdate &&
                $time <= $status->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');
            if ($status->alwaysshowdescription) {
                $date = userdate($status->allowsubmissionsfromdate);
                $o .= get_string('allowsubmissionsfromdatesummary', 'fastassignment', $date);
            } else {
                $date = userdate($status->allowsubmissionsfromdate);
                $o .= get_string('allowsubmissionsanddescriptionfromdatesummary', 'fastassignment', $date);
            }
            $o .= $this->output->box_end();
        }
        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        $warningmsg = '';
        if ($status->teamsubmissionenabled) {
            $cell1content = get_string('submissionteam', 'fastassignment');
            $group = $status->submissiongroup;
            if ($group) {
                $cell2content = format_string($group->name, false, $status->context);
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $notification = new \core\output\notification(get_string('noteam', 'fastassignment'), 'error');
                    $notification->set_show_closebutton(false);
                    $warningmsg = $this->output->notification(get_string('noteam_desc', 'fastassignment'), 'error');
                } else if (count($status->usergroups) > 1) {
                    $notification = new \core\output\notification(get_string('multipleteams', 'fastassignment'), 'error');
                    $notification->set_show_closebutton(false);
                    $warningmsg = $this->output->notification(get_string('multipleteams_desc', 'fastassignment'), 'error');
                }
                $cell2content = $this->output->render($notification);
            } else {
                $cell2content = get_string('defaultteam', 'fastassignment');
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        if ($status->attemptreopenmethod != FASTASSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }

            $cell1content = get_string('attemptnumber', 'fastassignment');
            $maxattempts = $status->maxattempts;
            if ($maxattempts == FASTASSIGN_UNLIMITED_ATTEMPTS) {
                $cell2content = get_string('currentattempt', 'fastassignment', $currentattempt);
            } else {
                $cell2content = get_string('currentattemptof', 'fastassignment',
                    array('attemptnumber' => $currentattempt, 'maxattempts' => $maxattempts));
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        $cell1content = get_string('submissionstatus', 'fastassignment');
        $cell2attributes = [];
        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != FASTASSIGN_SUBMISSION_STATUS_NEW) {
                $cell2content = get_string('submissionstatus_' . $status->submission->status, 'fastassignment');
                $cell2attributes = array('class' => 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2content = get_string('noonlinesubmissions', 'fastassignment');
                } else {
                    $cell2content = get_string('noattempt', 'fastassignment');
                }
            }
        } else {
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $cell2content = get_string('nosubmission', 'fastassignment');
            } else if ($status->teamsubmission && $status->teamsubmission->status != FASTASSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $cell2content = get_string('submissionstatus_' . $teamstatus, 'fastassignment');

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course'=>$status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == fastassignment_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'fastassignment', $userstr);
                    $cell2content .= $this->output->container($formatteduserstr);
                }

                $cell2attributes = array('class' => 'submissionstatus' . $status->teamsubmission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2content = get_string('noonlinesubmissions', 'fastassignment');
                } else {
                    $cell2content = get_string('nosubmission', 'fastassignment');
                }
            }
        }

        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        // Is locked?
        if ($status->locked) {
            $cell1content = '';
            $cell2content = get_string('submissionslocked', 'fastassignment');
            $cell2attributes = array('class' => 'submissionlocked');
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Grading status.
        $cell1content = get_string('gradingstatus', 'fastassignment');
        if ($status->gradingstatus == FASTASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == FASTASSIGN_GRADING_STATUS_NOT_GRADED) {
            $cell2content = get_string($status->gradingstatus, 'fastassignment');
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $cell2content = get_string($gradingstatus, 'fastassignment');
        }
        if ($status->gradingstatus == FASTASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == FASTASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $cell2attributes = array('class' => 'submissiongraded');
        } else {
            $cell2attributes = array('class' => 'submissionnotgraded');
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            // Due date.
            $cell1content = get_string('duedate', 'fastassignment');
            $cell2content = userdate($duedate);
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if ($status->view == fastassignment_submission_status::GRADER_VIEW) {
                if ($status->cutoffdate) {
                    // Cut off date.
                    $cell1content = get_string('cutoffdate', 'fastassignment');
                    $cell2content = userdate($status->cutoffdate);
                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }
            }

            if ($status->extensionduedate) {
                // Extension date.
                $cell1content = get_string('extensionduedate', 'fastassignment');
                $cell2content = userdate($status->extensionduedate);
                $this->add_table_row_tuple($t, $cell1content, $cell2content);
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $cell1content = get_string('timeremaining', 'fastassignment');
            $cell2attributes = [];
            if ($duedate - $time <= 0) {
                if (!$submission ||
                        $submission->status != FASTASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $cell2content = get_string('overdue', 'fastassignment', format_time($time - $duedate));
                        $cell2attributes = array('class' => 'overdue');
                    } else {
                        $cell2content = get_string('duedatereached', 'fastassignment');
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $cell2content = get_string('submittedlate',
                                              'fastassignment',
                                              format_time($submission->timemodified - $duedate));
                        $cell2attributes = array('class' => 'latesubmission');
                    } else {
                        $cell2content = get_string('submittedearly',
                                               'fastassignment',
                                               format_time($submission->timemodified - $duedate));
                        $cell2attributes = array('class' => 'earlysubmission');
                    }
                }
            } else {
                $cell2content = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == fastassignment_submission_status::GRADER_VIEW) {
            $cell1content = get_string('editingstatus', 'fastassignment');
            if ($status->canedit) {
                $cell2content = get_string('submissioneditable', 'fastassignment');
                $cell2attributes = array('class' => 'submissioneditable');
            } else {
                $cell2content = get_string('submissionnoteditable', 'fastassignment');
                $cell2attributes = array('class' => 'submissionnoteditable');
            }
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $cell1content = get_string('gradingmethodpreview', 'fastassignment');
            $cell2content = $status->gradingcontrollerpreview;
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Last modified.
        if ($submission) {
            $cell1content = get_string('timemodified', 'fastassignment');

            if ($submission->status != FASTASSIGN_SUBMISSION_STATUS_NEW) {
                $cell2content = userdate($submission->timemodified);
            } else {
                $cell2content = "-";
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        $pluginshowsummary
                    ) {

                        $cell1content = $plugin->get_name();
                        $displaymode = fastassignment_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new fastassignment_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $cell2content = $this->render($pluginsubmission);
                        $this->add_table_row_tuple($t, $cell1content, $cell2content);
                    }
                }
            }
        }

        $o .= $warningmsg;
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Links.
        if ($status->view == fastassignment_submission_status::STUDENT_VIEW) {
            if ($status->canedit) {
                if (!$submission || $submission->status == FASTASSIGN_SUBMISSION_STATUS_NEW) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                       get_string('addsubmission', 'fastassignment'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addsubmission_help', 'fastassignment');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else if ($submission->status == FASTASSIGN_SUBMISSION_STATUS_REOPENED) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid,
                                       'action' => 'editprevioussubmission',
                                       'sesskey'=>sesskey());
                    $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                       get_string('addnewattemptfromprevious', 'fastassignment'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattemptfromprevious_help', 'fastassignment');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                       get_string('addnewattempt', 'fastassignment'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattempt_help', 'fastassignment');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                       get_string('editsubmission', 'fastassignment'), 'get');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'removesubmissionconfirm');
                    $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                       get_string('removesubmission', 'fastassignment'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'fastassignment');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                }
            }

            if ($status->cansubmit) {
                $urlparams = array('id' => $status->coursemoduleid, 'action'=>'submit');
                $o .= $this->output->box_start('generalbox submissionaction');
                $o .= $this->output->single_button(new moodle_url('/mod/fastassignment/view.php', $urlparams),
                                                   get_string('submitassignment', 'fastassignment'), 'get');
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= get_string('submitassignment_help', 'fastassignment');
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }

        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Output the attempt history chooser for this assignment
     *
     * @param fastassignment_attempt_history_chooser $history
     * @return string
     */
    public function render_fastassignment_attempt_history_chooser(fastassignment_attempt_history_chooser $history) {
        $o = '';

        $context = $history->export_for_template($this);
        $o .= $this->render_from_template('mod_fastassignment/attempt_history_chooser', $context);

        return $o;
    }

    /**
     * Output the attempt history for this assignment
     *
     * @param fastassignment_attempt_history $history
     * @return string
     */
    public function render_fastassignment_attempt_history(fastassignment_attempt_history $history) {
        $o = '';

        // Don't show the last one because it is the current submission.
        array_pop($history->submissions);

        // Show newest to oldest.
        $history->submissions = array_reverse($history->submissions);

        if (empty($history->submissions)) {
            return '';
        }

        $containerid = 'attempthistory' . uniqid();
        $o .= $this->output->heading(get_string('attempthistory', 'fastassignment'), 3);
        $o .= $this->box_start('attempthistory', $containerid);

        foreach ($history->submissions as $i => $submission) {
            $grade = null;
            foreach ($history->grades as $onegrade) {
                if ($onegrade->attemptnumber == $submission->attemptnumber) {
                    if ($onegrade->grade != FASTASSIGN_GRADE_NOT_SET) {
                        $grade = $onegrade;
                    }
                    break;
                }
            }

            if ($submission) {
                $submissionsummary = userdate($submission->timemodified);
            } else {
                $submissionsummary = get_string('nosubmission', 'fastassignment');
            }

            $attemptsummaryparams = array('attemptnumber'=>$submission->attemptnumber+1,
                                          'submissionsummary'=>$submissionsummary);
            $o .= $this->heading(get_string('attemptheading', 'fastassignment', $attemptsummaryparams), 4);

            $t = new html_table();

            if ($submission) {
                $cell1content = get_string('submissionstatus', 'fastassignment');
                $cell2content = get_string('submissionstatus_' . $submission->status, 'fastassignment');
                $this->add_table_row_tuple($t, $cell1content, $cell2content);

                foreach ($history->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                            $plugin->is_visible() &&
                            $plugin->has_user_summary() &&
                            $pluginshowsummary) {

                        $cell1content = $plugin->get_name();
                        $pluginsubmission = new fastassignment_submission_plugin_submission($plugin,
                                                                                    $submission,
                                                                                    fastassignment_submission_plugin_submission::SUMMARY,
                                                                                    $history->coursemoduleid,
                                                                                    $history->returnaction,
                                                                                    $history->returnparams);
                        $cell2content = $this->render($pluginsubmission);
                        $this->add_table_row_tuple($t, $cell1content, $cell2content);
                    }
                }
            }

            if ($grade) {
                // Heading 'feedback'.
                $title = get_string('feedback', 'fastassignment', $i);
                $title .= $this->output->spacer(array('width'=>10));
                if ($history->cangrade) {
                    // Edit previous feedback.
                    $returnparams = http_build_query($history->returnparams);
                    $urlparams = array('id' => $history->coursemoduleid,
                                   'rownum'=>$history->rownum,
                                   'useridlistid'=>$history->useridlistid,
                                   'attemptnumber'=>$grade->attemptnumber,
                                   'action'=>'grade',
                                   'returnaction'=>$history->returnaction,
                                   'returnparams'=>$returnparams);
                    $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
                    $icon = new pix_icon('gradefeedback',
                                            get_string('editattemptfeedback', 'fastassignment', $grade->attemptnumber+1),
                                            'mod_fastassignment');
                    $title .= $this->output->action_icon($url, $icon);
                }
                $cell = new html_table_cell($title);
                $cell->attributes['class'] = 'feedbacktitle';
                $cell->colspan = 2;
                $t->data[] = new html_table_row(array($cell));

                // Grade.
                $cell1content = get_string('grade');
                $cell2content = $grade->gradefordisplay;
                $this->add_table_row_tuple($t, $cell1content, $cell2content);

                // Graded on.
                $cell1content = get_string('gradedon', 'fastassignment');
                $cell2content = userdate($grade->timemodified);
                $this->add_table_row_tuple($t, $cell1content, $cell2content);

                // Graded by set to a real user. Not set can be empty or -1.
                if (!empty($grade->grader) && is_object($grade->grader)) {
                    $cell1content = get_string('gradedby', 'fastassignment');
                    $cell2content = $this->output->user_picture($grade->grader) .
                                    $this->output->spacer(array('width' => 30)) . fullname($grade->grader);
                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }

                // Feedback from plugins.
                foreach ($history->feedbackplugins as $plugin) {
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        !$plugin->is_empty($grade)) {

                        $pluginfeedback = new fastassignment_feedback_plugin_feedback(
                            $plugin, $grade, fastassignment_feedback_plugin_feedback::SUMMARY, $history->coursemoduleid,
                            $history->returnaction, $history->returnparams
                        );

                        $cell1content = $plugin->get_name();
                        $cell2content = $this->render($pluginfeedback);
                        $this->add_table_row_tuple($t, $cell1content, $cell2content);
                    }

                }

            }

            $o .= html_writer::table($t);
        }
        $o .= $this->box_end();

        $this->page->requires->yui_module('moodle-mod_fastassignment-history', 'Y.one("#' . $containerid . '").history');

        return $o;
    }

    /**
     * Render a submission plugin submission
     *
     * @param fastassignment_submission_plugin_submission $submissionplugin
     * @return string
     */
    public function render_fastassignment_submission_plugin_submission(fastassignment_submission_plugin_submission $submissionplugin) {
        $o = '';

        if ($submissionplugin->view == fastassignment_submission_plugin_submission::SUMMARY) {
            $showviewlink = false;
            $summary = $submissionplugin->plugin->view_summary($submissionplugin->submission,
                                                               $showviewlink);

            $classsuffix = $submissionplugin->plugin->get_subtype() .
                           '_' .
                           $submissionplugin->plugin->get_type() .
                           '_' .
                           $submissionplugin->submission->id;

            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewsubmission', 'fastassignment');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'fastassignment');
                $expandicon = $this->output->pix_icon('t/switch_plus', $expandstr);
                $options = array(
                    'class' => 'expandsummaryicon expand_' . $classsuffix,
                    'aria-label' => $expandstr,
                    'role' => 'button',
                    'aria-expanded' => 'false'
                );
                $o .= html_writer::link('', $expandicon, $options);

                $jsparams = array($submissionplugin->plugin->get_subtype(),
                                  $submissionplugin->plugin->get_type(),
                                  $submissionplugin->submission->id);

                $this->page->requires->js_init_call('M.mod_fastassignment.init_plugin_summary', $jsparams);

                $action = 'viewplugin' . $submissionplugin->plugin->get_subtype();
                $returnparams = http_build_query($submissionplugin->returnparams);
                $link .= '<noscript>';
                $urlparams = array('id' => $submissionplugin->coursemoduleid,
                                   'sid'=>$submissionplugin->submission->id,
                                   'plugin'=>$submissionplugin->plugin->get_type(),
                                   'action'=>$action,
                                   'returnaction'=>$submissionplugin->returnaction,
                                   'returnparams'=>$returnparams);
                $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width'=>15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $collapsestr = get_string('viewsummary', 'fastassignment');
                $options = array(
                    'class' => 'expandsummaryicon contract_' . $classsuffix,
                    'aria-label' => $collapsestr,
                    'role' => 'button',
                    'aria-expanded' => 'true'
                );
                $collapseicon = $this->output->pix_icon('t/switch_minus', $collapsestr);
                $o .= html_writer::link('', $collapseicon, $options);

                $o .= $submissionplugin->plugin->view($submissionplugin->submission);
                $o .= $this->output->box_end();
            }
        } else if ($submissionplugin->view == fastassignment_submission_plugin_submission::FULL) {
            $o .= $this->output->box_start('boxaligncenter submissionfull');
            $o .= $submissionplugin->plugin->view($submissionplugin->submission);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render the grading table.
     *
     * @param fastassignment_grading_table $table
     * @return string
     */
    public function render_fastassignment_grading_table(fastassignment_grading_table $table) {
        $o = '';
        $o .= $this->output->box_start('boxaligncenter gradingtable');

        $this->page->requires->js_init_call('M.mod_fastassignment.init_grading_table', array());
        $this->page->requires->string_for_js('nousersselected', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmgrantextension', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmlock', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmremovesubmission', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmreverttodraft', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmunlock', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmaddattempt', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmdownloadselected', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmsetmarkingworkflowstate', 'fastassignment');
        $this->page->requires->string_for_js('batchoperationconfirmsetmarkingallocation', 'fastassignment');
        $this->page->requires->string_for_js('editaction', 'fastassignment');
        foreach ($table->plugingradingbatchoperations as $plugin => $operations) {
            foreach ($operations as $operation => $description) {
                $this->page->requires->string_for_js('batchoperationconfirm' . $operation,
                                                     'fastassignfeedback_' . $plugin);
            }
        }
        $o .= $this->flexible_table($table, $table->get_rows_per_page(), true);
        $o .= $this->output->box_end();

        return $o;
    }

    /**
     * Render a feedback plugin feedback
     *
     * @param fastassignment_feedback_plugin_feedback $feedbackplugin
     * @return string
     */
    public function render_fastassignment_feedback_plugin_feedback(fastassignment_feedback_plugin_feedback $feedbackplugin) {
        $o = '';

        if ($feedbackplugin->view == fastassignment_feedback_plugin_feedback::SUMMARY) {
            $showviewlink = false;
            $summary = $feedbackplugin->plugin->view_summary($feedbackplugin->grade, $showviewlink);

            $classsuffix = $feedbackplugin->plugin->get_subtype() .
                           '_' .
                           $feedbackplugin->plugin->get_type() .
                           '_' .
                           $feedbackplugin->grade->id;
            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewfeedback', 'fastassignment');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'fastassignment');
                $expandicon = $this->output->pix_icon('t/switch_plus', $expandstr);
                $options = array(
                    'class' => 'expandsummaryicon expand_' . $classsuffix,
                    'aria-label' => $expandstr,
                    'role' => 'button',
                    'aria-expanded' => 'false'
                );
                $o .= html_writer::link('', $expandicon, $options);

                $jsparams = array($feedbackplugin->plugin->get_subtype(),
                                  $feedbackplugin->plugin->get_type(),
                                  $feedbackplugin->grade->id);
                $this->page->requires->js_init_call('M.mod_fastassignment.init_plugin_summary', $jsparams);

                $urlparams = array('id' => $feedbackplugin->coursemoduleid,
                                   'gid'=>$feedbackplugin->grade->id,
                                   'plugin'=>$feedbackplugin->plugin->get_type(),
                                   'action'=>'viewplugin' . $feedbackplugin->plugin->get_subtype(),
                                   'returnaction'=>$feedbackplugin->returnaction,
                                   'returnparams'=>http_build_query($feedbackplugin->returnparams));
                $url = new moodle_url('/mod/fastassignment/view.php', $urlparams);
                $link .= '<noscript>';
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width'=>15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $collapsestr = get_string('viewsummary', 'fastassignment');
                $options = array(
                    'class' => 'expandsummaryicon contract_' . $classsuffix,
                    'aria-label' => $collapsestr,
                    'role' => 'button',
                    'aria-expanded' => 'true'
                );
                $collapseicon = $this->output->pix_icon('t/switch_minus', $collapsestr);
                $o .= html_writer::link('', $collapseicon, $options);

                $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
                $o .= $this->output->box_end();
            }
        } else if ($feedbackplugin->view == fastassignment_feedback_plugin_feedback::FULL) {
            $o .= $this->output->box_start('boxaligncenter feedbackfull');
            $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render a course index summary
     *
     * @param fastassignment_course_index_summary $indexsummary
     * @return string
     */
    public function render_fastassignment_course_index_summary(fastassignment_course_index_summary $indexsummary) {
        $o = '';

        $strplural = get_string('modulenameplural', 'fastassignment');
        $strsectionname  = $indexsummary->courseformatname;
        $strduedate = get_string('duedate', 'fastassignment');
        $strsubmission = get_string('submission', 'fastassignment');
        $strgrade = get_string('grade');

        $table = new html_table();
        if ($indexsummary->usesections) {
            $table->head  = array ($strsectionname, $strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array ('left', 'left', 'center', 'right', 'right');
        } else {
            $table->head  = array ($strplural, $strduedate, $strsubmission, $strgrade);
            $table->align = array ('left', 'left', 'center', 'right');
        }
        $table->data = array();

        $currentsection = '';
        foreach ($indexsummary->assignments as $info) {
            $params = array('id' => $info['cmid']);
            $link = html_writer::link(new moodle_url('/mod/fastassignment/view.php', $params),
                                      $info['cmname']);
            $due = $info['timedue'] ? userdate($info['timedue']) : '-';

            $printsection = '';
            if ($indexsummary->usesections) {
                if ($info['sectionname'] !== $currentsection) {
                    if ($info['sectionname']) {
                        $printsection = $info['sectionname'];
                    }
                    if ($currentsection !== '') {
                        $table->data[] = 'hr';
                    }
                    $currentsection = $info['sectionname'];
                }
            }

            if ($indexsummary->usesections) {
                $row = array($printsection, $link, $due, $info['submissioninfo'], $info['gradeinfo']);
            } else {
                $row = array($link, $due, $info['submissioninfo'], $info['gradeinfo']);
            }
            $table->data[] = $row;
        }

        $o .= html_writer::table($table);

        return $o;
    }



    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     *
     * @param fastassignment_files $tree
     * @param array $dir
     * @return string
     */
    protected function htmllize_tree(fastassignment_files $tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(),
                                             $subdir['dirname'],
                                             'moodle',
                                             array('class'=>'icon'));
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                       '<div>' . $image . ' ' . s($subdir['dirname']) . '</div> ' .
                       $this->htmllize_tree($tree, $subdir) .
                       '</li>';
        }

        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            if ($CFG->enableplagiarism) {
                require_once($CFG->libdir.'/plagiarismlib.php');
                $plagiarismlinks = plagiarism_get_links(array('userid'=>$file->get_userid(),
                                                             'file'=>$file,
                                                             'cmid'=>$tree->cm->id,
                                                             'course'=>$tree->course));
            } else {
                $plagiarismlinks = '';
            }
            $image = $this->output->pix_icon(file_file_icon($file),
                                             $filename,
                                             'moodle',
                                             array('class'=>'icon'));
            $result .= '<li yuiConfig=\'' . json_encode($yuiconfig) . '\'>' .
                '<div>' .
                    '<div class="fileuploadsubmission">' . $image . ' ' .
                    $file->fileurl . ' ' .
                    $plagiarismlinks . ' ' .
                    $file->portfoliobutton . ' ' .
                    '</div>' .
                    '<div class="fileuploadsubmissiontime">' . $file->timemodified . '</div>' .
                '</div>' .
            '</li>';
        }

        $result .= '</ul>';

        return $result;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of flexible_table
     *
     * @param flexible_table $table The table to render
     * @param int $rowsperpage How many assignments to render in a page
     * @param bool $displaylinks - Whether to render links in the table
     *                             (e.g. downloads would not enable this)
     * @return string HTML
     */
    protected function flexible_table(flexible_table $table, $rowsperpage, $displaylinks) {

        $o = '';
        ob_start();
        $table->out($rowsperpage, $displaylinks);
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform) {

        $o = '';
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Defer to template..
     *
     * @param grading_app $app - All the data to render the grading app.
     */
    public function render_grading_app(grading_app $app) {
        $context = $app->export_for_template($this);
        return $this->render_from_template('mod_fastassignment/grading_app', $context);
    }
}

