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
 * This file contains the definition for the library class for comment feedback plugin
 *
 * @package   fastassignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// File component for feedback comments.
define('FASTASSIGNFEEDBACK_COMMENTS_COMPONENT', 'fastassignfeedback_comments');

// File area for feedback comments.
define('FASTASSIGNFEEDBACK_COMMENTS_FILEAREA', 'feedback');

/**
 * Library class for comment feedback plugin extending feedback plugin base class.
 *
 * @package   fastassignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fastassignment_feedback_comments extends fastassignment_feedback_plugin {

    /**
     * Get the name of the online comment feedback plugin.
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'fastassignfeedback_comments');
    }

    /**
     * Get the feedback comment from the database.
     *
     * @param int $gradeid
     * @return stdClass|false The feedback comments for the given grade if it exists.
     *                        False if it doesn't.
     */
    public function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('fastassignfeedback_comments', array('grade'=>$gradeid));
    }

    /**
     * Get quickgrading form elements as html.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param mixed $grade - The grade data - may be null if there are no grades for this user (yet)
     * @return mixed - A html string containing the html form elements required for quickgrading
     */
    public function get_quickgrading_html($userid, $grade) {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }

        $pluginname = get_string('pluginname', 'fastassignfeedback_comments');
        $labeloptions = array('for'=>'quickgrade_comments_' . $userid,
                              'class'=>'accesshide');
        $textareaoptions = array('name'=>'quickgrade_comments_' . $userid,
                                 'id'=>'quickgrade_comments_' . $userid,
                                 'class'=>'quickgrade');
        return html_writer::tag('label', $pluginname, $labeloptions) .
               html_writer::tag('textarea', $commenttext, $textareaoptions);
    }

    /**
     * Has the plugin quickgrading form element been modified in the current form submission?
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the quickgrading form element has been modified
     */
    public function is_quickgrading_modified($userid, $grade) {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }
        // Note that this handles the difference between empty and not in the quickgrading
        // form at all (hidden column).
        $newvalue = optional_param('quickgrade_comments_' . $userid, false, PARAM_RAW);
        return ($newvalue !== false) && ($newvalue != $commenttext);
    }

    /**
     * Has the comment feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return boolean True if the comment feedback has been modified, else false.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        $commenttext = '';
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $commenttext = $feedbackcomments->commenttext;
            }
        }

        $formtext = $data->fastassignfeedbackcomments_editor['text'];

        // Need to convert the form text to use @@PLUGINFILE@@ and format it so we can compare it with what is stored in the DB.
        if (isset($data->fastassignfeedbackcomments_editor['itemid'])) {
            $formtext = file_rewrite_urls_to_pluginfile($formtext, $data->fastassignfeedbackcomments_editor['itemid']);
            $formtext = format_text($formtext, FORMAT_HTML);
        }

        if ($commenttext == $formtext) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Override to indicate a plugin supports quickgrading.
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading() {
        return true;
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin.
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('comments' => get_string('pluginname', 'fastassignfeedback_comments'));
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param int $gradeid
     * @return string
     */
    public function get_editor_text($name, $gradeid) {
        if ($name == 'comments') {
            $feedbackcomments = $this->get_feedback_comments($gradeid);
            if ($feedbackcomments) {
                return $feedbackcomments->commenttext;
            }
        }

        return '';
    }

    /**
     * Get the saved text content from the editor.
     *
     * @param string $name
     * @param string $value
     * @param int $gradeid
     * @return string
     */
    public function set_editor_text($name, $value, $gradeid) {
        global $DB;

        if ($name == 'comments') {
            $feedbackcomment = $this->get_feedback_comments($gradeid);
            if ($feedbackcomment) {
                $feedbackcomment->commenttext = $value;
                return $DB->update_record('fastassignfeedback_comments', $feedbackcomment);
            } else {
                $feedbackcomment = new stdClass();
                $feedbackcomment->commenttext = $value;
                $feedbackcomment->commentformat = FORMAT_HTML;
                $feedbackcomment->grade = $gradeid;
                $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                return $DB->insert_record('fastassignfeedback_comments', $feedbackcomment) > 0;
            }
        }

        return false;
    }

    /**
     * Save quickgrading changes.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the grade changes were saved correctly
     */
    public function save_quickgrading_changes($userid, $grade) {
        global $DB;
        $feedbackcomment = $this->get_feedback_comments($grade->id);
        $quickgradecomments = optional_param('quickgrade_comments_' . $userid, null, PARAM_RAW);
        if (!$quickgradecomments && $quickgradecomments !== '') {
            return true;
        }
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $quickgradecomments;
            return $DB->update_record('fastassignfeedback_comments', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $quickgradecomments;
            $feedbackcomment->commentformat = FORMAT_HTML;
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('fastassignfeedback_comments', $feedbackcomment) > 0;
        }
    }

    /**
     * Save the settings for feedback comments plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('commentinline', !empty($data->fastassignfeedback_comments_commentinline));
        return true;
    }

    /**
     * Get the default setting for feedback comments plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
       
       // ==================== Fast Assignment add on configurations: Start ==================
        // config for checks
        $OPTIONS_MAXCHECKS = array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            '11' => '11',
            '12' => '12',
            '13' => '13',
            '14' => '14',
            '15' => '15',
            '16' => '16',
            '17' => '17',
            '18' => '18',
            '19' => '19',
            '20' => '20',
            '21' => '21',
            '22' => '22',
            '23' => '23',
            '24' => '24',
            '25' => '25'
        );

    // AUTOMATED CASE - Grammar check option and max no. of attempts allowed - amir
    $mform->addElement('checkbox', 'automatedgrammarcheck', get_string('automatedgrammarcheck', 'fastassignment'));
    $mform->setDefault('automatedgrammarcheck', true);

    $select = $mform->addElement('select', 'maxnoofchecks', get_string('maxnoofchecks', 'fastassignment'), $OPTIONS_MAXCHECKS);
    $mform->setDefault('maxnoofchecks', '5');
    
    // API POST URL - Grammar Check
    /*$mform->addElement('text', 'apiposturl_feedback', get_string('apiposturl_feedback', 'fastassignment'), array('size' => '64'));
    $mform->setType('apiposturl_feedback', PARAM_TEXT);
    $mform->addHelpButton('apiposturl_feedback', 'apiposturl_feedback', 'fastassignment');*/
    $mform->addElement('hidden','apiposturl_feedback', "https://virtualwritingtutor.com/api/checkgrammar.php");

    // API KEY
    $mform->addElement('hidden', 'api_key_feedback', get_string('getapikey', 'fastassignment'), array('size' => '64'));
    /*$mform->setType('api_key_feedback', PARAM_TEXT);
    $mform->addHelpButton('api_key_feedback', 'getapikey', 'fastassignment');*/


    // STUDENT CASE - Grammar check option and max no. of attempts allowed
    $mform->addElement('checkbox', 'studentaccessibleautoeval', get_string('studentaccessibleautoeval', 'fastassignment'));
    $mform->setDefault('studentaccessibleautoeval', true);

    $select = $mform->addElement('select', 'maxnoofchecksstudent', get_string('maxnoofchecksstudent', 'fastassignment'), $OPTIONS_MAXCHECKS);
    $mform->setDefault('maxnoofchecksstudent', '5');

    // TEACHER CASE - Grammar check option and max no. of attempts allowed
    $mform->addElement('checkbox', 'teacheraccessibleautoeval', get_string('teacheraccessibleautoeval', 'fastassignment'));
    $mform->setDefault('teacheraccessibleautoeval', true);

    $select = $mform->addElement('select', 'maxnoofchecksteacher', get_string('maxnoofchecksteacher', 'fastassignment'), $OPTIONS_MAXCHECKS);
    $mform->setDefault('maxnoofchecksteacher', '5');

    // ==================== Fast Assignment add on configurations: End ==================

    $default = $this->get_config('commentinline');
        if ($default === false) {
            // Apply the admin default if we don't have a value yet.
            $default = get_config('fastassignfeedback_comments', 'inline');
        }
        $mform->addElement('selectyesno',
                           'fastassignfeedback_comments_commentinline',
                           get_string('commentinline', 'fastassignfeedback_comments'));
        $mform->addHelpButton('fastassignfeedback_comments_commentinline', 'commentinline', 'fastassignfeedback_comments');
        $mform->setDefault('fastassignfeedback_comments_commentinline', $default);
        // Disable comment online if comment feedback plugin is disabled.
        $mform->hideIf('fastassignfeedback_comments_commentinline', 'fastassignfeedback_comments_enabled', 'notchecked');
   }

    /**
     * Convert the text from any submission plugin that has an editor field to
     * a format suitable for inserting in the feedback text field.
     *
     * @param stdClass $submission
     * @param stdClass $data - Form data to be filled with the converted submission text and format.
     * @param stdClass|null $grade
     * @return boolean - True if feedback text was set.
     */
    protected function convert_submission_text_to_feedback($submission, $data, $grade) {
        global $DB;

        $format = false;
        $text = '';

        foreach ($this->assignment->get_submission_plugins() as $plugin) {
            $fields = $plugin->get_editor_fields();
            if ($plugin->is_enabled() && $plugin->is_visible() && !$plugin->is_empty($submission) && !empty($fields)) {
                $user = $DB->get_record('user', ['id' => $submission->userid]);
                // Copy the files to the feedback area.
                if ($files = $plugin->get_files($submission, $user)) {
                    $fs = get_file_storage();
                    $component = 'fastassignfeedback_comments';
                    $filearea = FASTASSIGNFEEDBACK_COMMENTS_FILEAREA;
                    $itemid = $grade->id;
                    $fieldupdates = [
                        'component' => $component,
                        'filearea' => $filearea,
                        'itemid' => $itemid
                    ];
                    foreach ($files as $file) {
                        if ($file instanceof stored_file) {
                            // Before we create it, check that it doesn't already exist.
                            if (!$fs->file_exists(
                                    $file->get_contextid(),
                                    $component,
                                    $filearea,
                                    $itemid,
                                    $file->get_filepath(),
                                    $file->get_filename())) {
                                $fs->create_file_from_storedfile($fieldupdates, $file);
                            }
                        }
                    }
                }
                foreach ($fields as $key => $description) {
                    $rawtext = clean_text($plugin->get_editor_text($key, $submission->id));
                    $newformat = $plugin->get_editor_format($key, $submission->id);

                    if ($format !== false && $newformat != $format) {
                        // There are 2 or more editor fields using different formats, set to plain as a fallback.
                        $format = FORMAT_PLAIN;
                    } else {
                        $format = $newformat;
                    }
                    $text .= $rawtext;
                }
            }
        }

        if ($format === false) {
            $format = FORMAT_HTML;
        }
        $data->fastassignfeedbackcomments = $text;
        $data->fastassignfeedbackcommentsformat = $format;

        return true;
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        $commentinlinenabled = $this->get_config('commentinline');
        $submission = $this->assignment->get_user_submission($userid, false);
        $feedbackcomments = false;

        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
        }

        if ($feedbackcomments && !empty($feedbackcomments->commenttext)) {
            $data->fastassignfeedbackcomments = $feedbackcomments->commenttext;
            $data->fastassignfeedbackcommentsformat = $feedbackcomments->commentformat;
        } else {
            // No feedback given yet - maybe we need to copy the text from the submission?
            if (!empty($commentinlinenabled) && $submission) {
                $this->convert_submission_text_to_feedback($submission, $data, $grade);
            } else { // Set it to empty.
                $data->fastassignfeedbackcomments = '';
                $data->fastassignfeedbackcommentsformat = FORMAT_HTML;
            }
        }

        file_prepare_standard_editor(
            $data,
            'fastassignfeedbackcomments',
            $this->get_editor_options(),
            $this->assignment->get_context(),
            FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
            $grade->id
        );

        $mform->addElement('editor', 'fastassignfeedbackcomments_editor', $this->get_name(), null, $this->get_editor_options());

        return true;
    }

    /**
     * Saving the comment content into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;

        // Save the files.
        $data = file_postupdate_standard_editor(
            $data,
            'fastassignfeedbackcomments',
            $this->get_editor_options(),
            $this->assignment->get_context(),
            FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
            $grade->id
        );

        $feedbackcomment = $this->get_feedback_comments($grade->id);
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $data->fastassignfeedbackcomments;
            $feedbackcomment->commentformat = $data->fastassignfeedbackcommentsformat;
            return $DB->update_record('fastassignfeedback_comments', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $data->fastassignfeedbackcomments;
            $feedbackcomment->commentformat = $data->fastassignfeedbackcommentsformat;
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('fastassignfeedback_comments', $feedbackcomment) > 0;
        }
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            $text = $this->rewrite_feedback_comments_urls($feedbackcomments->commenttext, $grade->id);
            $text = format_text(
                $text,
                $feedbackcomments->commentformat,
                [
                    'context' => $this->assignment->get_context()
                ]
            );

            // Show the view all link if the text has been shortened.
            $short = shorten_text($text, 140);
            $showviewlink = $short != $text;
            return $short;
        }
        return '';
    }

    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            $text = $this->rewrite_feedback_comments_urls($feedbackcomments->commenttext, $grade->id);
            $text = format_text(
                $text,
                $feedbackcomments->commentformat,
                [
                    'context' => $this->assignment->get_context()
                ]
            );

            return $text;
        }
        return '';
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        if (($type == 'upload' || $type == 'uploadsingle' ||
             $type == 'online' || $type == 'offline') && $version >= 2011112900) {
            return true;
        }
        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the context for the old assignment
     * @param stdClass $oldassignment - the data for the old assignment
     * @param string $log - can be appended to by the upgrade
     * @return bool was it a success? (false will trigger a rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        if ($oldassignment->assignmenttype == 'online') {
            $this->set_config('commentinline', $oldassignment->var1);
            return true;
        }
        return true;
    }

    /**
     * Upgrade the feedback from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $grade The data record for the new grade
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $grade,
                            & $log) {
        global $DB;

        $feedbackcomments = new stdClass();
        $feedbackcomments->commenttext = $oldsubmission->submissioncomment;
        $feedbackcomments->commentformat = FORMAT_HTML;

        $feedbackcomments->grade = $grade->id;
        $feedbackcomments->assignment = $this->assignment->get_instance()->id;
        if (!$DB->insert_record('fastassignfeedback_comments', $feedbackcomments) > 0) {
            $log .= get_string('couldnotconvertgrade', 'mod_fastassignment', $grade->userid);
            return false;
        }

        return true;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format of the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commentformat;
        }
        return FORMAT_MOODLE;
    }

    /**
     * If this plugin adds to the gradebook comments field, it must format the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commenttext;
        }
        return '';
    }

    /**
     * Return any files this plugin wishes to save to the gradebook.
     *
     * @param stdClass $grade The fastassignment_grades object from the db
     * @return array
     */
    public function files_for_gradebook(stdClass $grade) : array {
        return [
            'contextid' => $this->assignment->get_context()->id,
            'component' => FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            'filearea' => FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
            'itemid' => $grade->id
        ];
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('fastassignfeedback_comments',
                            array('assignment'=>$this->assignment->get_instance()->id));
        return true;
    }

    /**
     * Returns true if there are no feedback comments for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(FASTASSIGNFEEDBACK_COMMENTS_FILEAREA => $this->get_name());
    }

    /**
     * Return a description of external params suitable for uploading an feedback comment from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        $editorparams = array('text' => new external_value(PARAM_RAW, 'The text for this feedback.'),
                              'format' => new external_value(PARAM_INT, 'The format for this feedback'));
        $editorstructure = new external_single_structure($editorparams, 'Editor structure', VALUE_OPTIONAL);
        return array('fastassignfeedbackcomments_editor' => $editorstructure);
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Convert encoded URLs in $text from the @@PLUGINFILE@@/... form to an actual URL.
     *
     * @param string $text the Text to check
     * @param int $gradeid The grade ID which refers to the id in the gradebook
     */
    private function rewrite_feedback_comments_urls(string $text, int $gradeid) {
        return file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $this->assignment->get_context()->id,
            FASTASSIGNFEEDBACK_COMMENTS_COMPONENT,
            FASTASSIGNFEEDBACK_COMMENTS_FILEAREA,
            $gradeid
        );
    }

    /**
     * File format options.
     *
     * @return array
     */
    private function get_editor_options() {
        global $COURSE;

        return [
            'subdirs' => 1,
            'maxbytes' => $COURSE->maxbytes,
            'accepted_types' => '*',
            'context' => $this->assignment->get_context(),
            'maxfiles' => EDITOR_UNLIMITED_FILES
        ];
    }
}
